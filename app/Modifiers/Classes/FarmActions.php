<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\ModifierState;
use App\States\ChallengeState;
use Thunk\Verbs\Facades\Verbs;

class FarmActions extends BaseModifierClass
{
    const NAME = 'Farm Actions';

    const DESCRIPTION = 'The actions for the farm game.';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'farm_actions';
    }

    public function isInvalidForTemplate(
        array $challenges,
        array $modifiers,
        string $type,
        array $team_names
    ) {
        if (! in_array(FarmMap::key(), $modifiers)) {
            return 'Farm teams modifier is required to run this modifier';
        }

        return false;
    }

    public function frontendComponent(Player $player): array
    {
        if ($player->team_id === null) {
            return [];
        }

        $player_actions = $this->modifier->modifier_data[$player->id] ?? [];

        $player_space = collect($this->farmMap()->modifier_data)
            ->filter(fn ($data) => in_array($player->id, $data['player_ids']))->first();

        return $this->form()
            ->title('Actions')
            ->farmActions(
                player_actions: $player_actions,
                player_space: $player_space,
                farm_map: $this->farmMap()->modifier_data,
                can_move: $this->canMove($player),
            )
            ->build();
    }

    public function canMove(Player $player)
    {
        return $this->modifier->modifier_data[$player->id]['actions'] > 0;
    }

    public function farmMap()
    {
        if ($this->modifier) {
            return $this->modifier->game->modifiers->firstWhere('class_key', FarmMap::key());
        }

        if ($this->modifier_state) {
            return $this->modifier_state->game()->modifiers()->filter(fn ($modifier) => $modifier->class_key === FarmMap::key())->first();
        }

        return [];
    }

    public function onGameStarted(
        GameState $game_state,
        ModifierState $modifier_state,
    ) {
        $game_state->player_ids->each(function ($player_id) use ($modifier_state) {
            $this->initializePlayerActions($modifier_state, $player_id);
        });
    }

    public function onUserAdmittedToGame(
        PlayerState $player_state,
        GameState $game_state,
        ModifierState $modifier_state,
    ) { 
        $this->initializePlayerActions($modifier_state, $player_state->id);
    }

    public function initializePlayerActions(ModifierState $modifier_state, int $player_id)
    {
        $modifier_state->modifier_data[$player_id] = [
            'actions' => 3,
            'limit' => 3,
            'actions_gained_per_round' => 3,
        ];
    }

    public function onChallengeStarted(
        GameState $game_state,
        ChallengeState $challenge_state,
        ModifierState $modifier_state,
    )
    {
        $modifier_state->modifier_data = collect($modifier_state->modifier_data)
            ->map(function ($data) {

                $actions_to_gain = $data['actions_gained_per_round'];
                $current_actions = $data['actions'];
                $limit = $data['limit'];

                $new_actions = min($current_actions + $actions_to_gain, $limit);

                return [
                    'actions' => $new_actions,
                    ...$data,
                ];
            })->toArray();
    }

    public function move(Player $player, array $params)
    {
        $space_string = $params['space_string'];

        $x = ord($space_string[0]) - 65;
        $y = intval($space_string[1]) - 1;

        $space = collect($this->farmMap()->modifier_data)->firstWhere('x-index', $x)->firstWhere('y-index', $y);

        if (! $space) {
            throw new \Exception('Space not found');
        }

        // PlayerMovedInFarm::fire(
        //     game_id: $this->modifier->game_id,
        //     modifier_id: $this->modifier->id,
        //     player_id: $params['player_id'],
        //     x_index: $x,
        //     y_index: $y,
        // );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }
}
