<?php

namespace App\Http\Controllers;

use App\Models\MatchEvent;
use App\Models\Pertandingan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchController extends Controller
{
    public function current()
    {
        $match = Pertandingan::active()->first();

        if (! $match) {
            return response()->json(null, 404);
        }

        return response()->json($match->toOverlayArray());
    }

    public function update(Request $request)
    {
        $data = $this->settingsData($request);

        $match = DB::transaction(function () use ($data, $request) {
            $match = Pertandingan::active()->lockForUpdate()->first();
            $before = $match?->toOverlayArray() ?? [];

            $match ??= Pertandingan::newMatch();
            $match->fill($data);
            $match->save();

            $this->audit($match, $request, $before, $match->toOverlayArray(), 'settings_updated');

            return $match;
        });

        return back()->with('status', 'Pengaturan pertandingan diperbarui.');
    }

    public function finish(Request $request)
    {
        DB::transaction(function () use ($request) {
            $match = Pertandingan::active()->lockForUpdate()->firstOrFail();
            $before = $match->toOverlayArray();

            $match->is_finished = true;
            $match->is_active = false;
            $match->status = 'SELESAI';
            $match->save();

            $this->audit($match, $request, $before, $match->toOverlayArray(), 'match_finished');
        });

        return redirect()->route('settings')->with('status', 'Pertandingan disimpan ke riwayat. Siapkan pertandingan berikutnya.');
    }

    public function adjustScore(Request $request)
    {
        $data = $request->validate([
            'side' => 'required|in:home,away',
            'delta' => 'required|integer|in:-1,1',
            'reason' => 'required_if:delta,-1|nullable|string|max:100',
        ]);

        $match = DB::transaction(function () use ($data, $request) {
            $match = Pertandingan::active()->lockForUpdate()->first();

            if (! $match) {
                return null;
            }

            $this->ensureOpen($match);
            $before = $match->toOverlayArray();
            $column = $data['side'].'_score';
            $match->{$column} = max(0, $match->{$column} + $data['delta']);

            if ($data['delta'] > 0 && $match->sport === 'volley') {
                $match->serving = $data['side'];
                $this->completeSetWhenWon($match);
            }

            $match->save();
            $this->audit(
                $match,
                $request,
                $before,
                $match->toOverlayArray(),
                $data['delta'] > 0 ? 'point_awarded' : 'score_corrected',
                $data['reason'] ?? null,
            );

            return $match;
        });

        if (! $match) {
            return response()->json(['error' => 'Tidak ada pertandingan aktif'], 404);
        }

        return response()->json($match->toOverlayArray());
    }

    public function setServe(Request $request)
    {
        $data = $request->validate(['serving' => 'required|in:home,away,']);

        return $this->change($request, 'serve_changed', function (Pertandingan $match) use ($data): void {
            $match->serving = $data['serving'];
        });
    }

    public function adjustTimeout(Request $request)
    {
        $data = $request->validate([
            'side' => 'required|in:home,away',
            'delta' => 'required|integer|in:-1,1',
            'reason' => 'required_if:delta,-1|nullable|string|max:100',
        ]);

        return $this->change($request, $data['delta'] > 0 ? 'timeout_used' : 'timeout_corrected', function (Pertandingan $match) use ($data): void {
            $column = $data['side'].'_timeouts';
            $match->{$column} = max(0, min(2, $match->{$column} + $data['delta']));
        }, $data['reason'] ?? null);
    }

    private function change(Request $request, string $type, callable $change, ?string $reason = null)
    {
        $match = DB::transaction(function () use ($request, $type, $change, $reason) {
            $match = Pertandingan::active()->lockForUpdate()->first();

            if (! $match) {
                return null;
            }

            $this->ensureOpen($match);
            $before = $match->toOverlayArray();
            $change($match);
            $match->save();
            $this->audit($match, $request, $before, $match->toOverlayArray(), $type, $reason);

            return $match;
        });

        if (! $match) {
            return response()->json(['error' => 'Tidak ada pertandingan aktif'], 404);
        }

        return response()->json($match->toOverlayArray());
    }

    private function completeSetWhenWon(Pertandingan $match): void
    {
        if (! $match->isSetComplete()) {
            return;
        }

        $sets = $match->set_scores ?? [];
        $sets[] = ['home' => $match->home_score, 'away' => $match->away_score];
        $match->set_scores = $sets;

        $setsWon = $match->setsWon();
        if (max($setsWon) === 3) {
            $match->is_finished = true;
            $match->status = 'SELESAI';

            return;
        }

        $match->home_score = 0;
        $match->away_score = 0;
        $match->home_timeouts = 0;
        $match->away_timeouts = 0;
        $match->serving = '';
        $match->current_set = count($sets) + 1;
    }

    private function ensureOpen(Pertandingan $match): void
    {
        if ($match->is_finished) {
            throw ValidationException::withMessages([
                'match' => 'Pertandingan sudah selesai. Mulai pertandingan baru untuk mengubah skor.',
            ]);
        }
    }

    private function settingsData(Request $request): array
    {
        $data = $request->validate([
            'sport' => 'required|in:volley,futsal',
            'home_name' => 'required|string|max:50',
            'away_name' => 'required|string|max:50',
            'home_sub' => 'nullable|string|max:50',
            'away_sub' => 'nullable|string|max:50',
            'period' => 'required|string|max:20',
            'clock' => 'nullable|string|max:10',
            'status' => 'nullable|string|max:20',
            'home_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'away_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'pos' => 'required|in:bottom,top',
        ]);

        foreach (['clock', 'status', 'home_sub', 'away_sub'] as $field) {
            if (($data[$field] ?? null) === null) {
                $data[$field] = '';
            }
        }

        return $data;
    }

    private function audit(Pertandingan $match, Request $request, array $before, array $after, string $type, ?string $reason = null): void
    {
        MatchEvent::create([
            'match_id' => $match->id,
            'user_id' => $request->user()->id,
            'type' => $type,
            'reason' => $reason,
            'before' => $before,
            'after' => $after,
            'ip_address' => $request->ip(),
        ]);
    }
}
