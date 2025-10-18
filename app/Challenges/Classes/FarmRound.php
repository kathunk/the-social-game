<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmMap;

class FarmRound extends BaseChallengeClass
{
    const NAME = 'Farm round';

    const DESCRIPTION = 'description';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'farm_round';
    }

    public function isInvalidForTemplate(
        array $challenge_keys,
        array $modifier_keys,
        string $type,
        array $team_names
    ) {
        if (! in_array(FarmMap::key(), $modifier_keys)) {
            return 'Farm map modifier is required to run this challenge';
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        return [];
    }

    public function frontendComponent(Player $player): array
    {
        if (! $player->team_id) {
            return [];
        }

        $is_final_round = $this->challenge->round_number === $this->challenge->game->challenges->count();

        $actions = $this->actionsModifier($player);
        $actions_limit = $actions['action_limit'];
        $actions_current = $actions['actions'];
        $actions_gained_per_round = $actions['actions_gained_per_round'];

        return $this->form()->title('Round '.$this->challenge->round_number)
            ->when(! $is_final_round, fn ($form) => $form->subtitle('You will gain '.$actions_gained_per_round.' actions at the end of this round, but you can only hold up to '.$actions_limit.' actions at a time.'))
            ->when($is_final_round, fn ($form) => $form->subtitle('This is the final round. Good luck.'))
            ->build();
    }

    public function actionsModifier(Player $player): array
    {
        $mod = $this->challenge->game->modifiers->firstWhere('class_key', FarmActions::key());

        return $mod->modifier_data[$player->id];
    }
}
