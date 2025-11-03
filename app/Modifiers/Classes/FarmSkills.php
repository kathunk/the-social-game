<?php

namespace App\Modifiers\Classes;

use App\Events\PlayerUpgradedSkillInFarm;
use App\Models\Player;
use App\States\ChallengeState;
use App\States\GameState;
use App\States\ModifierState;
use App\States\PlayerState;
use Illuminate\Support\Str;
use Thunk\Verbs\Facades\Verbs;

class FarmSkills extends BaseModifierClass
{
    const NAME = 'Farm Skills';

    const DESCRIPTION = 'The skills for the farm game.';

    const TYPE = 'team';

    const SKILLS = [
        'brute' => [
            'name' => 'Brute',
            'level_1' => 'Seize and defend Silos and Fields',
            'level_2' => '+2 Seize and defense of structures, and spend fewer actions to seize',
            'level_3' => '+3 Seize and defense of structures, and spend fewer actions to seize',
            'long_description' => "You are strong, and make all of your teammates more powerful. 
                Each of them gains special actions when on a space with you. You can seize and defend 
                Silos and Fields. Currently you have +1 Attack and Defense. By standing on a space with 
                one of your team's structures, you passively add to its defense. In order to seize an 
                opponent's structure, you must have more Attack than it has defense. You also have some 
                special collaborative abilities. If you and an ally Thief are on a space with an oppoenent's 
                field, you may burn it.",
        ],
        'builder' => [
            'name' => 'Builder',
            'level_1' => 'Build Roads, Silos, Watchtowers, and Traps',
            'level_2' => 'Build larger Silos, and spend fewer actions to build',
            'level_3' => 'Build larger Silos, and spend fewer actions to build',
            'long_description' => "You build the infrastructure your team will use to thrive. You can build Silos 
                on your own, which your team can use to stockpile grain. When on a space with an ally Farmer, you 
                can build roads on grass and desert, to move longer distances. When on a space with an ally Brute 
                (strong as an ox), you can build walls that add defense to your team's structures. When on a space
                with an ally Thief, you can build traps that rob opponents of 3 actions. When on a space with an 
                ally Scout, you can build watchtowers that allow teammates to see long distances.",
        ],
        'farmer' => [
            'name' => 'Farmer',
            'level_1' => 'Plant Fields, and spend fewer actions to harvest',
            'level_2' => 'Plant more bountiful Fields, and spend fewer actions to plant and harvest',
            'level_3' => 'Plant more bountiful Fields, and spend fewer actions to plant and harvest',
            'long_description' => "You plant the fields that give your team grain. When on a space with an ally Brute 
                (strong as an ox), you can plant fields in the mountains. When on a space with an ally Builder 
                (experts in irrigation), you can plant fields in the desert. When on a space with an ally Thief and 
                an opponent's field, you may harvest it.",
        ],
        'scout' => [
            'name' => 'Scout',
            'level_1' => 'Inspect up to 2 spaces away, and see recent history of your space.',
            'level_2' => 'Inspect up to 3 spaces away, and see more history of your space.',
            'level_3' => 'Spot hidden caches of grain. Inspect up to 4 spaces away, and see all history of your space.',
            'long_description' => 'You are the keeper of hidden information. When on a space with an ally Thief, 
                you can hide away secret caches of grain invisible to other players. And when you are high enough 
                level, you can spot caches hidden by opponents.',
        ],
        'thief' => [
            'name' => 'Thief',
            'level_1' => 'Pickpocket opponents, and withdraw from opposing silos that have less than 5 defense.',
            'level_2' => 'Spend fewer actions to pickpocket opponents, and withdraw from opposing silos that have less than 7 defense.',
            'level_3' => 'Spend fewer actions to pickpocket opponents, and withdraw from opposing silos that have less than 9 defense.',
            'long_description' => "You are a dirty rotten scoundrel. You can pickpocket opponents, and withdraw from
                opponents' silos (unless there is too much defense). When on a space with an ally Scout, you can 
                hide away secret caches of grain invisible to other players. When on a space with an ally Farmer,
                you can harvest opponents' fields. When on a space with an ally Brute, you can burn opponents' 
                fields",
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

        $form = $this->form()
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
            });

        $skills = $this->skillLevels($player);

        foreach ($skills as $skill_name => $skill_level) {
            $lower_case = Str::snake($skill_name);

            if ($skill_level === 0) {
                continue;
            }

            $form->divider()
                ->title(self::SKILLS[$lower_case]['name'])
                ->subtitle(self::SKILLS[$lower_case]['long_description']);
        }

        return $form->build();
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

        if ($selected_skill === null) {
            throw new \Exception('Selected skill is required');
        }

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
            'xp' => 2,
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
