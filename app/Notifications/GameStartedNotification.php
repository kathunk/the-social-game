<?php

namespace App\Notifications;

use App\Models\Game;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class GameStartedNotification extends Notification
{

    public function __construct(
        public Game $game
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotificationVia('notify_via_email')) {
            $channels[] = 'mail';
        }

        if ($notifiable->wantsNotificationVia('notify_via_sms') && $notifiable->phone_number) {
            $channels[] = 'sms';
        }

        if ($notifiable->wantsNotificationVia('notify_via_discord') && $notifiable->default_discord_webhook) {
            $channels[] = 'discord';
        }

        Log::info('GameStartedNotification channels determined', [
            'user_id' => $notifiable->id,
            'channels' => $channels,
            'preferences' => $notifiable->notification_preferences,
        ]);

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->game->name} has started!")
            ->markdown('emails.game-started', [
                'game' => $this->game,
            ]);
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return "Game Started: {$this->game->name}. View it here: {$this->game->url}";
    }

    /**
     * Get the Discord representation of the notification.
     */
    public function toDiscord(object $notifiable): array
    {
        return [
            'content' => "🎮 **Game Started!**",
            'embeds' => [
                [
                    'title' => $this->game->name,
                    'description' => "The game has started! Good luck and have fun!",
                    'url' => $this->game->url,
                    'color' => 0x00ff00, // Green color
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }
}
