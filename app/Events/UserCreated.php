<?php

namespace App\Events;

use App\Models\User;
use App\States\UserState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class UserCreated extends Event
{
    #[StateId(UserState::class)]
    public ?int $user_id = null;

    public string $name;

    public string $email;

    public string $encrypted_password;

    public function applyToUser(UserState $user)
    {
        $user->name = $this->name;
        $user->email = $this->email;
        $user->encrypted_password = $this->encrypted_password;
        $user->status = 'pending';
    }

    public function handle()
    {
        $user = User::find($this->user_id);

        if ($user) {
            return $user;
        }

        $user =User::create([
            'id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->encrypted_password,
        ]);

        return $user;
    }
}
