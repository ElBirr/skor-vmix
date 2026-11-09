<?php

namespace App\Http\Controllers;

use App\Models\LowerThird;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OverlayController extends Controller
{
    private const CACHE_KEY = 'overlay.state';

    /**
     * Status tampil/sembunyi tiap scene yang diatur operator dari halaman skor.
     *
     * @return array{live: bool, start: bool, end: bool, serveCue: int}
     */
    public static function state(): array
    {
        return Cache::get(self::CACHE_KEY, []) + [
            'live' => true,
            'start' => true,
            'end' => true,
            'serveCue' => 0,
        ];
    }

    public function updateVisibility(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scene' => ['required', 'in:live,start,end'],
            'visible' => ['required', 'boolean'],
        ]);

        return $this->save([$data['scene'] => (bool) $data['visible']]);
    }

    /**
     * Minta overlay menampilkan lagi label SERVIS.
     */
    public function cueServe(): JsonResponse
    {
        return $this->save(['serveCue' => now()->getTimestampMs()]);
    }

    /**
     * Tutup semua grafis overlay sekaligus (dipakai saat pertandingan selesai).
     */
    public static function hideAll(): void
    {
        self::store(['live' => false, 'start' => false, 'end' => false]);
        LowerThird::where('is_live', true)->update(['is_live' => false]);
    }

    /**
     * @param  array<string, bool|int>  $changes
     */
    private function save(array $changes): JsonResponse
    {
        return response()->json(self::store($changes));
    }

    /**
     * @param  array<string, bool|int>  $changes
     * @return array{live: bool, start: bool, end: bool, serveCue: int}
     */
    private static function store(array $changes): array
    {
        $state = array_merge(self::state(), $changes);
        Cache::forever(self::CACHE_KEY, $state);

        return $state;
    }
}
