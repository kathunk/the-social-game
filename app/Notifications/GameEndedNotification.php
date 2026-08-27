<?php

namespace App\Notifications;

use App\Models\Game;
use App\Models\Player;
use App\Notifications\Concerns\ChecksPlayerChannelPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GameEndedNotification extends Notification implements ShouldQueue
{
    use ChecksPlayerChannelPreferences, Queueable;

    public function __construct(
        public Game $game,
        public ?Player $player = null
    ) {}

    public function headline(): string
    {
        return "🏆 {$this->game->name} has ended!";
    }

    public function body(): string
    {
        return "The game has concluded! Check out the final results and see how you did.";
    }

    public function url(): string
    {
        return route('game-dashboard', ['game' => $this->game]);
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->wantsVia($notifiable, 'notify_via_email')) {
            $channels[] = 'mail';
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
            ->subject($this->headline())
            ->line($this->body())
            ->action('View Game', $this->url())
            ->markdown('emails.game-ended', [
                'game' => $this->game,
            ]);
    }

    public function toDiscord(object $notifiable): array
    {
        return [
            'content' => "**{$this->headline()}**",
            'embeds' => [
                [
                    'title' => $this->game->name,
                    'description' => $this->body(),
                    'url' => $this->game->url,
                    'color' => 0xFFD700,
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        return "<b>{$this->headline()}</b>\n\n".
               "{$this->body()}\n\n".
               "View game: {$this->game->url}";
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => $this->headline(),
            'body' => $this->body(),
            'icon' => '/favicon.ico',
            'url' => $this->game->url,
        ];
    }
}
