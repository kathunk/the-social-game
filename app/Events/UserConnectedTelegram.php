<?php

namespace App\Events;

use App\Events\Traits\HasUser;
use Thunk\Verbs\Event;

class UserConnectedTelegram extends Event
{
    use HasUser;

    public string $telegram_chat_id;

    public ?string $telegram_username;

    public function handle()
    {
        $this->user()->update([
            'telegram_chat_id' => $this->telegram_chat_id,
            'telegram_username' => $this->telegram_username,
            'telegram_verification_token' => null,
            'telegram_connected_at' => now(),
        ]);
    }
}
