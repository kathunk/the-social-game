<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('game_id')->constrained('games');
            $table->integer('score')->default(0);
            $table->integer('hidden_score')->default(0);
            $table->string('name');
            $table->string('status');
            $table->timestamp('last_switched_team_at')->nullable();
            $table->timestamps();
        });
    }
};
