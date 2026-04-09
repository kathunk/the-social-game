<?php

namespace App\Events;

use App\Events\Traits\HasUser;
use App\Jobs\UnsubscribeUserFromKit;
use Thunk\Verbs\Event;
use Thunk\Verbs\Facades\Verbs;

class UserUnsubscribedFromNewsletter extends Event
{
    use HasUser;

    public function handle()
    {
        $user = $this->user();

        $user->update([
            'subscribed_to_newsletter' => false,
        ]);

        Verbs::unlessReplaying(function () use ($user) {
            UnsubscribeUserFromKit::dispatch($user->email);
        });
    }
}
