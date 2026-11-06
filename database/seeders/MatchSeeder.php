<?php

namespace Database\Seeders;

use App\Models\Pertandingan;
use Illuminate\Database\Seeder;

class MatchSeeder extends Seeder
{
    public function run(): void
    {
        if (Pertandingan::exists()) {
            return;
        }

        Pertandingan::create([
            'sport' => 'volley',
            'home_name' => 'Garuda',
            'away_name' => 'Merdeka',
            'home_sub' => 'Coach Andi',
            'away_sub' => 'Coach Budi',
            'home_score' => 2,
            'away_score' => 1,
            'period' => 'SET 4',
            'clock' => '',
            'status' => 'LIVE',
            'home_color' => '#2563eb',
            'away_color' => '#dc2626',
            'set_scores' => [
                ['home' => 25, 'away' => 23],
                ['home' => 23, 'away' => 25],
                ['home' => 25, 'away' => 20],
            ],
            'current_set' => 4,
            'serving' => 'home',
            'home_timeouts' => 1,
            'away_timeouts' => 2,
            'is_active' => true,
        ]);
    }
}
