<?php

use App\Http\Controllers\MatchController;
use App\Models\Pertandingan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('scoreboard', ['match' => Pertandingan::active()->first()]);
})->name('scoreboard');

Route::get('/settings', function () {
    return view('settings', ['match' => Pertandingan::active()->first()]);
})->name('settings');

Route::post('/match', [MatchController::class, 'update'])->name('match.update');
Route::post('/match/score', [MatchController::class, 'adjustScore'])->name('match.score');
Route::post('/match/serve', [MatchController::class, 'setServe'])->name('match.serve');
Route::post('/match/timeout', [MatchController::class, 'adjustTimeout'])->name('match.timeout');
Route::post('/match/set', [MatchController::class, 'winSet'])->name('match.set');

Route::get('/overlay', function () {
    return view('overlay');
})->name('overlay');

Route::get('/api/match', [MatchController::class, 'current'])->name('api.match');
