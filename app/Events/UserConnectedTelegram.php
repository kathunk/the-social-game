<?php

namespace App\Events;

use App\Models\User;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class UserConnectedTelegram extends Event
{
    #[StateId(User::class)]
    public int $user_id;

    public string $telegram_chat_id;

    public ?string $telegram_username;

    public function apply(User $user)
    {
        $user->telegram_chat_id = $this->telegram_chat_id;
        $user->telegram_username = $this->telegram_username;
        $user->telegram_verification_token = null;
        $user->telegram_connected_at = $this->created_at;
    }
}
