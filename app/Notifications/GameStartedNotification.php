<?php

namespace App\Notifications;

use App\Models\Game;
use App\Models\Player;
use App\Notifications\Concerns\ChecksPlayerChannelPreferences;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GameStartedNotification extends Notification
{
    use ChecksPlayerChannelPreferences;

    public function __construct(
        public Game $game,
        public ?Player $player = null
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->wantsVia($notifiable, 'notify_via_email')) {
            $channels[] = 'mail';
        }

        if ($this->wantsVia($notifiable, 'notify_via_sms') && $notifiable->phone_number) {
            $channels[] = 'sms';
        }

        if ($this->wantsVia($notifiable, 'notify_via_discord') && $notifiable->default_discord_webhook) {
            $channels[] = 'discord';
        }

        if ($this->wantsVia($notifiable, 'notify_via_telegram') && $notifiable->telegram_chat_id) {
            $channels[] = 'telegram';
        }

        if ($this->wantsVia($notifiable, 'notify_via_browser') && $notifiable->hasPushSubscriptions()) {
            $channels[] = 'webpush';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->game->name} is afoot!")
            ->markdown('emails.game-started', [
                'game' => $this->game,
            ]);
    }

    public function toSms(object $notifiable): string
    {
        return "{$this->game->name} is afoot! Make your first move. Good luck 😈";
    }

    public function toDiscord(object $notifiable): array
    {
        return [
            'content' => "**{$this->game->name} is afoot!**",
            'embeds' => [
                [
                    'title' => $this->game->name,
                    'description' => 'Make your first move. Good luck 😈',
                    'url' => $this->game->url,
                    'color' => 0x00FF00,
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        return "<b>{$this->game->name} is afoot!</b>\n\n".
               "Make your first move. Good luck 😈\n\n".
               "View game: {$this->game->url}";
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => "{$this->game->name} is afoot!",
            'body' => 'Make your first move. Good luck 😈',
            'icon' => '/favicon.ico',
            'url' => $this->game->url,
        ];
    }
}
