<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\Models\Modifier;
use App\States\ModifierState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class PlayerSpawnedInFarm extends Event
{
    use HasGame, HasPlayer;

    #[StateId(ModifierState::class)]
    public int $map_modifier_id;

    public int $x_index;

    public int $y_index;

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data = collect($modifier->modifier_data)->map(function ($space) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                $space['player_ids'][] = $this->player_id;
            }

            return $space;
        })->toArray();
    }

    public function handle()
    {
        Modifier::find($this->map_modifier_id)->updateModelWithStateData();
    }
}
