<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Cashier\Subscription;

class CancelAllStripeSubscriptions extends Command
{
    protected $signature = 'stripe:cancel-all-subscriptions
                            {--immediately : Cancel immediately instead of at the end of the billing period}
                            {--dry-run : Show what would be cancelled without actually cancelling}';

    protected $description = 'Cancel every active Stripe subscription. Defaults to cancel-at-period-end so users keep what they paid for.';

    public function handle(): int
    {
        $immediately = (bool) $this->option('immediately');
        $dryRun = (bool) $this->option('dry-run');

        // Active = subscription is not yet cancelled and has no ends_at in the past.
        // Cashier's "active" scope covers active + on-trial + still in grace period.
        $subscriptions = Subscription::query()
            ->where('stripe_status', '!=', 'canceled')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->get();

        $count = $subscriptions->count();

        if ($count === 0) {
            $this->info('No active subscriptions found. Nothing to do.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->warn("Found {$count} active subscription(s):");
        $this->line('');

        $this->table(
            ['Sub ID', 'User ID', 'Stripe ID', 'Status', 'Type', 'Ends At'],
            $subscriptions->map(fn (Subscription $s) => [
                $s->id,
                $s->user_id,
                $s->stripe_id,
                $s->stripe_status,
                $s->type,
                $s->ends_at?->toDateTimeString() ?? '—',
            ])->all()
        );

        $mode = $immediately ? 'IMMEDIATELY (no grace period)' : 'at the end of the current billing period';
        $this->line('');
        $this->warn("These subscriptions will be cancelled {$mode}.");

        if ($dryRun) {
            $this->info('--dry-run: nothing was cancelled.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Are you absolutely sure you want to cancel ALL of these subscriptions?', false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        if (! $this->confirm('This will hit the Stripe API and is hard to undo. Proceed?', false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $cancelled = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                if ($immediately) {
                    $subscription->cancelNow();
                } else {
                    $subscription->cancel();
                }
                $this->line("  ✓ Cancelled {$subscription->stripe_id} (user {$subscription->user_id})");
                $cancelled++;
            } catch (\Throwable $e) {
                $this->error("  ✗ Failed to cancel {$subscription->stripe_id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->line('');
        $this->info("Cancelled: {$cancelled}");

        if ($failed > 0) {
            $this->error("Failed: {$failed}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
