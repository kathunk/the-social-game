<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\Challenges\Classes\MorningRoutineChallenge;
use App\Events\PlayerLeftHouseInMorningRoutine;

class MorningRoutineProgress extends BaseModifierClass
{
    const NAME = 'To Do';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'morning_routine_progress';
    }

    public function isInvalidForTemplate(
        array $challenge_keys,
        array $modifier_keys,
        string $type,
        array $team_names
    ) {
        if (! collect($challenge_keys)->flatten()->contains(MorningRoutineChallenge::key())) {
            return 'Morning Routine challenge is required to run this modifier';
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        $data = [
            'rooms' => [
                'Kitchen' => null,
                'Bathroom' => null,
                'Laundry' => null,
                'Study' => null,
            ],
            'players' => $this->modifier_state->game()->player_ids->mapWithKeys(fn ($player_id) => [
                $player_id => [
                    'items' => [
                        'Kitchen' => null,
                        'Bathroom' => null,
                        'Laundry' => null,
                        'Study' => null,
                    ],
                ],
            ])->toArray(),
        ];

        return $data;
    }

    public function frontendComponent(Player $player): array
    {
        $rooms = collect($this->modifier->modifier_data[$player->id])
            ->map(function ($room, $key) {
                return [
                    'Room' => $key,
                    'Status' => $room ? '✅' : '❌',
                ];
            })
            ->toArray();

        $done = collect($rooms)->filter(fn ($room) => $room['Status'] === '✅')->count() === count($rooms);

        return $this->form()
            ->title('To Do')
            ->subtitle('Do your morning routine in each of these rooms before you can leave the house.')
            ->table(headers: ['Room', 'Status'], rows: $rooms)
            ->when($done, function ($form) {
                return $form->buttonGroup()->button(
                    label: 'Leave the house',
                    action: 'leave',
                    properties_to_validate: [],
                )->endGroup();
            })
            ->build();
    }

    public function leave(Player $player, array $params)
    {
        PlayerLeftHouseInMorningRoutine::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            challenge_id: $this->modifier->game->current_challenge_id,
            modifier_id: $this->modifier->id,
        );
    }
}
