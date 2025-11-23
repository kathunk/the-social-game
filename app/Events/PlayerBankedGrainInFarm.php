<?php

namespace App\Events;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasTeam;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmMap;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Event;

class PlayerBankedGrainInFarm extends Event
{
    use HasActivePlayer, HasChallenge, HasGame, HasModifier, HasTeam;

    public int $amount;

    public function validate()
    {
        $game = $this->state(GameState::class);

        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());
        $player_space = collect($map_state->modifier_data)
            ->firstWhere(fn ($space) => in_array($this->player_id, $space['player_ids']));

        $this->assert(
            $player_space['npc'] === 'broker',
            'Player is not on the broker space',
        );

        $player_grain = $actions_state->modifier_data[$this->player_id]['grain'];
        $this->assert(
            $player_grain >= $this->amount,
            'Player does not have enough grain to bank',
        );
    }

    public function apply(GameState $game)
    {
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $actions_state->modifier_data = collect($actions_state->modifier_data)
            ->map(function ($data, $player_id) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $data['grain'] = $data['grain'] - $this->amount;

                return $data;
            })->toArray();
    }

    public function applyToTeam(TeamState $team)
    {
        $team->addToScoreHistory('🌾', $this->amount, $this->state(PlayerState::class)->name.' banked '.$this->amount.' grain.');
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
        $this->team()->updateModelWithStateData();
    }
}
