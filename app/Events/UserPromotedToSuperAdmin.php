<?php

namespace App\Events;

use App\Models\User;
use Thunk\Verbs\Event;
use App\States\UserState;
use App\Events\Traits\HasUser;

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
