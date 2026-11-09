<?php

namespace App\Http\Controllers;

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
     * @param  array<string, bool|int>  $changes
     */
    private function save(array $changes): JsonResponse
    {
        $state = array_merge(self::state(), $changes);
        Cache::forever(self::CACHE_KEY, $state);

        return response()->json($state);
    }
}
