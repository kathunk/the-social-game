<?php

namespace App\Events\Traits;

use App\States\UserState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasUser
{
    #[StateId(UserState::class)]
    public int $user_id;
}
