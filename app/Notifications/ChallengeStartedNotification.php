<?php

namespace App\Notifications;

use App\Models\Challenge;
use App\Models\Game;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChallengeStartedNotification extends Notification
{
    public function __construct(
        public Challenge $challenge,
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
        $isFinalChallenge = $this->isFinalChallenge();

        if ($isFinalChallenge) {
            return (new MailMessage)
                ->subject("Final Round: {$this->game->name}")
                ->markdown('emails.challenge-started', [
                    'challenge' => $this->challenge,
                    'game' => $this->game,
                    'isFinalChallenge' => true,
                ]);
        }

        return (new MailMessage)
            ->subject("New Challenge Started: {$this->game->name}")
            ->markdown('emails.challenge-started', [
                'challenge' => $this->challenge,
                'game' => $this->game,
                'isFinalChallenge' => false,
            ]);
    }

    public function toDiscord(object $notifiable): array
    {
        $isFinalChallenge = $this->isFinalChallenge();

        if ($isFinalChallenge) {
            return [
                'content' => "🏁 **Final Round!**",
                'embeds' => [
                    [
                        'title' => $this->game->name,
                        'description' => "This is the final round: {$this->challenge->handler()::NAME}. The challenge ends {$this->challenge->ends_at->diffForHumans()}.",
                        'url' => $this->game->url,
                        'color' => 0xff0000, // Red color for final round
                        'timestamp' => now()->toIso8601String(),
                    ],
                ],
            ];
        }

        return [
            'content' => "**New Challenge Started: {$this->challenge->handler()::NAME}!**",
            'embeds' => [
                [
                    'title' => $this->game->name,
                    'description' => "A new challenge has begun: {$this->challenge->handler()::NAME}. The challenge ends {$this->challenge->ends_at->diffForHumans()}.",
                    'url' => $this->game->url,
                    'color' => 0x0000ff, // Blue color
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        $isFinalChallenge = $this->isFinalChallenge();

        if ($isFinalChallenge) {
            return "🏁 <b>Final Round!</b>\n\n" .
                   "{$this->game->name}\n\n" .
                   "This is the final round! Make your final moves and give it your all!\n\n" .
                   "View game: {$this->game->url}";
        }

        return "🎯 <b>New Challenge Started!</b>\n\n" .
               "{$this->game->name}\n\n" .
               "A new challenge has begun! Check it out and make your moves.\n\n" .
               "View game: {$this->game->url}";
    }

    public function toWebPush(object $notifiable): array
    {
        $isFinalChallenge = $this->isFinalChallenge();

        if ($isFinalChallenge) {
            return [
                'title' => 'Final Round!',
                'body' => "This is the final round of {$this->game->name}! Make your final moves and give it your all!",
                'icon' => '/favicon.ico',
                'url' => $this->game->url,
            ];
        }

        return [
            'title' => 'New Challenge Started!',
            'body' => "A new challenge has begun in {$this->game->name}! Check it out and make your moves.",
            'icon' => '/favicon.ico',
            'url' => $this->game->url,
        ];
    }

    private function isFinalChallenge(): bool
    {
        $totalChallenges = $this->game->challenges()->count();
        return $this->challenge->round_number === $totalChallenges;
    }
}
