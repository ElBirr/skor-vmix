<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pertandingan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemController extends Controller
{
    public function index()
    {
        $driver = DB::connection()->getDriverName();
        $databaseSize = null;

        if ($driver === 'sqlite') {
            $databaseSize = 0;
            foreach ([database_path('database.sqlite'), database_path('database.sqlite-wal'), database_path('database.sqlite-shm')] as $file) {
                if (is_file($file)) {
                    $databaseSize += filesize($file);
                }
            }
        }

        $logos = $this->logoStats();

        return view('admin.system', [
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
            'driver' => $driver,
            'databaseSize' => $databaseSize,
            'storageLinked' => file_exists(public_path('storage')),
            'storageWritable' => is_writable(storage_path()),
            'diskFree' => @disk_free_space(base_path()) ?: null,
            'logoCount' => $logos['count'],
            'logoSize' => $logos['size'],
            'orphanCount' => count($logos['orphans']),
            'orphanSize' => $logos['orphanSize'],
        ]);
    }

    public function cleanupLogos()
    {
        $deleted = 0;

        foreach ($this->logoStats()['orphans'] as $file) {
            Storage::disk('public')->delete($file);
            $deleted++;
        }

        return back()->with('status', $deleted.' file logo tidak terpakai dihapus.');
    }

    private function logoStats(): array
    {
        $referenced = Pertandingan::query()
            ->get(['logo', 'home_logo', 'away_logo'])
            ->flatMap(fn ($match) => [$match->logo, $match->home_logo, $match->away_logo])
            ->filter()
            ->map(fn ($path) => basename($path))
            ->unique()
            ->values()
            ->all();

        $files = Storage::disk('public')->files('logos');
        $size = 0;
        $orphans = [];
        $orphanSize = 0;

        foreach ($files as $file) {
            $bytes = Storage::disk('public')->size($file);
            $size += $bytes;

            if (! in_array(basename($file), $referenced, true)) {
                $orphans[] = $file;
                $orphanSize += $bytes;
            }
        }

        return ['count' => count($files), 'size' => $size, 'orphans' => $orphans, 'orphanSize' => $orphanSize];
    }
}
