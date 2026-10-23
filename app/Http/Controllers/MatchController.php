<?php

namespace App\Http\Controllers;

use App\Models\Pertandingan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    public function current()
    {
        $match = Pertandingan::active()->first();

        if (!$match) {
            return response()->json(null, 404);
        }

        return response()->json($match->toOverlayArray());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'sport' => 'required|in:volley,futsal',
            'home_name' => 'required|string|max:50',
            'away_name' => 'required|string|max:50',
            'home_sub' => 'nullable|string|max:50',
            'away_sub' => 'nullable|string|max:50',
            'home_score' => 'sometimes|integer|min:0|max:999',
            'away_score' => 'sometimes|integer|min:0|max:999',
            'period' => 'required|string|max:20',
            'clock' => 'nullable|string|max:10',
            'status' => 'nullable|string|max:20',
            'home_color' => 'nullable|string|max:7',
            'away_color' => 'nullable|string|max:7',
            'accent' => 'nullable|string|max:7',
            'pos' => 'required|in:bottom,top',
            'set_scores_text' => 'nullable|string|max:100',
            'current_set' => 'required|integer|min:1|max:9',
            'serving' => 'sometimes|nullable|in:home,away,',
            'home_timeouts' => 'sometimes|integer|min:0|max:2',
            'away_timeouts' => 'sometimes|integer|min:0|max:2',
        ]);

        $data['set_scores'] = $this->parseSetScores($request->input('set_scores_text'));
        unset($data['set_scores_text']);

        // Kolom NOT NULL default '' tidak boleh menerima null (middleware mengubah '' -> null)
        foreach (['clock', 'status', 'serving', 'home_sub', 'away_sub'] as $nullableText) {
            if (array_key_exists($nullableText, $data) && $data[$nullableText] === null) {
                $data[$nullableText] = '';
            }
        }

        $match = Pertandingan::active()->first() ?? new Pertandingan(['is_active' => true]);
        $match->fill($data);
        $match->save();

        return back()->with('status', 'Pertandingan diperbarui.');
    }

    public function adjustScore(Request $request)
    {
        $data = $request->validate([
            'side' => 'required|in:home,away',
            'delta' => 'required|integer|between:-10,10',
        ]);

        $match = DB::transaction(function () use ($data) {
            $match = Pertandingan::active()->lockForUpdate()->first();

            if (!$match) {
                return null;
            }

            $column = $data['side'] . '_score';
            $match->{$column} = max(0, $match->{$column} + $data['delta']);

            // Skor bertambah => serve pindah ke tim tsb (perilaku voli)
            if ($data['delta'] > 0 && $match->sport === 'volley') {
                $match->serving = $data['side'];
            }

            $match->save();

            return $match;
        });

        if (!$match) {
            return response()->json(['error' => 'Tidak ada pertandingan aktif'], 404);
        }

        return response()->json($match->toOverlayArray());
    }

    public function setServe(Request $request)
    {
        $data = $request->validate([
            'serving' => 'required|in:home,away,',
        ]);

        $match = Pertandingan::active()->first();

        if (!$match) {
            return response()->json(['error' => 'Tidak ada pertandingan aktif'], 404);
        }

        $match->serving = $data['serving'];
        $match->save();

        return response()->json($match->toOverlayArray());
    }

    public function adjustTimeout(Request $request)
    {
        $data = $request->validate([
            'side' => 'required|in:home,away',
            'delta' => 'required|integer|between:-1,1',
        ]);

        $match = DB::transaction(function () use ($data) {
            $match = Pertandingan::active()->lockForUpdate()->first();

            if (!$match) {
                return null;
            }

            $column = $data['side'] . '_timeouts';
            $match->{$column} = max(0, min(2, $match->{$column} + $data['delta']));
            $match->save();

            return $match;
        });

        if (!$match) {
            return response()->json(['error' => 'Tidak ada pertandingan aktif'], 404);
        }

        return response()->json($match->toOverlayArray());
    }

    public function winSet(Request $request)
    {
        $data = $request->validate([
            'side' => 'required|in:home,away',
            'delta' => 'sometimes|integer|in:-1,1',
        ]);

        $match = DB::transaction(function () use ($data) {
            $match = Pertandingan::active()->lockForUpdate()->first();

            if (!$match) {
                return null;
            }

            $side = $data['side'];
            $sets = $match->set_scores ?? [];

            // minus: batalkan set terakhir yang dimenangi tim ini
            if (($data['delta'] ?? 1) === -1) {
                foreach (array_reverse(array_keys($sets)) as $i) {
                    $won = $side === 'home'
                        ? $sets[$i]['home'] > $sets[$i]['away']
                        : $sets[$i]['away'] > $sets[$i]['home'];
                    if ($won) {
                        $last = $sets[$i];
                        unset($sets[$i]);
                        $match->set_scores = array_values($sets);
                        $match->home_score = $last['home'];
                        $match->away_score = $last['away'];
                        $match->current_set = max(1, $match->current_set - 1);
                        break;
                    }
                }
                $match->save();

                return $match;
            }

            // catat skor set berjalan; jika tidak menang, bikin menang agar setsWon konsisten
            $set = ['home' => $match->home_score, 'away' => $match->away_score];
            $other = $side === 'home' ? 'away' : 'home';
            if ($set[$side] <= $set[$other]) {
                $set[$side] = $set[$other] + 1;
            }
            $match->set_scores = collect(array_merge($sets, [$set]))
                ->take(5)
                ->values()
                ->all();

            $match->home_score = 0;
            $match->away_score = 0;
            $match->home_timeouts = 0;
            $match->away_timeouts = 0;
            $match->current_set = min(9, $match->current_set + 1);
            $match->save();

            return $match;
        });

        if (!$match) {
            return response()->json(['error' => 'Tidak ada pertandingan aktif'], 404);
        }

        return response()->json($match->toOverlayArray());
    }

    private function parseSetScores(?string $raw): array
    {
        if (!$raw) {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(function ($pair) {
                $parts = array_map('trim', explode('-', $pair, 2));
                if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
                    return null;
                }
                return [
                    'home' => (int) $parts[0],
                    'away' => (int) $parts[1],
                ];
            })
            ->filter()
            ->take(5)
            ->values()
            ->all();
    }
}
