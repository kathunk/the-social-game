<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\ModifierState;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerMovedInFarm;

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

        $player_skills = $this->allSkills()[$player->id]['skills'];

        return $this->form()
            ->farmActions(
                player_actions: $player_actions,
                player_space: $player_space,
                player_skills: $player_skills,
                farm_map: $this->farmMap()->modifier_data,
                can_move: $this->canMove($player),
                can_plant_farm: $this->canPlantFarm($player, $player_skills, $player_space),
                current_player_id: $player->id, // Pass the current player ID
            )
            ->build();
    }

    public function canMove(Player $player)
    {
        return $this->modifier->modifier_data[$player->id]['actions'] > 0;
    }

    public function canPlantFarm(Player $player, array $player_skills, array $player_space)
    {
        return $this->modifier->modifier_data[$player->id]['actions'] > 0 
            && $player_skills['Farmer'] > 0
            && $player_space['farm_status'] === 'null'
            && ($player_space['type'] === 'grass' || $player_space['type'] === 'mountain');
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

    public function allSkills()
    {
        if ($this->modifier) {
            return $this->modifier->game->modifiers->firstWhere('class_key', FarmSkills::key())
                ->modifier_data;
        }

        if ($this->modifier_state) {
            return $this->modifier_state->game()->modifiers()->filter(fn ($modifier) => $modifier->class_key === FarmSkills::key())->first()
                ->modifier_data;
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
            'grain' => 0,
            'grain_capacity' => 5,
        ];
    }

    public function onChallengeEnded(GameState $game_state)
    {

        $skills_modifier = $game_state->modifiers()->firstWhere('class_key', FarmSkills::key());
        $all_skills = $skills_modifier->modifier_data;

        $modifier_state = $game_state->modifiers()->firstWhere('class_key', self::key());

        $modifier_state->modifier_data = collect($modifier_state->modifier_data)
            ->map(function ($data, $player_id) use ($all_skills) {

                $player_skills = $all_skills[$player_id]['skills'];
                $actions_to_gain = 3 + $player_skills['Tactician'];
                $current_actions = $data['actions'];
                $limit = 3 + ($player_skills['Strategist'] * 2);

                $new_actions = min($current_actions + $actions_to_gain, $limit);

                return [
                    'actions' => $new_actions,
                    'grain' => $data['grain'],
                    'grain_capacity' => $data['grain_capacity'],
                ];
            })->toArray();
    }

    public function move(Player $player, array $params)
    {
        $space_string = $params['space_string'];

        $x = ord($space_string[0]) - 65;
        $y = intval($space_string[1]) - 1;

        $space = collect($this->farmMap()->modifier_data)
            ->filter(fn ($space) => $space['x-index'] === $x && $space['y-index'] === $y)
            ->first();

        if (! $space) {
            throw new \Exception('Space not found');
        }

        PlayerMovedInFarm::fire(
            game_id: $this->modifier->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            x_index: $x,
            y_index: $y,
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function plantFarm(Player $player, array $params)
    {
        dd($params);
    }
}
