<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pertandingan extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'sport',
        'home_name',
        'away_name',
        'home_sub',
        'away_sub',
        'home_score',
        'away_score',
        'period',
        'clock',
        'status',
        'home_color',
        'away_color',
        'accent',
        'pos',
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
        'home_timeouts' => 'integer',
        'away_timeouts' => 'integer',
        'is_active' => 'boolean',
        'is_finished' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function newMatch(): self
    {
        return new self([
            'is_active' => true,
            'is_finished' => false,
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
            'accent' => '#c00000',
            'home_score' => 0,
            'away_score' => 0,
            'current_set' => 1,
            'set_scores' => [],
            'serving' => '',
            'home_timeouts' => 0,
            'away_timeouts' => 0,
        ]);
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
        $target = count($this->set_scores ?? []) === 4 ? 15 : 25;

        return max($this->home_score, $this->away_score) >= $target
            && abs($this->home_score - $this->away_score) >= 2;
    }

    public function toOverlayArray(): array
    {
        $setsWon = $this->setsWon();

        return [
            'sport' => $this->sport,
            'pos' => $this->pos,
            'accent' => $this->accent,
            'home' => [
                'name' => $this->home_name,
                'sub' => (string) $this->home_sub,
                'score' => $this->home_score,
                'color' => $this->home_color,
                'setsWon' => $setsWon['home'],
                'timeouts' => $this->home_timeouts,
            ],
            'away' => [
                'name' => $this->away_name,
                'sub' => (string) $this->away_sub,
                'score' => $this->away_score,
                'color' => $this->away_color,
                'setsWon' => $setsWon['away'],
                'timeouts' => $this->away_timeouts,
            ],
            'period' => $this->period,
            'clock' => $this->clock,
            'status' => $this->status,
            'sets' => $this->set_scores ?? [],
            'currentSet' => $this->current_set,
            'serving' => $this->serving,
            'isFinished' => $this->is_finished,
        ];
    }
}
