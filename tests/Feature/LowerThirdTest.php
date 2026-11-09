<?php

namespace Tests\Feature;

use App\Models\LowerThird;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LowerThirdTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_manage_lower_thirds(): void
    {
        $lowerThird = LowerThird::factory()->create();

        $this->get('/lower-thirds')->assertRedirect('/login');
        $this->post("/lower-thirds/{$lowerThird->id}/show")->assertRedirect('/login');
        $this->assertFalse($lowerThird->fresh()->is_live);
    }

    public function test_operator_can_create_preset_with_logo(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post('/lower-thirds', [
                'title' => 'RIZKY PRATAMA',
                'subtitle' => 'Tim A • #7',
                'duration' => 8,
                'logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertRedirect();

        $lowerThird = LowerThird::sole();
        $this->assertSame('RIZKY PRATAMA', $lowerThird->title);
        $this->assertStringStartsWith('/storage/lower-thirds/', $lowerThird->logo);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $lowerThird->logo));
    }

    public function test_showing_a_preset_replaces_the_one_on_air(): void
    {
        $user = User::factory()->create();
        [$first, $second] = LowerThird::factory()->count(2)->create();

        $this->actingAs($user)->post("/lower-thirds/{$first->id}/show")->assertRedirect();
        $this->getJson('/api/lower-third')->assertJsonPath('lowerThird.id', $first->id);

        $this->actingAs($user)->post("/lower-thirds/{$second->id}/show")->assertRedirect();
        $this->getJson('/api/lower-third')->assertJsonPath('lowerThird.id', $second->id);
        $this->assertFalse($first->fresh()->is_live);

        $this->actingAs($user)->post('/lower-thirds/hide')->assertRedirect();
        $this->getJson('/api/lower-third')->assertJsonPath('lowerThird', null);
    }

    public function test_lower_third_with_duration_goes_off_air_automatically(): void
    {
        LowerThird::factory()->live(duration: 5)->create();

        $this->getJson('/api/lower-third')->assertJsonPath('lowerThird.remaining', 5);

        $this->travel(6)->seconds();
        $this->getJson('/api/lower-third')->assertJsonPath('lowerThird', null);
    }

    public function test_deleting_preset_removes_its_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('lower-thirds/logo.png', 'x');
        $lowerThird = LowerThird::factory()->create(['logo' => '/storage/lower-thirds/logo.png']);

        $this->actingAs(User::factory()->create())
            ->delete("/lower-thirds/{$lowerThird->id}")
            ->assertRedirect();

        $this->assertModelMissing($lowerThird);
        Storage::disk('public')->assertMissing('lower-thirds/logo.png');
    }
}
