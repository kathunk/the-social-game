<?php

namespace App\Events;

use App\Events\Traits\HasUser;
use Thunk\Verbs\Event;

class UserUnsubscribedFromPush extends Event
{
    use HasUser;

    public string $endpoint;

    public function handle()
    {
        $user = $this->user();

        $subscriptions = collect($user->push_subscriptions ?? [])
            ->filter(fn ($sub) => $sub['endpoint'] !== $this->endpoint)
            ->values()
            ->toArray();

        $user->update([
            'push_subscriptions' => $subscriptions,
        ]);
    }
}
