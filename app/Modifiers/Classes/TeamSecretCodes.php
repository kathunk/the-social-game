<?php

namespace App\Modifiers\Classes;

use App\Events\PlayerAssignedSecretAllyInTeamGame;
use App\Models\Modifier;
use App\Models\Player;
use App\States\GameState;
use App\States\ModifierState;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Facades\Verbs;

class TeamSecretCodes extends BaseModifierClass
{
    const NAME = 'Secret code';

    const DESCRIPTION = "If you find a secret code, input it here to receive 1 hidden point for your team.
        Spammers beware: if you input an invalid code, or a code that has already been used,
        you will not be able to input any more codes for the rest of the game.";

    const TYPE = 'team';

    public static function key(): string
    {
        return 'team_secret_alliance';
    }

    public function dataArrayForState(): array
    {
        return [
            'unused_codes' => [],
            'used_codes' => [],
            'banned_player_ids' => [],
        ];
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
            hidden_point_reward: 1,
            point_reward: 0,
            team_id: $player->team_id,
        );
    }
