<?php

namespace App\States;

use Thunk\Verbs\State;
use App\States\UserState;

class MembershipState extends State
{
    public int $user_id;

    public $ends_at;

    public function isActive(): bool
    {
        return $this->ends_at === null || $this->ends_at->isFuture();
    }

    public function user(): UserState
    {
        return UserState::load($this->user_id);
    }
}
