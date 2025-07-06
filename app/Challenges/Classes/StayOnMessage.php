<?php

namespace App\Challenges\Classes;

use App\Events\PlayerSubmittedStayOnMessage;
use App\Models\Player;
use App\States\GameState;

class StayOnMessage extends BaseChallengeClass
{
    const NAME = 'Stay on message';

    const DESCRIPTION = "Let's see how well you can work together. You have 1 hour to submit any 50 character string in the field below. At the end of the challenge, we will find your team's most commonly submitted string. Your team will receive (% of teammates who submitted your most popular string - 50%) * 100 points.";

    const TYPE = 'team';

    public static function key(): string
    {
        return 'staying_on_message';
    }

    public function dataArrayForState(): array
    {
        return $this->challenge_state->game()->teams()->mapWithKeys(fn ($t) => [$t->id => []])->toArray();
    }

    public function frontendComponent(Player $player): array
    {
        if (isset($this->challenge->challenge_data[$player->team_id][$player->id])) {
            return $this->form()
                ->title('Stay on message')
                ->subtitle('Your submission: '.$this->challenge->challenge_data[$player->team_id][$player->id])
                ->build();
        }

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->input(
                property_name: 'string_input',
                validation_rules: 'required|min:50|max:50',
                validation_messages: [
                    'required' => 'I assure you, 0 is less than 50',
                    'min' => "Don't be nervous. We need at least 50 characters from you.",
                    'max' => "Easy there John Updike, we don't need a novel",
                ]
            )
            ->buttonGroup()
            ->button(
                label: 'Submit',
                action: 'submitString',
                properties_to_validate: ['string_input'],
            )
            ->endGroup()
            ->build();
    }

    public function submitString(Player $player, array $params)
    {
        PlayerSubmittedStayOnMessage::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
            team_id: $player->team_id,
            message: $params['string_input'],
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $game_state->teams()->each(function ($team) {
            $member_count = $team->player_ids->count();

            if ($member_count === 0) {
                return;
            }

            $messages = $this->challenge_state->challenge_data[$team->id];

            $most_common_message = array_count_values($messages) ?: [];
            $most_common_message = ! empty($most_common_message) ? array_keys($most_common_message, max($most_common_message))[0] : null;

            $count = empty($messages) ? 0 : max(array_count_values($messages));

            $points = (($count / $member_count) - 0.5) * 100;

            if ($points >= 0) {
                $team->addToScoreHistory((int) round($points, 0), '💬 Stayed on message');
            } else {
                $team->addToScoreHistory((int) round($points, 0), '💬 Failed to stay on message');
            }
        });
    }
}
