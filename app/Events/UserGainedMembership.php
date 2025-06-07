<?php

namespace App\Events;

use App\Events\Traits\HasUser;
use App\Models\Membership;
use App\States\MembershipState;
use Illuminate\Support\Carbon;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class UserGainedMembership extends Event
{
    use HasUser;

    #[StateId(MembershipState::class)]
    public ?int $membership_id = null;

    public ?Carbon $ends_at = null;

    public function apply(MembershipState $state)
    {
        $state->user_id = $this->user_id;
        $state->ends_at = $this->ends_at;
    }

    public function handle()
    {
        Membership::create([
            'id' => $this->membership_id,
            'user_id' => $this->user_id,
            'ends_at' => $this->ends_at,
        ]);
    }
}
