<?php

namespace App\Events;

use App\Events\Traits\HasMembership;
use App\Events\Traits\HasUser;
use App\States\MembershipState;
use Illuminate\Support\Carbon;
use Thunk\Verbs\Event;

class UserLostMembership extends Event
{
    use HasMembership, HasUser;

    public Carbon $ended_at;

    public function apply(MembershipState $state)
    {
        $state->ends_at = $this->ended_at;
    }

    public function handle()
    {
        $this->membership()->update([
            'ends_at' => $this->ended_at,
        ]);
    }
}
