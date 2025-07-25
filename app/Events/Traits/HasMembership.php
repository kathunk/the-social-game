<?php

namespace App\Events\Traits;

use App\Models\Membership;
use App\States\MembershipState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasMembership
{
    #[StateId(MembershipState::class)]
    public int $membership_id;

    public function membership()
    {
        return Membership::find($this->membership_id);
    }
}
