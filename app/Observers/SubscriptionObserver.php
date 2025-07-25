<?php

namespace App\Observers;

// @todo i'm assuming this is the right model?
use App\Events\UserGainedMembership;
use App\Events\UserLostMembership;
use App\Models\Membership;
use Laravel\Cashier\Subscription;

class SubscriptionObserver
{
    public function created(Subscription $subscription)
    {
        UserGainedMembership::fire(
            user_id: $subscription->user_id,
        );
    }

    public function updated(Subscription $subscription)
    {
        if ($subscription->stripe_status === 'active') {
            return;
        }

        $membership = Membership::where('user_id', $subscription->user_id)->first();

        if (! $membership) {
            return;
        }

        UserLostMembership::fire(
            user_id: $subscription->user_id,
            membership_id: $membership->id,
            ended_at: now()->subSeconds(1),
        );
    }
}
