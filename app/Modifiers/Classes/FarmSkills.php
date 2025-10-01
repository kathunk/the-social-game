<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\ModifierState;
use App\States\ChallengeState;
use Thunk\Verbs\Facades\Verbs;

class FarmSkills extends BaseModifierClass
{
    const NAME = 'Farm Skills';

    const DESCRIPTION = 'The skills for the farm game.';

    const TYPE = 'team';

    const CAPABILITIES = [
        // 'chronicler' => [
        //     'name' => 'Chronicler',
        //     'level_1' => 'See 1 round of Action History for your space',
        //     'level_2' => 'See 3 rounds of Action History for your space',
        //     'level_3' => 'See complete Action History for your space',
        // ],
        'brute' => [
            'name' => 'Brute',
            'level_1' => '+1 attack and defense of structures',
            'level_2' => '+2 attack and defense of structures',
            'level_3' => '+3 attack and defense of structures',
        ],
        'builder' => [
            'name' => 'Builder',
            'level_1' => 'Build level 1 Silos and Roads',
            'level_2' => 'Build level 2 Silos and Roads',
            'level_3' => 'Build level 3 Silos and Roads',
        ],
        'farmer' => [
            'name' => 'Farmer',
            'level_1' => 'Plant level 1 Farms',
            'level_2' => 'Plant level 2 Farms',
            'level_3' => 'Plant level 3 Farms',
        ],
        'porter' => [
            'name' => 'Porter',
            'level_1' => 'Max Grain capacity of 5',
            'level_2' => 'Max Grain capacity of 7',
            'level_3' => 'Max Grain capacity of 10',
        ],
        // 'scout' => [
        //     'name' => 'Scout',
        //     'level_1' => 'Inspect up to 1 space away',
        //     'level_2' => 'Inspect up to 2 spaces away',
        //     'level_3' => 'Inspect up to 3 spaces away',
        // ],
        'strategist' => [
            'name' => 'Strategist',
            'level_1' => 'Max capacity of 5 Actions',
            'level_2' => 'Max capacity of 7 Actions',
            'level_3' => 'Max capacity of 10 Actions',
        ],
        'tactician' => [
            'name' => 'Tactician',
            'level_1' => 'Gain 4 actions each round.',
            'level_2' => 'Gain 5 actions each round.',
            'level_3' => 'Gain 6 actions each round.',
        ],
        'thief' => [
            'name' => 'Thief',
            'level_1' => 'Pickpocket opponents for 25% of their grain',
            'level_2' => 'Pickpocket opponents for 50% of their grain',
            'level_3' => 'Pickpocket opponents for 100% of their grain',
        ],
    ];

    public static function key(): string
    {
        return 'farm_skills';
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
        
        return $this->form()
            ->title('Skills')
            ->subtitle('Make yourself useful. Spend XP to upgrade your skills.')
            ->buttonGroup()
            ->button('See available upgrades', 'seeUpgrades')
            ->endGroup()
            ->build();
    }

    public function frontendComponentForDedicatedPage(Player $player): array
    {
        return $this->form()
            ->title('Current XP: ' . $this->modifier->modifier_data[$player->id]['xp'])
            ->subtitle('Each round, you will gain 1 XP. Spend it to upgrade your skills.')
            ->divider()
            ->radioGroup(
                label: 'Skills',
                property_name: 'skills',
                options: $this->affordableSkills($player),
                validation_rules: 'required|exists:skills,id',
                validation_messages: [
                    'required' => 'Must select a skill',
                    'exists' => 'Must select a valid skill',
                ],
            )
            ->buttonGroup()
            ->button('Upgrade skill', 'upgradeSkill')
            ->endGroup()
            ->build();
    }

    public function seeUpgrades(Player $player, array $params)
    {
        return redirect()->route('games.secrets', ['game' => $player->game, 'modifier' => $this->modifier]);
    }

    public function upgradeSkill(Player $player, array $params)
    {
        // @todo upgrade skill
    }

    public function skillLevels(Player $player)
    {
        return $this->modifier->modifier_data[$player->id]['capabilities'];
    }

    public function affordableSkills(Player $player)
    {
        $buying_power = $this->modifier->modifier_data[$player->id]['xp'];
        $capabilities = $this->skillLevels($player);

        return collect(self::CAPABILITIES)
            ->map(function ($capability) use ($capabilities, $buying_power) {
                $existing_level = $capabilities[$capability['name']];
                $cost = $existing_level + 1;

                return [
                    'label' => $capability['name'] . ' Level ' . $existing_level + 1,
                    'value' => $capability['name'],
                    'description' => $capability['level_' . $existing_level + 1] . ' (cost: ' . $cost . ' XP)',
                    'disabled' => $buying_power < $cost,
                ];
            })->values()->toArray();
    }

    public function onGameStarted(
        GameState $game_state,
        ModifierState $modifier_state,
    ) {
        $game_state->player_ids->each(function ($player_id) use ($modifier_state) {
            $this->initializePlayerSkills($modifier_state, $player_id);
        });
    }

    public function onUserAdmittedToGame(
        PlayerState $player_state,
        GameState $game_state,
        ModifierState $modifier_state,
    ) { 
        $this->initializePlayerSkills($modifier_state, $player_state->id);
    }

    public function initializePlayerSkills(ModifierState $modifier_state, int $player_id)
    {
        $modifier_state->modifier_data[$player_id] = [
            'xp' => 3,
            'capabilities' => collect(self::CAPABILITIES)
                ->mapWithKeys(fn ($capability) => [$capability['name'] => 0])
                ->toArray(),
        ];
    }

    public function incrementPlayerXp(
        ModifierState $modifier_state,
        int $player_id,
        int $xp,
    )
    {
        $modifier_state->modifier_data = collect($modifier_state->modifier_data)
            ->map(function ($data) use ($player_id, $xp) {

                if ($player_id !== $data['player_id']) {
                    return $data;
                }

                return [
                    'xp' => $data['xp'] + $xp,
                    ...$data,
                ];
            })->toArray();
    }

    public function upgradePlayerCapability(
        ModifierState $modifier_state,
        int $player_id,
        string $capability,
    )
    {
        $modifier_state->modifier_data = collect($modifier_state->modifier_data)
            ->map(function ($data) use ($player_id, $capability) {

                if ($player_id !== $data['player_id']) {
                    return $data;
                }

                $current_capability = $data['capabilities'][$capability];
                $xp_cost = $current_capability +1;

                $data['capabilities'][$capability] = $current_capability + 1;
                $data['xp'] = $data['xp'] - $xp_cost;

                return $data;
            })->toArray();
    }
}
