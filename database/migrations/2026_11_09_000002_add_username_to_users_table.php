<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->after('name');
        });

        // Akun lama tetap bisa masuk: username diturunkan dari bagian depan email.
        DB::table('users')->orderBy('id')->each(function ($user): void {
            $base = Str::lower(Str::before((string) $user->email, '@'));
            $base = substr(preg_replace('/[^a-z0-9._-]/', '', $base) ?: 'operator', 0, 25);

            $username = $base;
            $suffix = 1;

            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base.++$suffix;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change(); // email tidak lagi wajib
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
