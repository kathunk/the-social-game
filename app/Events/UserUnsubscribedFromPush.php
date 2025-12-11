<?php

namespace App\Events;

use App\Models\User;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class UserUnsubscribedFromPush extends Event
{
    #[StateId(User::class)]
    public int $user_id;

    public string $endpoint;

    public function apply(User $user)
    {
        $subscriptions = collect($user->push_subscriptions ?? [])
            ->filter(fn ($sub) => $sub['endpoint'] !== $this->endpoint)
            ->values()
            ->toArray();

        $user->push_subscriptions = $subscriptions;
    }
}
