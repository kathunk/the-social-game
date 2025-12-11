<?php

namespace App\Events;

use App\Models\User;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class UserDisconnectedTelegram extends Event
{
    #[StateId(User::class)]
    public int $user_id;

    public function apply(User $user)
    {
        $user->telegram_chat_id = null;
        $user->telegram_username = null;
        $user->telegram_verification_token = null;
        $user->telegram_connected_at = null;
    }
}
