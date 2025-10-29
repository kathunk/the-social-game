<?php

namespace App\Modifiers\Classes;

use App\Events\PlayerBuiltRoad;
use App\Events\PlayerBuiltSilo;
use App\Events\PlayerDepositedToSilo;
use App\Events\PlayerHarvestedField;
use App\Events\PlayerPlantedField;
use App\Events\PlayerSeizedFarmProperty;
use App\Events\PlayerUpgradedSilo;
use App\Events\PlayerWithdrewFromSilo;
use App\Models\Player;
use App\States\GameState;
use App\States\ModifierState;
use App\States\PlayerState;
use Illuminate\Support\Collection;
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

        $player_skills = $this->allSkills()[$player->id]['skills'];

        return $this->form()
            ->farmActions(
                player_actions: $player_actions,
                player_space: $player_space,
                player_skills: $player_skills,
                farm_map: $this->farmMap()->modifier_data,
                can_plant_field: $this->canPlantField($player, $player_skills, $player_space),
                can_harvest_field: $this->canHarvestField($player, $player_space, $player_actions),
                can_build_silo: $this->canBuildSilo($player, $player_skills, $player_space),
                silo_exists: $this->siloExists($player, $player_space),
                can_upgrade_silo: $this->canUpgradeSilo($player, $player_skills, $player_space),
                can_withdraw_silo: $this->canWithdrawSilo($player, $player_space, $player_actions),
                can_deposit_silo: $this->canDepositSilo($player, $player_space, $player_actions),
                can_build_road: $this->canBuildRoad($player_skills, $player_space, $player_actions['actions'] ?? 0),
                player: $player,
                all_leader_ids: $this->allLeaderIds()->toArray(),
                field_seize_data: $this->seizePropertyData($player, $this->allPlayersOnSpace($player), $this->allSkills(), $player_space['field_status']['level'], $player_space['field_status']['owner_team_id']),
                silo_seize_data: $this->seizePropertyData($player, $this->allPlayersOnSpace($player), $this->allSkills(), $player_space['silo_status']['level'], $player_space['silo_status']['owner_team_id']),
                can_see_history: $this->canSeeHistory($player, $player_skills),
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

    public function farmTeams()
    {
        if ($this->modifier) {
            return $this->modifier->game->modifiers->firstWhere('class_key', FarmTeams::key());
        }

        if ($this->modifier_state) {
            return $this->modifier_state->game()->modifiers()->filter(fn ($modifier) => $modifier->class_key === FarmTeams::key())->first();
        }

        return [];
    }

    public function allLeaderIds()
    {
        return collect($this->farmTeams()->modifier_data['leaders'])->values();
    }

    public function allPlayersOnSpace(Player $player)
    {
        return collect($this->playerSpace($player)['player_ids'])
            ->map(fn ($player_id) => Player::find($player_id));
    }

    // actions

    public function canPlantField(Player $player, array $player_skills, array $player_space)
    {
        return $this->modifier->modifier_data[$player->id]['actions'] > 0
            && $player_skills['Farmer'] > 0
            && ($player_space['field_status']['level'] ?? null) === null
            && $player_space['type'] !== 'desert'
            && $player_space['type'] !== 'swamp'
            && $player_space['type'] !== 'volcano'
            && $player_space['type'] !== 'ash_heap';
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
            && $player_space['type'] !== 'swamp'
            && $player_space['type'] !== 'volcano'
            && $player_space['type'] !== 'ash_heap';
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
        return ($player_actions['grain_capacity'] ?? 0) > ($player_actions['grain'] ?? 0)
            && $player_space['silo_status']['amount'] > 0
            && $player_space['silo_status']['owner_team_id'] === $player->team_id;
    }

    public function withdrawSilo(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);

        PlayerWithdrewFromSilo::fire(
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
        return ($player_actions['grain'] ?? 0) > 0
            && $player_space['silo_status']['capacity'] > $player_space['silo_status']['amount']
            && $player_space['silo_status']['owner_team_id'] === $player->team_id;
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
            && ($player_actions['grain_capacity'] ?? 0) > ($player_actions['grain'] ?? 0);
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

    public function seizePropertyData(
        Player $player,
        Collection $players_on_space,
        array $all_skills,
        ?int $property_level = null,
        ?int $property_owner_team_id = null
    ) {
        if ($property_level === null || $property_owner_team_id === null) {
            return [
                'property_defense' => null,
                'attack_power_from_attackers' => null,
                'defense_power_from_defenders' => null,
                'attack_power' => null,
                'defense_power' => null,
                'can_seize' => false,
            ];
        }

        $defenders = $players_on_space->filter(fn ($p) => $p->team_id === $property_owner_team_id);
        $defense_from_defenders = $defenders->sum(fn ($p) => $all_skills[$p->id]['skills']['Brute']);
        $defense_power = $property_level - 1 + $defenders->sum(fn ($p) => $all_skills[$p->id]['skills']['Brute']);

        $attackers = $players_on_space->filter(fn ($p) => $p->team_id === $player->team_id);
        $attack_power = $attackers->sum(fn ($p) => $all_skills[$p->id]['skills']['Brute']);

        return [
            'property_defense' => $property_level - 1,
            'attack_power_from_attackers' => $attack_power,
            'defense_power_from_defenders' => $defense_from_defenders,
            'attack_power' => $attack_power,
            'defense_power' => $defense_power,
            'can_seize' => $attack_power > $defense_power,
            'defender_names' => $defenders->map(fn ($p) => $p->name)->implode(', '),
            'attacker_names' => $attackers->map(fn ($p) => $p->name)->implode(', '),
        ];
    }

    public function seizeSilo(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);

        PlayerSeizedFarmProperty::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
            property_type: 'silo',
            grain_transferred: $player_space['silo_status']['amount'],
            previous_owner_team_id: $player_space['silo_status']['owner_team_id'],
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function seizeField(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);

        PlayerSeizedFarmProperty::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
            property_type: 'field',
            grain_transferred: 0,
            previous_owner_team_id: $player_space['field_status']['owner_team_id'],
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function canBuildRoad(array $player_skills, array $player_space, int $actions)
    {
        return $actions > 0
            && $player_skills['Builder'] > 2
            && ($player_space['road_status']['owner_team_id'] ?? null) === null
            && ($player_space['type'] !== 'swamp')
            && ($player_space['type'] !== 'mountain')
            && ($player_space['type'] !== 'volcano')
            && ($player_space['type'] !== 'ash_heap');
    }

    public function buildRoad(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);

        PlayerBuiltRoad::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
            level: 1,
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    // routines

    public function initializePlayerActions(ModifierState $modifier_state, int $player_id)
    {
        $modifier_state->modifier_data[$player_id] = [
            'actions' => 6,
            'action_limit' => 6,
            'actions_gained_per_round' => 3,
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
        $modifier_state = $game_state->modifiers()->firstWhere('class_key', self::key());

        $modifier_state->modifier_data = collect($modifier_state->modifier_data)
            ->map(function ($data, $player_id) {
                $actions_to_gain = $data['actions_gained_per_round'];
                $current_actions = $data['actions'];
                $limit = $data['action_limit'];

                $new_actions = min($current_actions + $actions_to_gain, $limit);

                return [
                    'actions' => $new_actions,
                    'action_limit' => $limit,
                    'actions_gained_per_round' => $actions_to_gain,
                    'grain' => $data['grain'],
                    'grain_capacity' => $data['grain_capacity'],
                ];
            })->toArray();
    }

    public function canSeeHistory(Player $player, array $player_skills)
    {
        // return $player_skills['Chronicler'] > 0;
        return false;
    }
}
