<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\GameState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Modifiers\Classes\FarmMap;
use App\Events\Traits\HasActivePlayer;
use App\Modifiers\Classes\FarmActions;

class PlayerMovedInFarm extends Event
{
    use HasActivePlayer, HasModifier, HasGame;

    public int $x_index;

    public int $y_index;

    public function validate()
    {
        // player has actions
        // player is in adjacent space
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                $space['player_ids'][] = $this->player_id;
            } else {
                $space['player_ids'] = array_diff($space['player_ids'], [$this->player_id]);
            }

            return $space;
        })->toArray();

        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $actions_state->modifier_data = collect($actions_state->modifier_data)
            ->map(function ($data, $player_id) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $data['actions'] = $data['actions'] - 1;

                return $data;
            })->toArray();
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
    }
}
