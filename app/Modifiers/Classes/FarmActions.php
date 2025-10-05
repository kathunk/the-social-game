<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\ModifierState;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerBuiltSilo;
use App\Events\PlayerMovedInFarm;
use App\Events\PlayerPlantedFarm;
use App\Events\PlayerPlantedField;
use App\Events\PlayerUpgradedSilo;
use App\Events\PlayerHarvestedField;
use App\Events\PlayerDepositedToSilo;
use App\Events\PlayerWithrewFromSilo;

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
                can_plant_field: $this->canPlantField($player, $player_skills, $player_space),
                can_harvest_field: $this->canHarvestField($player, $player_space, $player_actions),
                can_build_silo: $this->canBuildSilo($player, $player_skills, $player_space),
                silo_exists: $this->siloExists($player, $player_space),
                can_upgrade_silo: $this->canUpgradeSilo($player, $player_skills, $player_space),
                can_withdraw_silo: $this->canWithdrawSilo($player, $player_space, $player_actions),
                can_deposit_silo: $this->canDepositSilo($player, $player_space, $player_actions),
                player: $player,
            )
            ->build();
    }



    // data helpers

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

    public function playerSpace(Player $player)
    {
        return collect($this->farmMap()->modifier_data)
            ->filter(fn ($data) => in_array($player->id, $data['player_ids']))->first();
    }

    public function siloExists(Player $player, array $player_space)
    {
        return $player_space['silo_status']['level'] > 0;
    }



    // actions

    public function canMove(Player $player)
    {
        return $this->modifier->modifier_data[$player->id]['actions'] > 0;
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

    public function canPlantField(Player $player, array $player_skills, array $player_space)
    {
        return $this->modifier->modifier_data[$player->id]['actions'] > 0
            && $player_skills['Farmer'] > 0
            && ($player_space['field_status']['level'] ?? null) === null
            && ($player_space['type'] === 'grass' || $player_space['type'] === 'mountain');
    }

    public function plantField(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);
        $player_skills = $this->allSkills()[$player->id]['skills'];
        
        PlayerPlantedField::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
            level: $player_skills['Farmer'],
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function canBuildSilo(Player $player, array $player_skills, array $player_space)
    {
        return $this->modifier->modifier_data[$player->id]['actions'] > 0
            && $player_skills['Builder'] > 0
            && ($player_space['silo_status']['level'] ?? null) === null
            && ($player_space['type'] !== 'swamp');
    }

    public function buildSilo(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);
        $player_skills = $this->allSkills()[$player->id]['skills'];
        
        PlayerBuiltSilo::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
            level: $player_skills['Builder'],
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function canUpgradeSilo(Player $player, array $player_skills, array $player_space)
    {
        return $this->modifier->modifier_data[$player->id]['actions'] > 0
            && $player_skills['Builder'] > $player_space['silo_status']['level'];
    }

    public function upgradeSilo(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);
        $player_skills = $this->allSkills()[$player->id]['skills'];
        
        PlayerUpgradedSilo::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
            level: $player_skills['Builder'],
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function canWithdrawSilo(Player $player, array $player_space, array $player_actions)
    {
        return $player_actions['grain_capacity'] > $player_actions['grain']
            && $player_space['silo_status']['amount'] > 0;
    }

    public function withdrawSilo(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);

        PlayerWithrewFromSilo::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
            amount: (int) $params['withdraw_amount'],
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function canDepositSilo(Player $player, array $player_space, array $player_actions)
    {
        return $player_actions['grain'] > 0
            && $player_space['silo_status']['capacity'] > $player_space['silo_status']['amount'];
    }

    public function depositSilo(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);
        
        PlayerDepositedToSilo::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
            amount: (int) $params['deposit_amount'],
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function canHarvestField(Player $player, array $player_space, array $player_actions)
    {
        return $this->modifier->modifier_data[$player->id]['actions'] > 0
            && $player_space['field_status']['stage'] === 'mature'
            && $player_space['field_status']['owner_team_id'] === $player->team_id
            && $player_actions['grain_capacity'] > $player_actions['grain'];
    }

    public function harvestField(Player $player, array $params)
    {
        $mod_data = $this->modifier->modifier_data[$player->id];
        $player_space = $this->playerSpace($player);
        
        PlayerHarvestedField::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
            field_quantity: $player_space['field_status']['quantity'],
            player_capacity: $mod_data['grain_capacity'],
            player_grain: $mod_data['grain'],
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }
    

    // routines

    public function initializePlayerActions(ModifierState $modifier_state, int $player_id)
    {
        $modifier_state->modifier_data[$player_id] = [
            // @farmtodo replace this with real values
            'actions' => 300,
            'action_limit' => 300,
            'grain' => 0,
            'grain_capacity' => 5,
        ];
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
                $limit = $data['action_limit'];

                $new_actions = min($current_actions + $actions_to_gain, $limit);

                return [
                    'actions' => $new_actions,
                    'action_limit' => $limit,
                    'grain' => $data['grain'],
                    'grain_capacity' => $data['grain_capacity'],
                ];
            })->toArray();
    }
}
