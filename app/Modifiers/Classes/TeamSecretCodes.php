<?php

namespace App\Modifiers\Classes;

use App\Models\User;
use App\Models\Player;
use App\Events\PlayerInputSecretCode;

class TeamSecretCodes extends BaseModifierClass
{
    const NAME = 'Secret code';

    const DESCRIPTION = "If you find a secret code, input it here to receive 1 hidden point for your team.
        Spammers beware: if you input an invalid code, or a code that has already been used,
        you will not be able to input any more codes for the rest of the game.";

    const TYPE = 'team';

    const REQUIRES_PRE_GAME_SETUP = true;

    public static function key(): string
    {
        return 'team_secret_codes';
    }

    public function dataArrayForState(): array
    {
        return [
            'unused_codes' => [],
            'used_codes' => [],
            'banned_player_ids' => [],
        ];
    }

    public function frontendComponentForSetup(User $user): array
    {
        if ($this->modifier->game->status === 'upcoming') {
            return $this->form()
                ->title('Secret codes')
                ->subtitle('Add secret codes, separated by commas. Each code must be unique and cannot be more than 100 characters. You will not be able to see or change these codes once the game begins.')
                ->input(
                    property_name: 'codes',
                    validation_rules: 'required|array|min:1',
                    validation_messages: [
                        'required' => 'You must add at least one code.',
                        'array' => 'Codes must be separated by commas.',
                        'min' => 'You must add at least one code.',
                    ]
                )->build();
        }

        return [];
    }

    public function frontendComponentForDedicatedPage(Player $player): array
    {
        $is_allowed_to_submit = ! collect($this->modifier->modifier_data['banned_player_ids'])->contains($player->id);

        $banned_component = $this->form()
            ->title('Naughty Naughty')
            ->subtitle('You submitted an invalid code. You cannot submit codes for the rest of the game.')
            ->build();

        $input_component = $this->form()
            ->title(static::NAME)
            ->subtitle(static::DESCRIPTION)
            ->input(
                property_name: 'code_input',
                validation_rules: 'required|max:100',
                validation_messages: [
                    'required' => 'You must input a code.',
                    'max' => 'The code is too long.',
                ]
            )
            ->buttonGroup()
            ->button(
                label: 'Submit',
                action: 'submit_code',
                properties_to_validate: ['code_input'],
            )
            ->endGroup()
            ->build();

        return $is_allowed_to_submit ? $input_component : $banned_component;
    }

    public function submit_code(Player $player, array $params)
    {
        PlayerInputSecretCode::fire(
            player_id: $player->id,
            code: $params['code_input'],
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            game_type: 'team',
            point_reward: 1,
            points_are_hidden: true,
            team_id: $player->team_id,
        );
    }
}