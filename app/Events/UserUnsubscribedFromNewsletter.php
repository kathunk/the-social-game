<?php

namespace App\Events;

use App\Events\Traits\HasUser;
use Thunk\Verbs\Event;

class UserUnsubscribedFromNewsletter extends Event
{
    use HasUser;

    public function handle()
    {
        $user = $this->user();

        $user->update([
            'subscribed_to_newsletter' => false,
        ]);
    }
}
