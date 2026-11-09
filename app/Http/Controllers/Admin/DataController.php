<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
    // Tabel yang boleh dilihat; kolom sensitif (password, token, sesi) tidak pernah masuk daftar.
    private const TABLES = [
        'matches' => [
            'id', 'sport', 'home_name', 'away_name', 'home_sub', 'away_sub',
            'home_logo', 'away_logo', 'home_score', 'away_score', 'period', 'clock',
            'status', 'home_color', 'away_color', 'logo', 'set_scores', 'current_set',
            'serving', 'home_timeouts', 'away_timeouts', 'is_active', 'is_finished',
            'created_at', 'updated_at',
        ],
        'match_events' => [
            'id', 'match_id', 'user_id', 'type', 'reason', 'before', 'after',
            'ip_address', 'created_at', 'updated_at',
        ],
        'users' => [
            'id', 'name', 'username', 'email', 'role', 'is_active', 'created_at', 'updated_at',
        ],
    ];

    public function index()
    {
        $counts = [];

        foreach (array_keys(self::TABLES) as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return view('admin.data.index', ['counts' => $counts]);
    }

    public function show(Request $request, string $table)
    {
        $columns = $this->columns($table);

        $rows = DB::table($table)
            ->select($columns)
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.data.show', [
            'table' => $table,
            'columns' => $columns,
            'rows' => $rows,
        ]);
    }

    public function export(string $table)
    {
        $columns = $this->columns($table);

        return response()->streamDownload(function () use ($table, $columns) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8 dengan benar
            fputcsv($handle, $columns);

            DB::table($table)->select($columns)->orderBy('id')->chunkById(500, function ($rows) use ($handle, $columns) {
                foreach ($rows as $row) {
                    fputcsv($handle, array_map(
                        fn ($column) => is_scalar($row->{$column}) ? (string) $row->{$column} : json_encode($row->{$column}),
                        $columns
                    ));
                }
            });

            fclose($handle);
        }, $table.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function columns(string $table): array
    {
        abort_unless(isset(self::TABLES[$table]), 404);

        return self::TABLES[$table];
    }
}
