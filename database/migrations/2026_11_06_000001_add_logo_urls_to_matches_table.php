<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('logo', 2048)->nullable()->after('accent');
            $table->string('home_logo', 2048)->nullable()->after('home_sub');
            $table->string('away_logo', 2048)->nullable()->after('away_sub');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['logo', 'home_logo', 'away_logo']);
        });
    }
};
