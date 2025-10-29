<?php

namespace App\Modifiers\Classes;

use App\Events\PlayerUpgradedSkillInFarm;
use App\Models\Player;
use App\States\ChallengeState;
use App\States\GameState;
use App\States\ModifierState;
use App\States\PlayerState;
use Thunk\Verbs\Facades\Verbs;

class FarmSkills extends BaseModifierClass
{
    const NAME = 'Farm Skills';

    const DESCRIPTION = 'The skills for the farm game.';

    const TYPE = 'team';

    const SKILLS = [
        'brute' => [
            'name' => 'Brute',
            'level_1' => 'Seize and defend structures',
            'level_2' => '+2 Seize and defense of structures, and spend fewer actions to seize',
            'level_3' => '+3 Seize and defense of structures, and spend fewer actions to seize',
        ],
        'engineer' => [
            'name' => 'Builder',
            'level_1' => 'Build Roads, Silos, Watchtowers, and Traps',
            'level_2' => 'Build level 2 Silos, Watchtowers, and Traps, and spend fewer actions to build',
            'level_3' => 'Build level 3 Silos, Watchtowers, and Traps, and spend fewer actions to build',
        ],
        'farmer' => [
            'name' => 'Farmer',
            'level_1' => 'Plant level 1 Farms',
            'level_2' => 'Plant level 2 Farms, and spend fewer actions to plant',
            'level_3' => 'Plant level 3 Farms, and spend fewer actions to plant',
        ],
        'scout' => [
            'name' => 'Scout',
            'level_1' => 'Inspect up to 2 spaces away',
            'level_2' => 'Inspect up to 3 spaces away',
            'level_3' => 'Inspect up to 4 spaces away',
        ],
        'thief' => [
            'name' => 'Thief',
            'level_1' => 'Pickpocket opponents',
            'level_2' => 'Spend fewer actions to pickpocket opponents',
            'level_3' => 'Spend fewer actions to pickpocket opponents',
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
        array $team_names,
    ) {
        if (! in_array(FarmMap::key(), $modifiers)) {
            return 'Farm teams modifier is required to run this modifier';
        }

        return false;
    }

    public function frontendComponentForDedicatedPage(Player $player): array
    {
        $affordable_skills = $this->affordableSkills($player);

        return $this->form()
            ->title(
                'Current XP: '.
                    $this->modifier->modifier_data[$player->id]['xp'],
            )
            ->subtitle(
                'Each round, you will gain 1 XP. Spend it to upgrade your skills. You can only upgrade 2 different skills. Once you have begun upgrading those skills, all other skills will be disabled for you.',
            )
            ->divider()
            ->when(count($affordable_skills) > 0, function ($form) use ($affordable_skills) {
                $form->radioGroup(
                    label: 'Skills',
                    property_name: 'selected_skill_to_upgrade',
                    options: $affordable_skills,
                    validation_rules: 'required|exists:skills,id',
                    validation_messages: [
                        'required' => 'Must select a skill',
                        'exists' => 'Must select a valid skill',
                    ],
                )
                    ->buttonGroup()
                    ->button('Upgrade skill', 'upgradeSkill')
                    ->endGroup();
            })
            ->when(count($affordable_skills) === 0, function ($form) {
                $form->subtitle('You have upgraded as much as possible. Now get back to work!');
            })
            ->build();
    }

    public function seeUpgrades(Player $player, array $params)
    {
        return redirect()->route('games.mods', [
            'game' => $player->game,
            'modifier' => $this->modifier,
        ]);
    }

    public function upgradeSkill(Player $player, array $params)
    {
        $selected_skill = $params['selected_skill_to_upgrade'];
        $current_level =
            $this->modifier->modifier_data[$player->id]['skills'][
                $selected_skill
            ];
        $xp_cost = match ($current_level) {
            0 => 1,
            1 => 3,
            2 => 5,
        };

        PlayerUpgradedSkillInFarm::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            skill_name: $selected_skill,
            xp_cost: $xp_cost,
        );

        Verbs::commit();

        return redirect()->route('games.mods', [
            'game' => $player->game,
            'modifier' => $this->modifier,
        ]);
    }

    public function skillLevels(Player $player)
    {
        return $this->modifier->modifier_data[$player->id]['skills'];
    }

    public function affordableSkills(Player $player)
    {
        $buying_power = $this->modifier->modifier_data[$player->id]['xp'];
        $skills = $this->skillLevels($player);

        $existing_skills = collect($skills)->filter(fn ($level) => $level > 0)->keys()->toArray();

        return collect(self::SKILLS)
            ->map(function ($skill) use ($skills, $buying_power, $existing_skills) {
                $existing_level = $skills[$skill['name']];

                if ($existing_level === 3) {
                    return [];
                }

                if (count($existing_skills) === 2 && $existing_level === 0) {
                    return [];
                }

                $cost = match ($existing_level) {
                    0 => 1,
                    1 => 3,
                    2 => 5,
                };
                $level_label = $existing_level === 0 ? '' : ' +'.$existing_level;

                return [
                    'label' => $skill['name'].$level_label,
                    'value' => $skill['name'],
                    'description' => $skill['level_'.$existing_level + 1].' (cost: '.$cost.' XP)',
                    'disabled' => ($buying_power < $cost),
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    public function onGameStarted(
        GameState $game_state,
        ModifierState $modifier_state,
    ) {
        $game_state->player_ids->each(function ($player_id) use (
            $modifier_state,
        ) {
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

    public function initializePlayerSkills(
        ModifierState $modifier_state,
        int $player_id,
    ) {
        $modifier_state->modifier_data[$player_id] = [
            'xp' => 15,
            'skills' => collect(self::SKILLS)
                ->mapWithKeys(fn ($skill) => [$skill['name'] => 0])
                ->toArray(),
        ];
    }

    public function onChallengeStarted(
        GameState $game_state,
        ChallengeState $challenge_state,
        ModifierState $modifier_state,
    ) {
        $modifier_state->modifier_data = collect($modifier_state->modifier_data)
            ->map(function ($data, $player_id) {
                return [
                    'xp' => $data['xp'] + 1,
                    'skills' => $data['skills'],
                ];
            })
            ->all();
    }

    public function incrementPlayerXp(
        ModifierState $modifier_state,
        int $player_id,
        int $xp,
    ) {
        $modifier_state->modifier_data = collect($modifier_state->modifier_data)
            ->map(function ($data) use ($player_id, $xp) {
                if ($player_id !== $data['player_id']) {
                    return $data;
                }

                return [
                    'xp' => $data['xp'] + $xp,
                    ...$data,
                ];
            })
            ->toArray();
    }
}
