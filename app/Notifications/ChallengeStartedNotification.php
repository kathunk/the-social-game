<?php

namespace App\Notifications;

use App\Models\Challenge;
use App\Models\Game;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChallengeStartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
            $description = 'This is the final round!';

            if ($sentence = $this->endsAtSentence()) {
                $description .= "\n\n{$sentence}";
            }

            return [
                'content' => "🏁 **Final Round: {$this->game->name}**",
                'embeds' => [
                    [
                        'title' => $this->game->name,
                        'description' => $description,
                        'url' => $this->game->url,
                        'color' => 0xff0000,
                        'timestamp' => now()->toIso8601String(),
                    ],
                ],
            ];
        }

        $challengeName = $this->challenge->handler()::NAME;
        $description = "The next challenge starts now: {$challengeName}.";

        if ($sentence = $this->endsAtSentence()) {
            $description .= " {$sentence}";
        }

        return [
            'content' => "**A new challenge has begun!**",
            'embeds' => [
                [
                    'title' => $this->game->name,
                    'description' => $description,
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
            $message = "🏁 <b>Final Round: {$this->game->name}</b>\n\n" .
                       "This is the final round!\n\n";

            if ($sentence = $this->endsAtSentence()) {
                $message .= "{$sentence}\n\n";
            }

            return $message . "View game: {$this->game->url}";
        }

        $challengeName = $this->challenge->handler()::NAME;
        $message = "<b>A new challenge has begun!</b>\n\n" .
                   "The next challenge starts now: {$challengeName}.";

        if ($sentence = $this->endsAtSentence()) {
            $message .= " {$sentence}";
        }

        return $message . "\n\nView game: {$this->game->url}";
    }

    public function toWebPush(object $notifiable): array
    {
        $isFinalChallenge = $this->isFinalChallenge();

        if ($isFinalChallenge) {
            $body = 'This is the final round!';

            if ($sentence = $this->endsAtSentence()) {
                $body .= " {$sentence}";
            }

            return [
                'title' => "🏁 Final Round: {$this->game->name}",
                'body' => $body,
                'icon' => '/favicon.ico',
                'url' => $this->game->url,
            ];
        }

        $challengeName = $this->challenge->handler()::NAME;
        $body = "The next challenge starts now: {$challengeName}.";

        if ($sentence = $this->endsAtSentence()) {
            $body .= " {$sentence}";
        }

        return [
            'title' => 'A new challenge has begun!',
            'body' => $body,
            'icon' => '/favicon.ico',
            'url' => $this->game->url,
        ];
    }

    private function endsAtSentence(): ?string
    {
        return $this->challenge->ends_at
            ? "The challenge ends {$this->challenge->ends_at->diffForHumans()}."
            : null;
    }

    private function isFinalChallenge(): bool
    {
        $totalChallenges = $this->game->challenges()->count();
        return $this->challenge->round_number === $totalChallenges;
    }
}
