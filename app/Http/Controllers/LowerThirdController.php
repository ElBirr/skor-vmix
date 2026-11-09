<?php

namespace App\Http\Controllers;

use App\Models\LowerThird;
use App\Models\Pertandingan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LowerThirdController extends Controller
{
    public function index(): View
    {
        return view('lower-thirds.index', [
            'lowerThirds' => LowerThird::orderBy('title')->get(),
            'current' => LowerThird::current(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:60'],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'duration' => ['required', 'integer', 'min:0', 'max:600'],
            'logo' => ['nullable', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ]);

        // Disimpan terpisah dari logo pertandingan agar tidak ikut terhapus saat bersih-bersih logo.
        $data['logo'] = $request->hasFile('logo')
            ? '/storage/'.$request->file('logo')->store('lower-thirds', 'public')
            : null;

        LowerThird::create($data);

        return back()->with('status', 'Preset lower third ditambahkan.');
    }

    public function destroy(LowerThird $lowerThird): RedirectResponse
    {
        if ($lowerThird->logo) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $lowerThird->logo));
        }

        $lowerThird->delete();

        return back()->with('status', 'Preset lower third dihapus.');
    }

    public function show(LowerThird $lowerThird): RedirectResponse
    {
        DB::transaction(function () use ($lowerThird): void {
            LowerThird::where('is_live', true)->update(['is_live' => false]);
            $lowerThird->update(['is_live' => true, 'shown_at' => now()]);
        });

        return back()->with('status', "Lower third \"{$lowerThird->title}\" tayang.");
    }

    public function hide(): RedirectResponse
    {
        LowerThird::where('is_live', true)->update(['is_live' => false]);

        return back()->with('status', 'Lower third disembunyikan.');
    }

    public function current(): JsonResponse
    {
        $match = Pertandingan::latest('id')->first();

        return response()->json([
            'lowerThird' => LowerThird::current()?->toOverlayArray(),
            'theme' => [
                'primary' => $match->theme_primary ?? '#001c54',
                'accent' => $match->theme_accent ?? '#c00000',
            ],
        ]);
    }
}
