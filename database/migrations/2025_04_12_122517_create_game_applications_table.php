<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('status', ['pending', 'accepted', 'rejected']);
            $table->foreignId('decided_by_id')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'user_id']);
        });
    }
};
