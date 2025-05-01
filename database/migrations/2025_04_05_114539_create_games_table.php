<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status');
            $table->integer('current_challenge_id')->nullable();
            $table->foreignId('game_template_id')->constrained('game_templates');
            $table->integer('code');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_public')->default(false);
            $table->boolean('requires_admin_approval_to_join')->default(false);
            $table->timestamps();
        });
    }
};
