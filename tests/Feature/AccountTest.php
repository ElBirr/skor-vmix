<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_registered_user_becomes_admin(): void
    {
        $this->post('/register', [
            'name' => 'Admin Pertama',
            'email' => 'admin@example.com',
            'password' => 'sandi-pertama-123',
            'password_confirmation' => 'sandi-pertama-123',
        ])->assertRedirect(route('scoreboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => 'sandi-lama-12345',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'sandi-lama-12345',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/account', ['name' => 'Nama Baru', 'email' => 'baru@example.com'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Baru', 'email' => 'baru@example.com']);
    }

    public function test_profile_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'dipakai@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/account', ['name' => 'Nama', 'email' => 'dipakai@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'sandi-lama-12345']);

        $this->actingAs($user)
            ->put('/account/password', [
                'current_password' => 'salah',
                'password' => 'sandi-baru-12345',
                'password_confirmation' => 'sandi-baru-12345',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('sandi-lama-12345', $user->fresh()->password));
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create(['password' => 'sandi-lama-12345']);

        $this->actingAs($user)
            ->put('/account/password', [
                'current_password' => 'sandi-lama-12345',
                'password' => 'sandi-baru-12345',
                'password_confirmation' => 'sandi-baru-12345',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('sandi-baru-12345', $user->fresh()->password));
    }
}
