<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\Events\Traits\HasUser;

class UserSubscribedToNewsletter extends Event
{
    use HasUser;

    public function handle()
    {
        $this->user()->update([
            'subscribed_to_newsletter' => true,
        ]);
    }
}