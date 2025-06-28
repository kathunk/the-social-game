<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\Events\Traits\HasUser;

class UserUnsubscribedFromNewsletter extends Event
{
    use HasUser;

    public function handle()
    {
        $this->user()->update([
            'subscribed_to_newsletter' => false,
        ]);
    }
}