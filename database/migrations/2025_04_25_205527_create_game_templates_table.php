<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['individual', 'team']);
            $table->integer('min_players')->nullable();
            $table->integer('max_players')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('players_can_join_late')->default(false);
            $table->text('pre_game_lobby_message');
            $table->boolean('is_archived')->default(false);
            $table->text('description')->nullable();
            $table->json('team_names');
            $table->json('challenges');
            $table->timestamps();
        });
    }
};
