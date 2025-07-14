<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modifier_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained();
            $table->json('modifier_data');
            $table->string('modifier_key');
            $table->timestamps();
        });
    }
};
