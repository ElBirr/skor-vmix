<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('sport', 10)->default('volley'); // volley | futsal
            $table->string('home_name', 50)->default('HOME');
            $table->string('away_name', 50)->default('AWAY');
            $table->string('home_sub', 50)->nullable();
            $table->string('away_sub', 50)->nullable();
            $table->unsignedInteger('home_score')->default(0);
            $table->unsignedInteger('away_score')->default(0);
            $table->string('period', 20)->default(''); // SET 1 / 1H / 2H
            $table->string('clock', 10)->default('');
            $table->string('status', 20)->default('');
            $table->string('home_color', 7)->default('#2563eb');
            $table->string('away_color', 7)->default('#dc2626');
            $table->string('accent', 7)->default('#f5a623');
            $table->string('pos', 10)->default('bottom'); // bottom | top
            $table->json('set_scores')->nullable(); // voli: [{home:25,away:23}, ...]
            $table->unsignedTinyInteger('current_set')->default(1);
            $table->string('serving', 5)->default(''); // voli: home | away | ''
            $table->unsignedTinyInteger('home_timeouts')->default(0);
            $table->unsignedTinyInteger('away_timeouts')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
