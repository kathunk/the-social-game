<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\ModifierState;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerBuiltRoad;
use App\Events\PlayerBuiltSilo;
use App\Events\PlayerBuiltWalls;
use App\Events\PlayerPlantedField;
use App\Events\PlayerUpgradedSilo;
use Illuminate\Support\Collection;
use App\Events\PlayerHarvestedField;
use App\Events\PlayerBuiltWatchtower;
use App\Events\PlayerDepositedToSilo;
use App\Events\PlayerWithdrewFromSilo;
use App\Events\PlayerSeizedFarmProperty;
use App\Events\PlayerPickpocketedOpponent;

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

        if ($player_space === null) {
            return [];
        }

        $all_players_on_space = $this->allPlayersOnSpace($player);
        $allies_on_space = $all_players_on_space->filter(fn ($p) => $p->team_id === $player->team_id);

        $all_skills = $this->allSkills();
        $player_skills = $all_skills[$player->id]['skills'];
        $ally_skills = collect($all_skills)
            ->filter(fn ($skill_set, $player_id) => $allies_on_space->pluck('id')->contains($player_id)
                && $player_id !== $player->id
            )
            ->toArray();

        $seize_data = $this->seizePropertyData(
            $player, 
            $all_players_on_space, 
            $all_skills, 
            $player_space, 
            $player_space['silo_status']['level'],
            $player_space['silo_status']['owner_team_id']
        );

        return $this->form()
            ->farmActions(
                player_actions: $player_actions,
                player_space: $player_space,
                player_skills: $player_skills,
                farm_map: $this->farmMap()->modifier_data,
                can_plant_field: $this->canPlantField($player, $player_skills, $player_space, $ally_skills),
                can_harvest_field: $this->canHarvestField($player, $player_space, $player_actions, $player_skills, $ally_skills),
                can_build_silo: $this->canBuildSilo($player, $player_skills, $player_space),
                silo_exists: $this->siloExists($player, $player_space),
                can_upgrade_silo: $this->canUpgradeSilo($player, $player_skills, $player_space),
                can_withdraw_silo: $this->canWithdrawSilo($player, $player_space, $player_actions, $player_skills, $seize_data),
                can_deposit_silo: $this->canDepositSilo($player, $player_space, $player_actions),
                can_build_road: $this->canBuildRoad($player_skills, $player_space, $player_actions['actions'] ?? 0, $ally_skills),
                can_build_wall: $this->canBuildWall($player_skills, $player_space, $player_actions['actions'] ?? 0, $ally_skills),
                can_build_trap: $this->canBuildTrap($player_skills, $player_space, $player_actions['actions'] ?? 0, $ally_skills),
                can_build_watchtower: $this->canBuildWatchtower($player_skills, $player_space, $player_actions['actions'] ?? 0, $ally_skills),
                pickpocketable_opponents: $this->pickpocketableOpponents($player, $player_skills, $player_space, $player_actions['actions'] ?? 0),
                player: $player,
                all_leader_ids: $this->allLeaderIds()->toArray(),
                silo_seize_data: $seize_data,
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

    public function allySkillLevelOnSpace(string $skill, array $player_space, array $ally_skills)
    {
        return collect($ally_skills)
            ->filter(fn ($s) => $s['skills'][$skill] > 0)
            ->max();
    }

    // actions

    public function canPlantField(Player $player, array $player_skills, array $player_space, array $ally_skills)
    {
        $cost = 4 - $player_skills['Farmer'];
        $actions = $this->modifier->modifier_data[$player->id]['actions'];
        $type = $player_space['type'];
        $ally_brute_level = $this->allySkillLevelOnSpace('Brute', $player_space, $ally_skills);
        $ally_builder_level = $this->allySkillLevelOnSpace('Builder', $player_space, $ally_skills);

        $type_is_valid = match ($type) {
            'grass' => true,
            'fertile_ashland' => true,
            'mountain' => $ally_brute_level && $ally_brute_level > 0,
            'desert' => $ally_builder_level && $ally_builder_level > 0,
            default => false,
        };

        return $actions >= $cost
            && $player_skills['Farmer'] > 0
            && ($player_space['field_status']['level'] ?? null) === null
            && $type_is_valid;
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

    public function canWithdrawSilo(Player $player, array $player_space, array $player_actions, array $player_skills, array $seize_data)
    {
        $player_thief_level = $player_skills['Thief'] > 0;
        $silo_belongs_to_player = $player_space['silo_status']['owner_team_id'] === $player->team_id;
        $silo_has_grain = $player_space['silo_status']['amount'] > 0;
        $player_is_at_capacity = ($player_actions['grain_capacity'] ?? 0) === ($player_actions['grain'] ?? 0);
        $silo_exists = $player_space['silo_status']['level'] > 0;

        if (! $silo_exists) {
            return false;
        }

        if ($player_is_at_capacity) {
            return false;
        }

        if (! $silo_has_grain) {
            return false;
        }

        if ($silo_belongs_to_player) {
            return true;
        }

        if ($player_thief_level < 1) {
            return false;
        }

        $thief_has_enough_power = match($player_thief_level) {
            1 => $seize_data['defense_power'] < 3,
            2 => $seize_data['defense_power'] < 5,
            3 => $seize_data['defense_power'] < 7,
            default => false,
        };

        return $thief_has_enough_power;
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

    public function canHarvestField(Player $player, array $player_space, array $player_actions, array $player_skills, array $ally_skills)
    {
        $stage = $player_space['field_status']['stage'] ?? null;
        if (! $stage === 'mature_1' || $stage === 'mature_2' || $stage === 'mature_3') {
            return false;
        }

        $farmer_level = $player_skills['Farmer'];
        $action_cost = 3 - $farmer_level;

        if ($this->modifier->modifier_data[$player->id]['actions'] < $action_cost) {
            return false;
        }

        if (($player_actions['grain_capacity'] ?? 0) === ($player_actions['grain'] ?? 0)) {
            return false;
        }

        $player_is_thief = $player_skills['Thief'] > 0;
        $player_is_farmer = $farmer_level > 0;
        $ally_farmer_level = $this->allySkillLevelOnSpace('Farmer', $player_space, $ally_skills);
        $ally_thief_level = $this->allySkillLevelOnSpace('Thief', $player_space, $ally_skills);

        $can_harvest_own_field = $player_space['field_status']['owner_team_id'] === $player->team_id;

        $can_harvest_opponent_field = ($player_is_thief && $ally_farmer_level && $ally_farmer_level > 0)
            || ($player_is_farmer && $ally_thief_level && $ally_thief_level > 0);

        return $can_harvest_own_field || $can_harvest_opponent_field;
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

    public static function seizePropertyData(
        Player $player,
        Collection $players_on_space,
        array $all_skills,
        array $player_space,
        ?int $property_level = null,
        ?int $property_owner_team_id = null
    ) {
        if ($property_level === null || $property_owner_team_id === null) {
            return [
                'attack_description' => null,
                'defense_description' => null,
                'can_seize' => false,
            ];
        }

        $defenders = $players_on_space->filter(fn ($p) => $p->team_id === $property_owner_team_id);
        $defense_from_defenders = $defenders->sum(fn ($p) => $all_skills[$p->id]['skills']['Brute']);
        $defense_from_level = $property_level - 1;
        $defense_from_walls = $player_space['walls_exist'] ? 2 : 0;
        $defense_power = $defense_from_level + $defense_from_walls + $defense_from_defenders;

        $attackers = $players_on_space->filter(fn ($p) => $p->team_id === $player->team_id);
        $attack_power = $attackers->sum(fn ($p) => $all_skills[$p->id]['skills']['Brute']);

        $defense_description = '🛡️ '.$defense_power.' defense: '.$defense_from_level.' from level + '.$defense_from_walls.' from walls + '.$defense_from_defenders.' from defenders';

        return [
            'attack_description' => 'Attack: '.$attack_power.' from '.$attackers->map(fn ($p) => $p->name)->implode(', '),
            'defense_description' => $defense_description,
            'can_seize' => $attack_power > $defense_power,
            'attack_power' => $attack_power,
            'defense_power' => $defense_power,
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

    public function canBuildRoad(array $player_skills, array $player_space, int $actions, array $ally_skills)
    {
        if ($player_skills['Builder'] < 1) {
            return false;
        }

        if (($player_space['road_exists'] ?? false) === true) {
            return false;
        }

        $acceptable_types = ['grass', 'fertile_ashland', 'mountain', 'desert'];

        if (! in_array($player_space['type'], $acceptable_types)) {
            return false;
        }

        $action_cost = 4 - $player_skills['Builder'];

        if ($actions < $action_cost) {
            return false;
        }

        $ally_farmer_level = $this->allySkillLevelOnSpace('Farmer', $player_space, $ally_skills);

        if (! $ally_farmer_level || $ally_farmer_level < 1) {
            return false;
        }

        return true;
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

    public function canBuildWall(array $player_skills, array $player_space, int $actions, array $ally_skills)
    {
        if ($player_skills['Builder'] < 1) {
            return false;
        }

        if (($player_space['walls_exist'] ?? false) === true) {
            return false;
        }

        $acceptable_types = ['grass', 'fertile_ashland', 'mountain', 'desert'];

        if (! in_array($player_space['type'], $acceptable_types)) {
            return false;
        }

        $action_cost = 4 - $player_skills['Builder'];

        if ($actions < $action_cost) {
            return false;
        }

        $ally_brute_level = $this->allySkillLevelOnSpace('Brute', $player_space, $ally_skills);

        if (! $ally_brute_level || $ally_brute_level < 1) {
            return false;
        }

        return true;
    }

    public function buildWall(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);

        PlayerBuiltWalls::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function canBuildTrap(array $player_skills, array $player_space, int $actions, array $ally_skills)
    {
        if ($player_skills['Builder'] < 1) {
            return false;
        }

        if (($player_space['trap_status']['level'] ?? false) !== null) {
            return false;
        }

        $action_cost = 4 - $player_skills['Builder'];

        if ($actions < $action_cost) {
            return false;
        }

        $ally_thief_level = $this->allySkillLevelOnSpace('Thief', $player_space, $ally_skills);

        if (! $ally_thief_level || $ally_thief_level < 1) {
            return false;
        }

        return true;
    }

    public function buildTrap(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);

        // PlayerBuiltTrap::fire(
        //     game_id: $player->game_id,
        //     modifier_id: $this->modifier->id,
        //     player_id: $player->id,
        //     team_id: $player->team_id,
        //     x_index: $player_space['x-index'],
        //     y_index: $player_space['y-index'],
        //     level: 1,
        // );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function canBuildWatchtower(array $player_skills, array $player_space, int $actions, array $ally_skills)
    {
        if ($player_skills['Builder'] < 1) {
            return false;
        }

        if (($player_space['watchtower_exists'] ?? false) === true) {
            return false;
        }

        $acceptable_types = ['grass', 'fertile_ashland', 'mountain', 'desert'];

        if (! in_array($player_space['type'], $acceptable_types)) {
            return false;
        }

        $action_cost = 4 - $player_skills['Builder'];

        if ($actions < $action_cost) {
            return false;
        }

        $ally_scout_level = $this->allySkillLevelOnSpace('Scout', $player_space, $ally_skills);

        if (! $ally_scout_level || $ally_scout_level < 1) {
            return false;
        }

        return true;
    }

    public function buildWatchtower(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);

        PlayerBuiltWatchtower::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function pickpocketableOpponents(Player $player, array $player_skills, array $player_space, int $actions)
    {
        $thief_level = $player_skills['Thief'];

        if ($thief_level < 1) {
            return collect([]);
        }

        $action_cost = 4 - $thief_level;

        if ($actions < $action_cost) {
            return collect([]);
        }

        return collect($player_space['player_ids'])
            ->filter(fn ($player_id) => $player_id !== $player->id)
            ->map(fn ($player_id) => Player::find($player_id))
            ->filter(fn ($p) => $p->team_id !== $player->team_id);
    }

    public function pickpocketOpponent(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);
        $opponent_id = (int) $params['pickpocket_target_id'];

        $player_capacity = $this->modifier->modifier_data[$player->id]['grain_capacity'];
        $player_grain = $this->modifier->modifier_data[$player->id]['grain'];
        $max_stealable = $player_capacity - $player_grain;
        $opponent_grain = $this->modifier->modifier_data[$opponent_id]['grain'];

        $amount = min($max_stealable, $opponent_grain);

        PlayerPickpocketedOpponent::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            target_player_id: $opponent_id,
            amount: $amount,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    // routines

    public function initializePlayerActions(ModifierState $modifier_state, int $player_id)
    {
        $modifier_state->modifier_data[$player_id] = [
            'actions' => 10,
            'action_limit' => 20,
            'actions_gained_per_round' => 10,
            'grain' => 0,
            'grain_capacity' => 20,
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
        return $player_skills['Scout'] > 0;
    }
}
