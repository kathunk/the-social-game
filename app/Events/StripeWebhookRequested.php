<?php

namespace App\Events;

use Thunk\Verbs\Event;

class StripeWebhookRequested extends Event
{
    public array $payload;

    public function handle()
    {
        //
    }
}
