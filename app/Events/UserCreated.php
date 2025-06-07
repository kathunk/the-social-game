<?php

namespace App\Events;

use App\Models\User;
use Thunk\Verbs\Event;
use App\States\UserState;
use Illuminate\Support\Facades\Log;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

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

        // Log::info('UserCreated event received', [
        //     'user_id' => $this->user_id, 
        //     'email' => $this->email,
        //     'existing_user' => $user,
        // ]);

        if ($user) {
            return $user;
        }

        // Also check by email to prevent duplicates during replay
        $existingUser = User::where('email', $this->email)->first();
        if ($existingUser) {
            return $existingUser;
        }

        $user = User::create([
            'id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->encrypted_password,
        ]);

        return $user;
    }
}
