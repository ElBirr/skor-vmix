<?php

namespace Tests\Feature;

use App\Models\Pertandingan;
use App\Models\User;
use Database\Seeders\MatchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MatchTest extends TestCase
{
    use RefreshDatabase;

    private function createMatch(): void
    {
        $this->actingAs(User::factory()->create())->post('/match', [
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
    }

    public function test_settings_save_without_score_fields_keeps_live_score(): void
    {
        $this->createMatch();
        $this->postJson('/match/score', ['side' => 'home', 'delta' => 1]);

        // form settings tidak lagi mengirim skor/timeout/serving
        $this->post('/match', [
            'sport' => 'volley',
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

    public function test_overlay_api_reports_running_timeout_until_it_expires(): void
    {
        $this->createMatch();
        $this->getJson('/api/match')->assertJsonPath('timeout', null);

        $this->postJson('/match/timeout', ['side' => 'away', 'delta' => 1])->assertOk();
        $this->getJson('/api/match')
            ->assertJsonPath('timeout.side', 'away')
            ->assertJsonPath('timeout.duration', 30);

        $this->travel(31)->seconds();
        $this->getJson('/api/match')->assertJsonPath('timeout', null);
    }

    public function test_overlay_api_clears_timeout_after_correction(): void
    {
        $this->createMatch();

        $this->postJson('/match/timeout', ['side' => 'home', 'delta' => 1])->assertOk();
        $this->postJson('/match/timeout', ['side' => 'home', 'delta' => -1, 'reason' => 'Salah tekan'])->assertOk();

        $this->getJson('/api/match')->assertJsonPath('timeout', null);
    }

    public function test_theme_colors_are_saved_and_carried_to_next_match(): void
    {
        $this->createMatch();

        $match = Pertandingan::active()->first();
        $this->post('/match', [
            ...$match->only(['sport', 'period', 'home_name', 'away_name', 'home_color', 'away_color']),
            'theme_primary' => '#112233',
            'theme_accent' => '#ff8800',
        ])->assertRedirect();

        $this->getJson('/api/match')
            ->assertJsonPath('theme.primary', '#112233')
            ->assertJsonPath('theme.accent', '#ff8800');

        $this->assertSame('#ff8800', Pertandingan::newMatch()->theme_accent);
    }

    public function test_venue_and_date_are_sent_to_overlay(): void
    {
        $this->createMatch();

        $match = Pertandingan::active()->first();
        $this->post('/match', [
            ...$match->only(['sport', 'period', 'home_name', 'away_name', 'home_color', 'away_color']),
            'headline' => 'PORPROV 2026',
            'round' => 'SEMIFINAL',
            'venue' => 'GOR Satria',
            'match_date' => '2026-11-09',
        ])->assertRedirect();

        $this->getJson('/api/match')
            ->assertJsonPath('headline', 'PORPROV 2026')
            ->assertJsonPath('round', 'SEMIFINAL')
            ->assertJsonPath('venue', 'GOR Satria')
            ->assertJsonPath('date', '2026-11-09');
    }

    public function test_too_large_upload_shows_friendly_message(): void
    {
        $this->actingAs(User::factory()->create())
            ->call('POST', '/match', [], [], [], ['CONTENT_LENGTH' => 50 * 1024 * 1024])
            ->assertStatus(413)
            ->assertSee('Upload terlalu besar');
    }

    public function test_end_scene_api_reports_totals_and_longest_run(): void
    {
        $this->createMatch();

        foreach (['home', 'home', 'home', 'away', 'home', 'away', 'away'] as $side) {
            $this->postJson('/match/score', ['side' => $side, 'delta' => 1])->assertOk();
        }
        // Koreksi poin away terakhir: run away jadi 1.
        $this->postJson('/match/score', ['side' => 'away', 'delta' => -1, 'reason' => 'Salah tekan'])->assertOk();

        $this->getJson('/api/match?latest=1')
            ->assertJsonPath('stats.totals.home', 4)
            ->assertJsonPath('stats.totals.away', 2)
            ->assertJsonPath('stats.longestRun.home', 3)
            ->assertJsonPath('stats.longestRun.away', 1);

        $this->getJson('/api/match')->assertJsonPath('stats', null);
    }

    public function test_control_page_requires_login_and_renders(): void
    {
        $this->get('/control')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/control')
            ->assertOk()
            ->assertSee('Kontrol vMix');
    }

    public function test_swap_sides_exchanges_all_team_data(): void
    {
        $this->createMatch();

        foreach (range(1, 25) as $i) {
            $this->postJson('/match/score', ['side' => 'home', 'delta' => 1])->assertOk();
        }
        $this->postJson('/match/score', ['side' => 'away', 'delta' => 1])->assertOk();
        $this->postJson('/match/timeout', ['side' => 'away', 'delta' => 1])->assertOk();

        $this->postJson('/match/swap')
            ->assertOk()
            ->assertJsonPath('home.name', 'TIM B')
            ->assertJsonPath('away.name', 'TIM A')
            ->assertJsonPath('home.color', '#2563eb')
            ->assertJsonPath('home.score', 1)
            ->assertJsonPath('home.timeouts', 1)
            ->assertJsonPath('serving', 'home')
            ->assertJsonPath('sets.0.home', 0)
            ->assertJsonPath('sets.0.away', 25)
            ->assertJsonPath('away.setsWon', 1);

        // Timeout yang sedang berjalan ikut pindah sisi.
        $this->getJson('/api/match')->assertJsonPath('timeout.side', 'home');

        // Poin beruntun 25 milik TIM A kini tercatat di sisi away.
        $this->getJson('/api/match?latest=1')->assertJsonPath('stats.longestRun.away', 25);
    }

    public function test_end_scene_reports_match_duration(): void
    {
        $this->createMatch();

        $this->postJson('/match/score', ['side' => 'home', 'delta' => 1])->assertOk();
        $this->travel(83)->minutes();
        $this->postJson('/match/score', ['side' => 'away', 'delta' => 1])->assertOk();

        $this->getJson('/api/match?latest=1')->assertJsonPath('durationMinutes', 83);
        $this->getJson('/api/match')->assertJsonPath('durationMinutes', null);
    }

    public function test_best_of_three_finishes_after_two_sets_with_third_set_to_fifteen(): void
    {
        $this->createMatch();
        $match = Pertandingan::active()->first();
        $this->post('/match', [
            ...$match->only(['sport', 'period', 'home_name', 'away_name', 'home_color', 'away_color']),
            'best_of' => 3,
        ])->assertRedirect();
        $this->getJson('/api/match')->assertJsonPath('bestOf', 3);

        // Set 1 home, set 2 away.
        foreach (['home', 'away'] as $side) {
            foreach (range(1, 25) as $i) {
                $this->postJson('/match/score', ['side' => $side, 'delta' => 1])->assertOk();
            }
        }
        $this->assertFalse(Pertandingan::active()->first()->is_finished);

        // Set ke-3 (penentu) cukup sampai 15 dan langsung mengakhiri pertandingan.
        foreach (range(1, 15) as $i) {
            $this->postJson('/match/score', ['side' => 'home', 'delta' => 1])->assertOk();
        }
        $m = Pertandingan::active()->first();
        $this->assertSame([['home' => 25, 'away' => 0], ['home' => 0, 'away' => 25], ['home' => 15, 'away' => 0]], $m->set_scores);
        $this->assertTrue($m->is_finished);
        $this->assertSame(['home' => 2, 'away' => 1], $m->setsWon());
    }

    public function test_best_of_five_still_needs_three_sets(): void
    {
        $this->createMatch();
        $match = Pertandingan::active()->first();
        $this->post('/match', [
            ...$match->only(['sport', 'period', 'home_name', 'away_name', 'home_color', 'away_color']),
            'best_of' => 5,
        ])->assertRedirect();

        foreach (range(1, 50) as $i) {
            $this->postJson('/match/score', ['side' => 'home', 'delta' => 1])->assertOk();
        }
        $m = Pertandingan::active()->first();
        $this->assertSame(2, count($m->set_scores));
        $this->assertFalse($m->is_finished);
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
        $this->assertSame('SET 2', $m->period);
        $this->assertSame(0, $m->home_score);
        $this->assertSame(0, $m->away_score);
    }

    public function test_deciding_set_finishes_match_at_fifteen_with_two_point_lead(): void
    {
        $this->createMatch();
        $match = Pertandingan::active()->first();
        $match->update([
            'best_of' => 5,
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

    public function test_empty_color_is_rejected_instead_of_crashing(): void
    {
        $this->createMatch();

        $this->from('/settings')->post('/match', [
            'sport' => 'volley',
            'period' => 'SET 1',
            'status' => 'LIVE',
            'home_name' => 'TIM A',
            'away_name' => 'TIM B',
            'home_color' => '',
            'away_color' => '#2563eb',
            'accent' => '#f59e0b',
        ])->assertRedirect('/settings')->assertSessionHasErrors('home_color');

        $this->assertDatabaseHas('matches', ['home_color' => '#e11d48']);
    }

    public function test_finished_match_cannot_be_edited_from_settings(): void
    {
        $this->createMatch();
        $match = Pertandingan::active()->first();
        $match->update(['is_finished' => true]);

        $this->from('/settings')->post('/match', [
            'sport' => 'volley',
            'period' => 'SET 1',
            'status' => 'LIVE',
            'home_name' => 'DIUBAH',
            'away_name' => 'TIM B',
            'home_color' => '#e11d48',
            'away_color' => '#2563eb',
            'accent' => '#f59e0b',
        ])->assertSessionHasErrors('match');

        $this->assertSame('TIM A', $match->fresh()->home_name);
    }

    public function test_minus_on_zero_score_undoes_the_last_completed_set(): void
    {
        $this->createMatch();
        $match = Pertandingan::active()->first();
        $match->update([
            'set_scores' => [['home' => 25, 'away' => 20]],
            'current_set' => 2,
            'home_score' => 0,
            'away_score' => 0,
        ]);

        $this->postJson('/match/score', ['side' => 'home', 'delta' => -1, 'reason' => 'Set salah tercatat'])->assertOk();

        $match->refresh();
        $this->assertSame([], $match->set_scores);
        $this->assertSame(25, $match->home_score);
        $this->assertSame(20, $match->away_score);
        $this->assertSame(1, $match->current_set);
    }

    public function test_seeder_does_not_overwrite_the_live_match(): void
    {
        $this->createMatch();
        $this->seed(MatchSeeder::class);

        $this->assertDatabaseCount('matches', 1);
        $this->assertSame('TIM A', Pertandingan::active()->first()->home_name);
    }

    public function test_overlay_renders_scorebug_and_intro_scenes(): void
    {
        $this->get('/overlay')
            ->assertOk()
            ->assertSee('id="scorebug"', false)
            ->assertSee('id="intro"', false)
            ->assertSee('id="introSets"', false);
    }

    public function test_finishing_records_the_current_set_for_the_end_scene(): void
    {
        $this->createMatch();
        $match = Pertandingan::active()->first();
        $match->update([
            'set_scores' => [['home' => 25, 'away' => 20]],
            'current_set' => 2,
            'home_score' => 12,
            'away_score' => 10,
        ]);

        $this->post('/match/finish')->assertRedirect('/settings');

        $this->assertSame(
            [['home' => 25, 'away' => 20], ['home' => 12, 'away' => 10]],
            $match->fresh()->set_scores
        );

        $this->getJson('/api/match?latest=1')
            ->assertOk()
            ->assertJsonPath('sets.0.home', 25)
            ->assertJsonPath('sets.1.home', 12)
            ->assertJsonPath('sets.1.away', 10);
    }

    public function test_end_scene_falls_back_to_the_latest_finished_match(): void
    {
        $this->createMatch();
        $this->post('/match/finish');

        $this->getJson('/api/match')->assertNotFound();
        $this->getJson('/api/match?latest=1')
            ->assertOk()
            ->assertJsonPath('home.name', 'TIM A')
            ->assertJsonPath('isFinished', true);
    }

    public function test_logo_uploads_are_saved_and_exposed_to_the_overlay(): void
    {
        Storage::fake('public');
        $this->createMatch();

        $this->post('/match', [
            'sport' => 'volley',
            'period' => 'SET 1',
            'status' => 'LIVE',
            'home_name' => 'TIM A',
            'away_name' => 'TIM B',
            'home_color' => '#e11d48',
            'away_color' => '#2563eb',
            'logo' => UploadedFile::fake()->image('event.png'),
            'home_logo' => UploadedFile::fake()->image('home.png'),
            'away_logo' => UploadedFile::fake()->image('away.png'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $match = Pertandingan::active()->first();

        foreach (['logo', 'home_logo', 'away_logo'] as $field) {
            $this->assertStringStartsWith('/storage/logos/', $match->{$field});
            Storage::disk('public')->assertExists(str_replace('/storage/', '', $match->{$field}));
        }

        $this->getJson('/api/match')
            ->assertOk()
            ->assertJsonPath('logo', $match->logo)
            ->assertJsonPath('home.logo', $match->home_logo)
            ->assertJsonPath('away.logo', $match->away_logo);

        // Tanpa upload baru, logo lama tetap dipakai.
        $this->post('/match', [
            'sport' => 'volley',
            'period' => 'SET 1',
            'status' => 'LIVE',
            'home_name' => 'TIM A',
            'away_name' => 'TIM B',
            'home_color' => '#e11d48',
            'away_color' => '#2563eb',
        ])->assertRedirect();

        $this->assertSame($match->home_logo, $match->fresh()->home_logo);
    }

    public function test_logo_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $this->createMatch();

        $this->post('/match', [
            'sport' => 'volley',
            'period' => 'SET 1',
            'status' => 'LIVE',
            'home_name' => 'TIM A',
            'away_name' => 'TIM B',
            'home_color' => '#e11d48',
            'away_color' => '#2563eb',
            'home_logo' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('home_logo');

        $this->assertNull(Pertandingan::active()->first()->home_logo);
    }
}
