<?php

namespace App\Events\Traits;

use App\Models\User;
use App\States\UserState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasUser
{
    #[StateId(UserState::class)]
    public int $user_id;

    public function user()
    {
        return User::find($this->user_id);
    }
}
