<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatchEvent;
use App\Models\Pertandingan;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'matchCount' => Pertandingan::count(),
            'activeCount' => Pertandingan::where('is_active', true)->count(),
            'finishedCount' => Pertandingan::where('is_finished', true)->count(),
            'eventCount' => MatchEvent::count(),
            'userCount' => User::count(),
            'adminCount' => User::where('role', 'admin')->count(),
            'operatorCount' => User::where('role', 'operator')->count(),
        ]);
    }
}
