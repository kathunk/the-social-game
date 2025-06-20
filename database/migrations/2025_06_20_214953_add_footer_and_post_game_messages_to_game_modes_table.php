<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_modes', function (Blueprint $table) {
            $table->text('footer_message')->nullable();
            $table->text('post_game_message')->nullable();
        });
    }
};
