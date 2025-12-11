<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toDiscord')) {
            return;
        }

        $webhookUrl = $notifiable->default_discord_webhook;

        if (! $webhookUrl) {
            return;
        }

        $message = $notification->toDiscord($notifiable);

        try {
            $response = Http::post($webhookUrl, $message);
        } catch (\Exception $e) {
            Log::error('Discord notification failed', [
                'user_id' => $notifiable->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
