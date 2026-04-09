<?php

namespace App\Events\PeckingOrder;

use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\States\ChallengeState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerSpiedOpponents extends Event
{
    use HasChallenge, HasGame, HasPlayer;

    public array $spied_opponent_ids;

    public string $ui_message;

    public int $score_cost;

    public int $hidden_score_cost;

    public function applyToPlayer(PlayerState $player)
    {
        if ($this->hidden_score_cost > 0) {
            $player->addToScoreHistory(
                icon: '👁️',
                points: -$this->hidden_score_cost,
                description: $this->ui_message,
                is_hidden: true,
            );
        }

        if ($this->score_cost > 0) {
            $player->addToScoreHistory(
                icon: '👁️',
                points: -$this->score_cost,
                description: $this->ui_message,
                is_hidden: false,
            );
        }
    }

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->challenge_data['information_bought'][$this->player_id] = $this->ui_message;
    }

    public function handle()
    {
        $this->challenge()->updateModelWithStateData();
        $this->player()->updateModelWithStateData();
    }
}
