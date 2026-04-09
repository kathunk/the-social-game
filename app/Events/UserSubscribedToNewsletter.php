<?php

namespace App\Events;

use App\Events\Traits\HasUser;
use App\Jobs\SubscribeUserToKit;
use Thunk\Verbs\Event;
use Thunk\Verbs\Facades\Verbs;

class UserSubscribedToNewsletter extends Event
{
    use HasUser;

    public function handle()
    {
        $user = $this->user();

        $user->update([
            'subscribed_to_newsletter' => true,
        ]);

        Verbs::unlessReplaying(function () use ($user) {
            SubscribeUserToKit::dispatch($user->email, $user->name);
        });
    }
}
