<?php

namespace App\Notifications\ElephantInTheRoom;

use App\Challenges\ElephantInTheRoom\Support\ImpossibleBotReward;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a player earns the impossible-bot bounty. Two flavors: the
 * winner gets their offer code (or an IOU when the pool ran dry), and the
 * site owner gets who won, which code went out, and how many are left.
 */
class ImpossibleBotDefeatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $winner_name,
        public ?string $code,
        public int $codes_remaining,
        public bool $for_owner = false,
        public ?string $winner_email = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->for_owner ? $this->ownerMail() : $this->winnerMail();
    }

    protected function winnerMail(): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('🎉 You beat the impossible bot!')
            ->greeting("Congratulations, {$this->winner_name}!")
            ->line('You beat the bot on impossible mode — most players never do.');

        if ($this->code !== null) {
            return $mail
                ->line('Your reward: a one-time code for '.ImpossibleBotReward::OFFER.'.')
                ->line("**{$this->code}**")
                ->line('Redeem it at checkout. One use, just for you.')
                ->action('Learn about Colossi', ImpossibleBotReward::OFFER_URL);
        }

        return $mail
            ->line('Your reward — a one-time code for '.ImpossibleBotReward::OFFER.' — is on its way.')
            ->line("We'll email it to you shortly.")
            ->action('Learn about Colossi', ImpossibleBotReward::OFFER_URL);
    }

    protected function ownerMail(): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->code !== null
                ? "🐘 {$this->winner_name} beat the impossible bot — code sent"
                : "🚨 {$this->winner_name} beat the impossible bot — POOL EMPTY")
            ->line("{$this->winner_name} just beat the bot on impossible mode.")
            ->line('Winner email: '.($this->winner_email ?? 'unknown'));

        if ($this->code !== null) {
            $mail->line("Offer code sent: {$this->code}");
        } else {
            $mail->line('No codes were left in the pool — they were promised one. Send a code manually, then top up the pool.');
        }

        $mail->line("Codes remaining in the pool: {$this->codes_remaining}.");

        if ($this->code !== null && $this->codes_remaining <= ImpossibleBotReward::LOW_POOL_THRESHOLD) {
            $mail->line('⚠️ The pool is running low — add more with `php artisan elephant:add-offer-codes`.');
        }

        return $mail->line('The full move log is in the winning game\'s Verbs event stream if you want to verify the run.');
    }
}
