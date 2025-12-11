<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toDiscord')) {
            return;
        }

        $webhookUrl = $notifiable->default_discord_webhook;

        if (! $webhookUrl) {
            Log::warning('Discord notification attempted but no webhook URL configured', [
                'user_id' => $notifiable->id,
            ]);

            return;
        }

        $message = $notification->toDiscord($notifiable);

        try {
            Log::info('Sending Discord notification', [
                'user_id' => $notifiable->id,
                'webhook_url' => $webhookUrl,
            ]);

            $response = Http::post($webhookUrl, $message);

            if ($response->successful()) {
                Log::info('Discord notification sent successfully', [
                    'user_id' => $notifiable->id,
                ]);
            } else {
                Log::error('Discord notification failed', [
                    'user_id' => $notifiable->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Discord notification exception', [
                'user_id' => $notifiable->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
