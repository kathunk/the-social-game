<?php

namespace App\Events;

use App\Events\Traits\HasUser;
use Thunk\Verbs\Event;

class UserSubscribedToPush extends Event
{
    use HasUser;

    public array $subscription;

    public function handle()
    {
        $user = $this->user();
        $subscriptions = $user->push_subscriptions ?? [];

        $exists = collect($subscriptions)->contains(function ($sub) {
            return $sub['endpoint'] === $this->subscription['endpoint'];
        });

        if (! $exists) {
            $subscriptions[] = $this->subscription;
            $user->update([
                'push_subscriptions' => $subscriptions,
            ]);
        }
    }
}
