<?php

namespace App\Events;

use App\Events\Traits\HasUser;
use Thunk\Verbs\Event;

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
