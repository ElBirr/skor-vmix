<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MatchController;
use App\Models\MatchEvent;
use App\Models\Pertandingan;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'createFirstUser'])->name('register');
    Route::post('/register', [AuthController::class, 'storeFirstUser'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('scoreboard', ['match' => Pertandingan::active()->first()]);
    })->name('scoreboard');

    Route::get('/settings', function () {
        $match = Pertandingan::active()->first();

        return view('settings', [
            'match' => $match ?? Pertandingan::newMatch(),
            'isNewMatch' => ! $match,
            'events' => $match
                ? MatchEvent::with('user')->where('match_id', $match->id)->latest()->take(10)->get()
                : collect(),
        ]);
    })->name('settings');

    Route::get('/history', function () {
        return view('history.index', [
            'matches' => Pertandingan::where('is_finished', true)->latest('updated_at')->get(),
        ]);
    })->name('history');

    Route::get('/history/{match}', function (Pertandingan $match) {
        abort_unless($match->is_finished, 404);

        return view('history.show', [
            'match' => $match,
            'events' => MatchEvent::with('user')->where('match_id', $match->id)->latest()->get(),
        ]);
    })->name('history.show');

    Route::post('/match', [MatchController::class, 'update'])->name('match.update');
    Route::post('/match/finish', [MatchController::class, 'finish'])->name('match.finish');
    Route::post('/match/score', [MatchController::class, 'adjustScore'])->name('match.score');
    Route::post('/match/serve', [MatchController::class, 'setServe'])->name('match.serve');
    Route::post('/match/timeout', [MatchController::class, 'adjustTimeout'])->name('match.timeout');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});

Route::get('/overlay', function () {
    return view('overlay');
})->name('overlay');

Route::get('/api/match', [MatchController::class, 'current'])->name('api.match');
