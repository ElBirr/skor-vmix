<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LowerThirdController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\OverlayController;
use App\Models\MatchEvent;
use App\Models\Pertandingan;
use Illuminate\Http\Request;
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

    Route::get('/history', function (Request $request) {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:50'],
            'sport' => ['nullable', 'in:volley,futsal'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $matches = Pertandingan::query()->where('is_finished', true)
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(
                fn ($inner) => $inner->where('home_name', 'like', "%{$q}%")->orWhere('away_name', 'like', "%{$q}%")
            ))
            ->when($filters['sport'] ?? null, fn ($query, $sport) => $query->where('sport', $sport))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('updated_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('updated_at', '<=', $to))
            ->latest('updated_at')
            ->paginate(50)
            ->withQueryString();

        return view('history.index', [
            'matches' => $matches,
            'filters' => $filters,
        ]);
    })->name('history');

    Route::get('/history/{match}', function (Pertandingan $match) {
        abort_unless($match->is_finished, 404);

        return view('history.show', [
            'match' => $match,
            'events' => MatchEvent::with('user')->where('match_id', $match->id)->latest()->paginate(100),
        ]);
    })->name('history.show');

    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');

    Route::post('/match', [MatchController::class, 'update'])->name('match.update');
    Route::post('/match/finish', [MatchController::class, 'finish'])->name('match.finish');
    Route::post('/match/score', [MatchController::class, 'adjustScore'])->name('match.score');
    Route::post('/match/serve', [MatchController::class, 'setServe'])->name('match.serve');
    Route::post('/match/timeout', [MatchController::class, 'adjustTimeout'])->name('match.timeout');
    Route::post('/overlay/visibility', [OverlayController::class, 'updateVisibility'])->name('overlay.visibility');
    Route::post('/overlay/serve-cue', [OverlayController::class, 'cueServe'])->name('overlay.serve-cue');

    Route::get('/lower-thirds', [LowerThirdController::class, 'index'])->name('lower-thirds.index');
    Route::post('/lower-thirds', [LowerThirdController::class, 'store'])->name('lower-thirds.store');
    Route::post('/lower-thirds/hide', [LowerThirdController::class, 'hide'])->name('lower-thirds.hide');
    Route::post('/lower-thirds/{lowerThird}/show', [LowerThirdController::class, 'show'])->name('lower-thirds.show');
    Route::delete('/lower-thirds/{lowerThird}', [LowerThirdController::class, 'destroy'])->name('lower-thirds.destroy');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/admin', DashboardController::class)->name('admin.dashboard');
        Route::get('/admin/data', [DataController::class, 'index'])->name('admin.data');
        Route::get('/admin/data/{table}', [DataController::class, 'show'])->name('admin.data.show');
        Route::get('/admin/data/{table}/export', [DataController::class, 'export'])->name('admin.data.export');

        Route::get('/admin/backup', BackupController::class)->name('admin.backup');

        Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users');
        Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::put('/admin/users/{user}/password', [UserController::class, 'resetPassword'])->name('admin.users.password');

        Route::get('/admin/system', [SystemController::class, 'index'])->name('admin.system');
        Route::post('/admin/system/logos', [SystemController::class, 'cleanupLogos'])->name('admin.system.logos');

        Route::delete('/admin/matches/{match}', [MatchController::class, 'destroy'])->name('admin.matches.destroy');
    });
});

Route::get('/overlay', function () {
    return view('overlay');
})->name('overlay');

Route::get('/api/match', [MatchController::class, 'current'])->name('api.match');
Route::get('/api/lower-third', [LowerThirdController::class, 'current'])->name('api.lower-third');
