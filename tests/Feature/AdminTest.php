<?php

namespace Tests\Feature;

use App\Models\MatchEvent;
use App\Models\Pertandingan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function operator(): User
    {
        return User::factory()->create(['role' => 'operator']);
    }

    private function finishedMatch(array $attributes = []): Pertandingan
    {
        return Pertandingan::create($attributes + [
            'sport' => 'volley',
            'home_name' => 'Garuda',
            'away_name' => 'Merdeka',
            'is_active' => false,
            'is_finished' => true,
            'status' => 'SELESAI',
        ]);
    }

    public function test_guest_and_operator_cannot_open_admin_pages(): void
    {
        $this->get('/admin/data')->assertRedirect('/login');

        $this->actingAs($this->operator())->get('/admin/data')->assertForbidden();
        $this->actingAs($this->operator())->get('/admin/users')->assertForbidden();
        $this->actingAs($this->operator())->get('/admin/system')->assertForbidden();
        $this->actingAs($this->operator())->get('/admin/backup')->assertForbidden();
    }

    public function test_admin_can_open_admin_pages(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/data')->assertOk();
        $this->get('/admin/data/matches')->assertOk();
        $this->get('/admin/data/match_events')->assertOk();
        $this->get('/admin/data/users')->assertOk();
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/system')->assertOk();
    }

    public function test_data_explorer_rejects_unknown_tables_and_hides_password(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/data/sessions')->assertNotFound();
        $this->get('/admin/data/password_reset_tokens')->assertNotFound();

        $this->get('/admin/data/users')
            ->assertOk()
            ->assertDontSee('remember_token')
            ->assertDontSee('$2y$');

        $this->finishedMatch();
        $this->get('/admin/data/matches')->assertOk()->assertSee('Garuda');

        $export = $this->get('/admin/data/users/export')->assertOk();
        $csv = $export->streamedContent();
        $this->assertStringNotContainsString('remember_token', $csv);
        $this->assertStringNotContainsString('$2y$', $csv);
    }

    public function test_operator_management_and_last_admin_guard(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->post('/admin/users', [
            'name' => 'Operator Baru',
            'email' => 'operator@example.com',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'operator@example.com',
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->put('/admin/users/'.$admin->id, [
            'role' => 'operator',
            'is_active' => '1',
        ])->assertSessionHasErrors('user');

        $this->put('/admin/users/'.$admin->id, [
            'role' => 'admin',
            'is_active' => '0',
        ])->assertSessionHasErrors('user');
    }

    public function test_admin_can_reset_operator_password(): void
    {
        $user = $this->operator();
        $this->actingAs($this->admin());

        $this->put('/admin/users/'.$user->id.'/password', [
            'password' => 'sandi-baru-12345',
            'password_confirmation' => 'sandi-baru-12345',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('sandi-baru-12345', $user->fresh()->password));
    }

    public function test_admin_deletes_finished_match_with_events_and_unused_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logos/logo-lama.png', 'x');

        $match = $this->finishedMatch(['logo' => '/storage/logos/logo-lama.png']);
        MatchEvent::create([
            'match_id' => $match->id,
            'type' => 'match_finished',
            'before' => [],
            'after' => [],
        ]);

        $this->actingAs($this->admin())
            ->delete('/admin/matches/'.$match->id, ['confirm' => 'HAPUS'])
            ->assertRedirect(route('history'));

        $this->assertDatabaseMissing('matches', ['id' => $match->id]);
        $this->assertDatabaseMissing('match_events', ['match_id' => $match->id]);
        Storage::disk('public')->assertMissing('logos/logo-lama.png');
    }

    public function test_match_delete_requires_finished_match_and_typed_confirmation(): void
    {
        $active = $this->finishedMatch(['is_active' => true, 'is_finished' => false]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete('/admin/matches/'.$active->id, ['confirm' => 'HAPUS'])
            ->assertSessionHasErrors('match');
        $this->assertDatabaseHas('matches', ['id' => $active->id]);

        $finished = $this->finishedMatch();

        $this->actingAs($admin)
            ->delete('/admin/matches/'.$finished->id, ['confirm' => 'salah'])
            ->assertSessionHasErrors('confirm');
        $this->assertDatabaseHas('matches', ['id' => $finished->id]);
    }

    public function test_logo_cleanup_keeps_referenced_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logos/dipakai.png', 'x');
        Storage::disk('public')->put('logos/yatim.png', 'x');

        $this->finishedMatch(['logo' => '/storage/logos/dipakai.png']);
        $this->actingAs($this->admin());

        $this->post('/admin/system/logos')->assertRedirect();

        Storage::disk('public')->assertExists('logos/dipakai.png');
        Storage::disk('public')->assertMissing('logos/yatim.png');
    }

    public function test_history_can_be_filtered(): void
    {
        $this->finishedMatch(['home_name' => 'Garuda', 'sport' => 'volley']);
        $this->finishedMatch(['home_name' => 'Elang', 'sport' => 'futsal']);

        $this->actingAs($this->operator());

        $this->get('/history?q=Garuda')->assertOk()->assertSee('Garuda')->assertDontSee('Elang');
        $this->get('/history?sport=futsal')->assertOk()->assertSee('Elang')->assertDontSee('Garuda');
    }
}
