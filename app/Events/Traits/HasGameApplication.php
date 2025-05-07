<?php

namespace App\Events\Traits;

use App\Models\GameApplication;
use App\States\GameApplicationState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasGameApplication
{
    #[StateId(GameApplicationState::class)]
    public int $application_id;

    public function validateGameApplication()
    {
        $this->assert(
            $this->state(GameApplicationState::class)->user_id === $this->user_id,
            'Application does not match the user',
        );

        $this->assert(
            $this->state(GameApplicationState::class)->game_id === $this->game_id,
            'Application does not match the game',
        );
    }

    public function gameApplication()
    {
        return GameApplication::find($this->application_id);
    }
}
