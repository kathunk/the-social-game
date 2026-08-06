<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_modes', function (Blueprint $table) {
            $table->boolean('skips_pre_game_lobby')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('game_modes', function (Blueprint $table) {
            $table->dropColumn('skips_pre_game_lobby');
        });
    }
};
