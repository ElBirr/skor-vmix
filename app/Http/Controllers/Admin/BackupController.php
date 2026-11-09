<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    public function __invoke()
    {
        abort_unless(DB::connection()->getDriverName() === 'sqlite', 501, 'Backup hanya tersedia untuk database SQLite.');

        $path = tempnam(sys_get_temp_dir(), 'skor-backup-');
        @unlink($path); // VACUUM INTO menolak file yang sudah ada

        DB::statement('VACUUM INTO '.DB::connection()->getPdo()->quote($path));

        return response()
            ->download($path, 'skor-vmix-'.now()->format('Ymd-His').'.sqlite')
            ->deleteFileAfterSend(true);
    }
}
