<?php

namespace Tests\Feature;

use App\Models\LowerThird;
use App\Models\Pertandingan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverlayControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_control_overlay(): void
    {
        $this->postJson('/overlay/visibility', ['scene' => 'live', 'visible' => false])->assertUnauthorized();
        $this->postJson('/overlay/serve-cue')->assertUnauthorized();
    }

    public function test_operator_can_hide_and_show_a_scene(): void
    {
        $this->actingAs(User::factory()->create());
        Pertandingan::newMatch()->save();

        $this->getJson('/api/match')->assertJsonPath('overlay.live', true);

        $this->postJson('/overlay/visibility', ['scene' => 'live', 'visible' => false])
            ->assertOk()
            ->assertJsonPath('live', false)
            ->assertJsonPath('end', true);
        $this->getJson('/api/match')->assertJsonPath('overlay.live', false);

        $this->postJson('/overlay/visibility', ['scene' => 'live', 'visible' => true]);
        $this->getJson('/api/match')->assertJsonPath('overlay.live', true);
    }

    public function test_serve_cue_changes_on_each_request(): void
    {
        $this->actingAs(User::factory()->create());

        $first = $this->postJson('/overlay/serve-cue')->assertOk()->json('serveCue');
        $this->travel(1)->seconds();
        $second = $this->postJson('/overlay/serve-cue')->json('serveCue');

        $this->assertGreaterThan($first, $second);
    }

    public function test_finishing_match_hides_every_overlay(): void
    {
        $this->actingAs(User::factory()->create());
        Pertandingan::newMatch()->save();
        $lowerThird = LowerThird::factory()->live()->create();

        $this->post('/match/finish')->assertRedirect('/settings');

        $this->getJson('/api/match?latest=1')
            ->assertJsonPath('overlay.live', false)
            ->assertJsonPath('overlay.start', false)
            ->assertJsonPath('overlay.end', false);
        $this->assertFalse($lowerThird->fresh()->is_live);
    }

    public function test_invalid_scene_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/overlay/visibility', ['scene' => 'lower', 'visible' => false])
            ->assertUnprocessable();
    }
}
