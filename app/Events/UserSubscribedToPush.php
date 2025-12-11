<?php

namespace App\Events;

use App\Models\User;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class UserSubscribedToPush extends Event
{
    #[StateId(User::class)]
    public int $user_id;

    public array $subscription;

    public function apply(User $user)
    {
        $subscriptions = $user->push_subscriptions ?? [];

        $exists = collect($subscriptions)->contains(function ($sub) {
            return $sub['endpoint'] === $this->subscription['endpoint'];
        });

        if (! $exists) {
            $subscriptions[] = $this->subscription;
            $user->push_subscriptions = $subscriptions;
        }
    }
}
