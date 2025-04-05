<?php

namespace App\Events;

use App\Models\User;
use Thunk\Verbs\Event;
use App\States\UserState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class UserPromotedToAdmin extends Event
{
    #[StateId(UserState::class)]
    public int $user_id;

    public function applyToUser(UserState $user)
    {
        $user->is_admin = true;
    }

    public function handle()
    {
        $user = User::find($this->user_id);
        $user->is_admin = true;
        $user->save();
    }
}
