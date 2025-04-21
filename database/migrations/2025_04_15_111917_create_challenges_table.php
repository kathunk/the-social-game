<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained();
            $table->string('class_key');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->json('challenge_data');
            $table->string('status')->default('upcoming');
            $table->timestamps();
        });
    }
};
