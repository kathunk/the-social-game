<?php

namespace App\Events;

use App\Events\Traits\HasUser;
use Thunk\Verbs\Event;

class UserDisconnectedTelegram extends Event
{
    use HasUser;

    public function handle()
    {
        $this->user()->update([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_verification_token' => null,
            'telegram_connected_at' => null,
        ]);
    }
}
