<?php

namespace Tests\Feature;

use App\Models\Pertandingan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchTest extends TestCase
{
    use RefreshDatabase;

    private function createMatch(): void
    {
        $this->actingAs(User::factory()->create())->post('/match', [
            'sport' => 'volley',
            'pos' => 'bottom',
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
    }

    public function test_settings_save_without_score_fields_keeps_live_score(): void
    {
        $this->createMatch();
        $this->postJson('/match/score', ['side' => 'home', 'delta' => 1]);

        // form settings tidak lagi mengirim skor/timeout/serving
        $this->post('/match', [
            'sport' => 'volley',
            'pos' => 'bottom',
            'period' => 'SET 1',
            'status' => 'LIVE',
            'home_name' => 'INDONESIA',
            'away_name' => 'JEPANG',
            'home_sub' => '',
            'away_sub' => '',
            'home_color' => '#e11d48',
            'away_color' => '#2563eb',
            'accent' => '#f59e0b',
            'current_set' => 1,
            'set_scores_text' => '',
        ]);

        $this->assertDatabaseHas('matches', [
            'home_name' => 'INDONESIA',
            'home_score' => 1,
            'away_score' => 0,
            'serving' => 'home',
        ]);
    }

    public function test_adjust_score_and_timeout_clamp(): void
    {
        $this->createMatch();

        $this->postJson('/match/score', ['side' => 'home', 'delta' => -1, 'reason' => 'Koreksi skor'])->assertOk();
        $this->assertSame(0, Pertandingan::active()->first()->home_score);

        foreach (range(1, 3) as $i) {
            $this->postJson('/match/timeout', ['side' => 'away', 'delta' => 1])->assertOk();
        }
        $this->assertSame(2, Pertandingan::active()->first()->away_timeouts);

        foreach (range(1, 3) as $i) {
            $this->postJson('/match/timeout', ['side' => 'away', 'delta' => -1, 'reason' => 'Koreksi timeout'])->assertOk();
        }
        $this->assertSame(0, Pertandingan::active()->first()->away_timeouts);
    }

    public function test_volleyball_set_requires_a_two_point_lead(): void
    {
        $this->createMatch();

        foreach (range(1, 24) as $i) {
            $this->postJson('/match/score', ['side' => 'home', 'delta' => 1])->assertOk();
            $this->postJson('/match/score', ['side' => 'away', 'delta' => 1])->assertOk();
        }

        $this->postJson('/match/score', ['side' => 'home', 'delta' => 1])->assertOk();
        $m = Pertandingan::active()->first();
        $this->assertSame([], $m->set_scores);
        $this->assertSame(25, $m->home_score);
        $this->assertSame(24, $m->away_score);

        $this->postJson('/match/score', ['side' => 'home', 'delta' => 1])->assertOk();
        $m = Pertandingan::active()->first();
        $this->assertSame([['home' => 26, 'away' => 24]], $m->set_scores);
        $this->assertSame(2, $m->current_set);
        $this->assertSame(0, $m->home_score);
        $this->assertSame(0, $m->away_score);
    }

    public function test_deciding_set_finishes_match_at_fifteen_with_two_point_lead(): void
    {
        $this->createMatch();
        $match = Pertandingan::active()->first();
        $match->update([
            'set_scores' => [
                ['home' => 25, 'away' => 20],
                ['home' => 20, 'away' => 25],
                ['home' => 25, 'away' => 20],
                ['home' => 20, 'away' => 25],
            ],
            'current_set' => 5,
            'home_score' => 14,
            'away_score' => 13,
        ]);

        $this->postJson('/match/score', ['side' => 'home', 'delta' => 1])->assertOk();

        $match->refresh();
        $this->assertTrue($match->is_finished);
        $this->assertSame('SELESAI', $match->status);
        $this->assertSame(['home' => 15, 'away' => 13], $match->set_scores[4]);
        $this->postJson('/match/score', ['side' => 'home', 'delta' => 1])->assertUnprocessable();
    }

    public function test_score_correction_requires_a_reason_and_is_audited(): void
    {
        $this->createMatch();
        $this->postJson('/match/score', ['side' => 'home', 'delta' => 1])->assertOk();
        $this->postJson('/match/score', ['side' => 'home', 'delta' => -1])->assertUnprocessable();
        $this->postJson('/match/score', ['side' => 'home', 'delta' => -1, 'reason' => 'Tombol terpencet'])->assertOk();

        $this->assertDatabaseHas('match_events', [
            'type' => 'score_corrected',
            'reason' => 'Tombol terpencet',
        ]);
    }

    public function test_finishing_a_match_archives_it_and_opens_new_match_settings(): void
    {
        $this->createMatch();
        $match = Pertandingan::active()->first();

        $this->post('/match/finish')->assertRedirect('/settings');

        $this->assertDatabaseHas('matches', [
            'id' => $match->id,
            'is_active' => 0,
            'is_finished' => 1,
            'status' => 'SELESAI',
        ]);
        $this->assertDatabaseHas('match_events', ['match_id' => $match->id, 'type' => 'match_finished']);
        $this->get('/history')->assertOk()->assertSee('TIM A');
        $this->get('/history/'.$match->id)->assertOk()->assertSee('TIM A')->assertSee('match finished');
        $this->get('/settings')->assertOk()->assertSee('Siapkan Pertandingan Baru');
    }

    public function test_new_match_keeps_finished_match_in_history(): void
    {
        $this->createMatch();
        $finishedMatch = Pertandingan::active()->first();
        $this->post('/match/finish');

        $this->post('/match', [
            'sport' => 'futsal',
            'pos' => 'top',
            'period' => '1H',
            'clock' => '20:00',
            'status' => 'LIVE',
            'home_name' => 'TIM BARU A',
            'away_name' => 'TIM BARU B',
            'home_sub' => '',
            'away_sub' => '',
            'home_color' => '#e11d48',
            'away_color' => '#2563eb',
            'accent' => '#f59e0b',
        ])->assertRedirect();

        $this->assertDatabaseHas('matches', ['id' => $finishedMatch->id, 'is_active' => 0, 'is_finished' => 1]);
        $this->assertDatabaseCount('matches', 2);
        $this->assertSame('TIM BARU A', Pertandingan::active()->first()->home_name);
    }

    public function test_score_controls_require_an_authenticated_operator(): void
    {
        $this->postJson('/match/score', ['side' => 'home', 'delta' => 1])->assertUnauthorized();
    }
}
