<?php

namespace App\Modifiers\Classes;

use App\Events\PlayerInputSecretCode;
use App\Models\Player;
use Thunk\Verbs\Facades\Verbs;

class TeamSecretCodes extends BaseModifierClass
{
    const NAME = 'Secret code';

    const DESCRIPTION = 'If you find a secret code, input it here to receive 1 hidden point for your team.
        Spammers beware: if you input an invalid code, or a code that has already been used,
        you will not be able to input any more codes for the rest of the game.';

    const TYPE = 'team';

    const REQUIRES_PRE_GAME_CONFIGURATION = true;

    const DEFAULT_CONFIGURATION = [
        'unused_codes' => ['1234567890', '0987654321'],
        'used_codes' => [],
        'banned_player_ids' => [],
    ];

    public static function key(): string
    {
        return 'team_secret_codes';
    }

    public function frontendComponentForDedicatedPage(Player $player): array
    {
        if ($player->team_id === null) {
            return $this->form()
                ->title('Secret codes')
                ->subtitle('Join a team to submit secret codes.')
                ->build();
        }

        $is_allowed_to_submit = ! collect($this->modifier->modifier_data['banned_player_ids'])->contains($player->id);

        $banned_component = $this->form()
            ->title('Naughty Naughty...')
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

        Verbs::commit();

        return redirect('secret-code');
    }
}
