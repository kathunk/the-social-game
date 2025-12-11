<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('default_discord_webhook');
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            $table->string('telegram_verification_token')->nullable()->after('telegram_username');
            $table->timestamp('telegram_connected_at')->nullable()->after('telegram_verification_token');
            $table->json('push_subscriptions')->nullable()->after('telegram_connected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_username',
                'telegram_verification_token',
                'telegram_connected_at',
                'push_subscriptions',
            ]);
        });
    }
};
