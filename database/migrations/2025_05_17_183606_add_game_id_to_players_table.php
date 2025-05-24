<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_game_id')->nullable()->constrained('games');
            $table->foreignId('current_player_id')->nullable()->constrained('players');
        });
    }
};
