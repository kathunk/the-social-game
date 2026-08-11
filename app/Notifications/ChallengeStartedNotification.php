<?php

namespace App\Notifications;

use App\Models\Challenge;
use App\Models\Game;
use App\Models\Player;
use App\Notifications\Concerns\ChecksPlayerChannelPreferences;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChallengeStartedNotification extends Notification
{
    use ChecksPlayerChannelPreferences;

    public function __construct(
        public Challenge $challenge,
        public Game $game,
        public ?Player $player = null
    ) {}

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
                'content' => "🏁 **Final Round: {$this->game->name}**",
                'embeds' => [
                    [
                        'title' => $this->game->name,
                        'description' => "This is the final round!\n\nThe challenge ends {$this->challenge->ends_at->diffForHumans()}.",
                        'url' => $this->game->url,
                        'color' => 0xff0000,
                        'timestamp' => now()->toIso8601String(),
                    ],
                ],
            ];
        }

        $challengeName = $this->challenge->handler()::NAME;

        return [
            'content' => "**A new challenge has begun!**",
            'embeds' => [
                [
                    'title' => $this->game->name,
                    'description' => "The next challenge starts now: {$challengeName}. The challenge ends {$this->challenge->ends_at->diffForHumans()}.",
                    'url' => $this->game->url,
                    'color' => 0x0000ff,
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        $isFinalChallenge = $this->isFinalChallenge();

        if ($isFinalChallenge) {
            return "🏁 <b>Final Round: {$this->game->name}</b>\n\n" .
                   "This is the final round!\n\n" .
                   "The challenge ends {$this->challenge->ends_at->diffForHumans()}.\n\n" .
                   "View game: {$this->game->url}";
        }

        $challengeName = $this->challenge->handler()::NAME;

        return "<b>A new challenge has begun!</b>\n\n" .
               "The next challenge starts now: {$challengeName}. The challenge ends {$this->challenge->ends_at->diffForHumans()}.\n\n" .
               "View game: {$this->game->url}";
    }

    public function toWebPush(object $notifiable): array
    {
        $isFinalChallenge = $this->isFinalChallenge();

        if ($isFinalChallenge) {
            return [
                'title' => "🏁 Final Round: {$this->game->name}",
                'body' => "This is the final round! The challenge ends {$this->challenge->ends_at->diffForHumans()}.",
                'icon' => '/favicon.ico',
                'url' => $this->game->url,
            ];
        }

        $challengeName = $this->challenge->handler()::NAME;

        return [
            'title' => 'A new challenge has begun!',
            'body' => "The next challenge starts now: {$challengeName}. The challenge ends {$this->challenge->ends_at->diffForHumans()}.",
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
