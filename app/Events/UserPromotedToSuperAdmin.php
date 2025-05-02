<?php

namespace App\Events;

use App\Events\Traits\HasUser;
use App\Models\User;
use App\States\UserState;
use Thunk\Verbs\Event;

class UserPromotedToSuperAdmin extends Event
{
    use HasUser;

    public function applyToUser(UserState $user)
    {
        $user->is_super_admin = true;
    }

    public function handle()
    {
        $user = User::find($this->user_id);

        $user->update(['is_super_admin' => true]);
    }
}
