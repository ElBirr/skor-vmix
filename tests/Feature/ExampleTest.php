<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_can_create_match_when_none_exists(): void
    {
        $response = $this->actingAs(User::factory()->create())->post('/match', [
            'sport' => 'volley',
            'period' => 'SET 1',
            'clock' => '',
            'status' => 'LIVE',
            'home_name' => 'TIM A',
            'away_name' => 'TIM B',
            'home_sub' => '',
            'away_sub' => '',
            'home_color' => '#e11d48',
            'away_color' => '#2563eb',
            'accent' => '#f59e0b',
            'home_score' => 0,
            'away_score' => 0,
            'current_set' => 1,
            'serving' => '',
            'home_timeouts' => 0,
            'away_timeouts' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('matches', [
            'home_name' => 'TIM A',
            'away_name' => 'TIM B',
            'is_active' => 1,
        ]);
    }
}
