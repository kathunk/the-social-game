<?php

namespace App\Notifications;

use App\Models\Game;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GameEndedNotification extends Notification
{
    public function __construct(
        public Game $game
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsNotificationVia('notify_via_email')) {
            $channels[] = 'mail';
        }

        if ($notifiable->wantsNotificationVia('notify_via_discord') && $notifiable->default_discord_webhook) {
            $channels[] = 'discord';
        }

        if ($notifiable->wantsNotificationVia('notify_via_telegram') && $notifiable->telegram_chat_id) {
            $channels[] = 'telegram';
        }

        if ($notifiable->wantsNotificationVia('notify_via_browser') && $notifiable->hasPushSubscriptions()) {
            $channels[] = 'webpush';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->game->name} has ended!")
            ->markdown('emails.game-ended', [
                'game' => $this->game,
            ]);
    }

    public function toDiscord(object $notifiable): array
    {
        return [
            'content' => '🏆 **Game Ended!**',
            'embeds' => [
                [
                    'title' => $this->game->name,
                    'description' => 'The game has ended! Check out the final results and see how you did!',
                    'url' => $this->game->url,
                    'color' => 0xFFD700,
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        return "🏆 <b>Game Ended!</b>\n\n".
               "{$this->game->name}\n\n".
               "The game has ended! Check out the final results and see how you did!\n\n".
               "View game: {$this->game->url}";
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => 'Game Ended!',
            'body' => "{$this->game->name} has ended! Check out the final results and see how you did!",
            'icon' => '/favicon.ico',
            'url' => $this->game->url,
        ];
    }
}
