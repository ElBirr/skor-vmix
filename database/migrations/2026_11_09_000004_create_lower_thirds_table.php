<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lower_thirds', function (Blueprint $table) {
            $table->id();
            $table->string('title', 60);
            $table->string('subtitle', 120)->nullable();
            $table->string('logo', 2048)->nullable();
            $table->unsignedSmallInteger('duration')->default(0);
            $table->boolean('is_live')->default(false);
            $table->timestamp('shown_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lower_thirds');
    }
};
