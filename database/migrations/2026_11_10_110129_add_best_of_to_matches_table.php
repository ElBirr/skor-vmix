<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pertandingan lama tetap best of 5 (aturan sebelumnya).
        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedTinyInteger('best_of')->default(5)->after('sport');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('best_of');
        });
    }
};
