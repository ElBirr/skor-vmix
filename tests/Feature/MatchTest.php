<?php

namespace Tests\Feature;

use App\Models\Pertandingan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchTest extends TestCase
{
    use RefreshDatabase;

    private function createMatch(): void
    {
        $this->post('/match', [
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

        $this->postJson('/match/score', ['side' => 'home', 'delta' => -1])->assertOk();
        $this->assertSame(0, Pertandingan::active()->first()->home_score);

        foreach (range(1, 3) as $i) {
            $this->postJson('/match/timeout', ['side' => 'away', 'delta' => 1])->assertOk();
        }
        $this->assertSame(2, Pertandingan::active()->first()->away_timeouts);

        foreach (range(1, 3) as $i) {
            $this->postJson('/match/timeout', ['side' => 'away', 'delta' => -1])->assertOk();
        }
        $this->assertSame(0, Pertandingan::active()->first()->away_timeouts);
    }

    public function test_win_set_records_set_and_advances(): void
    {
        $this->createMatch();
        $this->postJson('/match/score', ['side' => 'away', 'delta' => 3]);

        // tim away menang set dengan skor berjalan 0-3
        $this->postJson('/match/set', ['side' => 'away'])->assertOk();
        $m = Pertandingan::active()->first();
        $this->assertSame([['home' => 0, 'away' => 3]], $m->set_scores);
        $this->assertSame(0, $m->home_score);
        $this->assertSame(0, $m->away_score);
        $this->assertSame(2, $m->current_set);

        // skor imbang 0-0, set tetap diberi ke tim yang menekan
        $this->postJson('/match/set', ['side' => 'home'])->assertOk();
        $m = Pertandingan::active()->first();
        $this->assertSame([['home' => 0, 'away' => 3], ['home' => 1, 'away' => 0]], $m->set_scores);
        $this->assertSame(1, $m->setsWon()['home']);
        $this->assertSame(1, $m->setsWon()['away']);

        // minus home: batalkan set terakhir milik home (1-0), skornya jadi skor berjalan lagi
        $this->postJson('/match/set', ['side' => 'home', 'delta' => -1])->assertOk();
        $m = Pertandingan::active()->first();
        $this->assertSame([['home' => 0, 'away' => 3]], $m->set_scores);
        $this->assertSame(2, $m->current_set);
        $this->assertSame(1, $m->home_score);
        $this->assertSame(0, $m->away_score);

        // minus home lagi: set tersisa milik away — tidak dihapus, tidak ada error
        $this->postJson('/match/set', ['side' => 'home', 'delta' => -1])->assertOk();
        $m = Pertandingan::active()->first();
        $this->assertSame([['home' => 0, 'away' => 3]], $m->set_scores);
        $this->assertSame(2, $m->current_set);
    }
}
