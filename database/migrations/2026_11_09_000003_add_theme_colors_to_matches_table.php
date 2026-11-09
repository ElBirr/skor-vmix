<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('theme_primary', 7)->default('#001c54')->after('away_color');
            $table->string('theme_accent', 7)->default('#c00000')->after('theme_primary');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['theme_primary', 'theme_accent']);
        });
    }
};
