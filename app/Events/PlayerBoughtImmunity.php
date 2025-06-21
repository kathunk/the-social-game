<?php

namespace App\Events;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerBoughtImmunity extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public int $cost_in_hidden_points;

    public int $cost_in_points;

    public function validate()
    {
        $score = $this->state(PlayerState::class)->score();
        $hidden_points = $this->state(PlayerState::class)->score(include_hidden: true) - $score;

        if ($this->cost_in_hidden_points > 0) {
            $this->assert(
                $hidden_points >= $this->cost_in_hidden_points,
                'Player does not have enough hidden points to buy immunity',
            );
        }

        if ($this->cost_in_points > 0) {
            $this->assert(
                $score >= $this->cost_in_points,
                'Player does not have enough points to buy immunity',
            );
        }
    }

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->challenge_data['immune_player_ids'][] = $this->player_id;
    }

    public function applyToPlayer(PlayerState $player)
    {
        if ($this->cost_in_hidden_points > 0) {
            $player->addToScoreHistory(-$this->cost_in_hidden_points, '🛡️ Bought immunity', true);
        }

        if ($this->cost_in_points > 0) {
            $player->addToScoreHistory(-$this->cost_in_points, '🛡️ Bought immunity');
        }
    }

    public function handle()
    {
        $this->challenge()->updateModelWithStateData();

        $this->applyToChallenge($this->challenge()->state());
        $this->game()->players->each(fn ($p) => $p->updateModelWithStateData());
    }
}
