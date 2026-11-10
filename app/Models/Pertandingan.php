<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Pertandingan extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'sport',
        'best_of',
        'home_name',
        'away_name',
        'home_sub',
        'away_sub',
        'home_logo',
        'away_logo',
        'home_score',
        'away_score',
        'period',
        'clock',
        'status',
        'headline',
        'round',
        'venue',
        'match_date',
        'home_color',
        'away_color',
        'theme_primary',
        'theme_accent',
        'logo',
        'set_scores',
        'current_set',
        'serving',
        'home_timeouts',
        'away_timeouts',
        'is_active',
        'is_finished',
    ];

    protected $casts = [
        'set_scores' => 'array',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'current_set' => 'integer',
        'best_of' => 'integer',
        'home_timeouts' => 'integer',
        'away_timeouts' => 'integer',
        'is_active' => 'boolean',
        'is_finished' => 'boolean',
        'match_date' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function newMatch(): self
    {
        // Tema warna mengikuti pertandingan terakhir supaya satu event tetap seragam.
        $previous = self::latest('id')->first();

        return new self([
            'headline' => $previous->headline ?? null,
            'theme_primary' => $previous->theme_primary ?? '#001c54',
            'theme_accent' => $previous->theme_accent ?? '#c00000',
            'is_active' => true,
            'is_finished' => false,
            'sport' => 'volley',
            // Format mengikuti pertandingan sebelumnya; default best of 3 (babak penyisihan).
            'best_of' => $previous->best_of ?? 3,
            'period' => 'SET 1',
            'clock' => '',
            'status' => 'LIVE',
            'home_name' => 'TIM A',
            'away_name' => 'TIM B',
            'home_sub' => '',
            'away_sub' => '',
            'home_color' => '#e11d48',
            'away_color' => '#2563eb',
            'home_score' => 0,
            'away_score' => 0,
            'current_set' => 1,
            'set_scores' => [],
            'serving' => '',
            'home_timeouts' => 0,
            'away_timeouts' => 0,
        ]);
    }

    public function bestOf(): int
    {
        return $this->best_of === 3 ? 3 : 5;
    }

    /**
     * Jumlah set yang harus dimenangkan: 2 (best of 3) atau 3 (best of 5).
     */
    public function setsToWin(): int
    {
        return intdiv($this->bestOf(), 2) + 1;
    }

    public function setsWon(): array
    {
        $home = 0;
        $away = 0;

        foreach ($this->set_scores ?? [] as $set) {
            if (($set['home'] ?? 0) > ($set['away'] ?? 0)) {
                $home++;
            } elseif (($set['away'] ?? 0) > ($set['home'] ?? 0)) {
                $away++;
            }
        }

        return ['home' => $home, 'away' => $away];
    }

    public function isSetComplete(): bool
    {
        // Set penentu (set ke-3 di best of 3, set ke-5 di best of 5) dimainkan sampai 15.
        $target = count($this->set_scores ?? []) === $this->bestOf() - 1 ? 15 : 25;

        return max($this->home_score, $this->away_score) >= $target
            && abs($this->home_score - $this->away_score) >= 2;
    }

    /**
     * Lama timeout resmi dalam detik per cabang olahraga.
     */
    public function timeoutDuration(): int
    {
        return $this->sport === 'futsal' ? 60 : 30;
    }

    /**
     * Timeout yang sedang berjalan, diambil dari event audit timeout terakhir.
     *
     * @return array{side: string, remaining: int, duration: int}|null
     */
    public function activeTimeout(): ?array
    {
        $event = MatchEvent::where('match_id', $this->id)
            ->whereIn('type', ['timeout_used', 'timeout_corrected'])
            ->latest('id')
            ->first();

        if (! $event || $event->type !== 'timeout_used') {
            return null;
        }

        $duration = $this->timeoutDuration();
        $remaining = $duration - (int) $event->created_at->diffInSeconds(now());

        if ($remaining <= 0) {
            return null;
        }

        $side = collect(['home', 'away'])->first(
            fn (string $side): bool => ($event->after[$side]['timeouts'] ?? 0) > ($event->before[$side]['timeouts'] ?? 0)
        );

        if (! $side) {
            return null;
        }

        // Posisi tim ditukar setelah timeout dimulai: sisinya ikut ditukar.
        $swaps = MatchEvent::where('match_id', $this->id)
            ->where('type', 'sides_swapped')
            ->where('id', '>', $event->id)
            ->count();
        if ($swaps % 2 === 1) {
            $side = $side === 'home' ? 'away' : 'home';
        }

        return ['side' => $side, 'remaining' => $remaining, 'duration' => $duration];
    }

    /**
     * Tukar posisi tim home dan away (nama, logo, warna, skor, timeout, servis, riwayat set).
     */
    public function swapSides(): void
    {
        foreach (['name', 'sub', 'logo', 'score', 'color', 'timeouts'] as $field) {
            [$this->{'home_'.$field}, $this->{'away_'.$field}] = [$this->{'away_'.$field}, $this->{'home_'.$field}];
        }

        $this->serving = match ($this->serving) {
            'home' => 'away',
            'away' => 'home',
            default => $this->serving,
        };

        $this->set_scores = array_map(
            fn (array $set): array => ['home' => $set['away'] ?? 0, 'away' => $set['home'] ?? 0],
            $this->set_scores ?? [],
        );
    }

    /**
     * Statistik ringkas untuk scene hasil akhir (voli).
     *
     * @return array{totals: array{home: int, away: int}, longestRun: array{home: int, away: int}}
     */
    public function matchStats(): array
    {
        $sets = $this->set_scores ?? [];
        $totals = ['home' => 0, 'away' => 0];
        foreach ($sets as $set) {
            $totals['home'] += (int) ($set['home'] ?? 0);
            $totals['away'] += (int) ($set['away'] ?? 0);
        }

        // Set terakhir sudah masuk set_scores saat pertandingan selesai.
        if (! $this->is_finished) {
            $totals['home'] += $this->home_score;
            $totals['away'] += $this->away_score;
        }

        return ['totals' => $totals, 'longestRun' => $this->longestRuns()];
    }

    /**
     * Lama pertandingan dalam menit, dari poin pertama sampai poin terakhir / selesai.
     */
    public function durationMinutes(): ?int
    {
        $events = MatchEvent::where('match_id', $this->id)
            ->whereIn('type', ['point_awarded', 'match_finished'])
            ->selectRaw('MIN(created_at) as started, MAX(created_at) as ended')
            ->first();

        if (! $events?->started || ! $events->ended) {
            return null;
        }

        return (int) round(Carbon::parse($events->started)->diffInSeconds(Carbon::parse($events->ended)) / 60);
    }

    /**
     * Poin beruntun terpanjang tiap tim, dihitung dari urutan event poin.
     *
     * @return array{home: int, away: int}
     */
    private function longestRuns(): array
    {
        $sequence = [];
        $events = MatchEvent::where('match_id', $this->id)
            ->whereIn('type', ['point_awarded', 'score_corrected', 'sides_swapped'])
            ->orderBy('id')
            ->get(['type', 'before', 'after']);

        foreach ($events as $event) {
            // Posisi ditukar: semua poin sebelumnya ikut pindah sisi.
            if ($event->type === 'sides_swapped') {
                $sequence = array_map(fn (string $side): string => $side === 'home' ? 'away' : 'home', $sequence);

                continue;
            }

            $side = $this->scoringSide($event->before ?? [], $event->after ?? [], $event->type === 'point_awarded');

            if (! $side) {
                continue;
            }

            if ($event->type === 'point_awarded') {
                $sequence[] = $side;

                continue;
            }

            // Koreksi skor: batalkan poin terakhir milik tim tersebut.
            $index = array_search($side, array_reverse($sequence, true), true);
            if ($index !== false) {
                unset($sequence[$index]);
                $sequence = array_values($sequence);
            }
        }

        $runs = ['home' => 0, 'away' => 0];
        $current = null;
        $length = 0;
        foreach ($sequence as $side) {
            $length = $side === $current ? $length + 1 : 1;
            $current = $side;
            $runs[$side] = max($runs[$side], $length);
        }

        return $runs;
    }

    /**
     * Tim yang skornya naik (poin) atau turun (koreksi) pada satu event.
     */
    private function scoringSide(array $before, array $after, bool $increase): ?string
    {
        $setsBefore = count($before['sets'] ?? []);
        $setsAfter = count($after['sets'] ?? []);

        // Poin penentu set: skor direset, jadi lihat pemenang set yang baru tercatat.
        if ($increase && $setsAfter > $setsBefore) {
            $last = end($after['sets']);

            return ($last['home'] ?? 0) >= ($last['away'] ?? 0) ? 'home' : 'away';
        }

        foreach (['home', 'away'] as $side) {
            $delta = ($after[$side]['score'] ?? 0) - ($before[$side]['score'] ?? 0);
            if ($increase ? $delta > 0 : $delta < 0) {
                return $side;
            }
        }

        return null;
    }

    public function toOverlayArray(): array
    {
        $setsWon = $this->setsWon();

        return [
            'sport' => $this->sport,
            'bestOf' => $this->bestOf(),
            'logo' => (string) $this->logo,
            'home' => [
                'name' => $this->home_name,
                'sub' => (string) $this->home_sub,
                'logo' => (string) $this->home_logo,
                'score' => $this->home_score,
                'color' => $this->home_color,
                'setsWon' => $setsWon['home'],
                'timeouts' => $this->home_timeouts,
            ],
            'away' => [
                'name' => $this->away_name,
                'sub' => (string) $this->away_sub,
                'logo' => (string) $this->away_logo,
                'score' => $this->away_score,
                'color' => $this->away_color,
                'setsWon' => $setsWon['away'],
                'timeouts' => $this->away_timeouts,
            ],
            'period' => $this->period,
            'clock' => $this->clock,
            'status' => $this->status,
            'headline' => (string) $this->headline,
            'round' => (string) $this->round,
            'venue' => (string) $this->venue,
            'date' => $this->match_date?->toDateString() ?? '',
            'sets' => $this->set_scores ?? [],
            'currentSet' => $this->current_set,
            'serving' => $this->serving,
            'theme' => [
                'primary' => $this->theme_primary ?? '#001c54',
                'accent' => $this->theme_accent ?? '#c00000',
            ],
            'isFinished' => $this->is_finished,
        ];
    }
}
