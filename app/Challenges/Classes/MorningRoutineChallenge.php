<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use App\Events\PlayerMovedRoomsInMorningRoutine;
use App\Modifiers\Classes\MorningRoutineProgress;

class MorningRoutineChallenge extends BaseChallengeClass
{
    const NAME = 'Morning Routine';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'morning_routine';
    }

    public function isInvalidForTemplate(
        array $challenge_keys,
        array $modifier_keys,
        string $type,
        array $team_names
    ) {
        if (! in_array(MorningRoutineProgress::key(), $modifier_keys)) {
            return 'Morning Routine Progress modifier is required to run this challenge';
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        return [
            'has_moved' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        return $this->form()
            ->title(self::NAME)
            ->subtitle('You are in the ' . $this->currentRoom($player) . '.')
            ->when($this->canMove($player), fn ($form) => $form->roomMove($this->availableRooms($player))->divider())
            ->when($this->canTakeActionInCurrentRoom($player), fn ($form) => $form->actionsForCurrentRoom($player))
            ->build();
    }

    public function canMove(Player $player): bool
    {
        $empty_rooms = collect($this->progress($player)['rooms'])->filter(fn ($room) => $room === null)->keys();

        return ! in_array($player->id, $this->challenge->challenge_data['has_moved']) && $empty_rooms->isNotEmpty();
    }

    public function canTakeActionInCurrentRoom(Player $player): bool
    {
        $current_room = $this->currentRoom($player);

        if ($current_room === 'Hallway') {
            return true;
        }

        return $this->progress($player)['players'][$player->id]['items'][$current_room] === null;
    }

    // am I just essentially putting stuff that belongs in gameState onto a catchall modifier?
    public function blackboard(): array
    {
        return $this->challenge->game->modifiers->firstWhere('class_key', MorningRoutineProgress::key())
            ->modifier_data;
    }

    public function rooms(): array 
    {
        return $this->challenge->game
    }

    public function progress(Player $player): array
    {

    }

    public function move(Player $player, array $params)
    {
        PlayerMovedRoomsInMorningRoutine::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            room: $params['room'],
        );
    }

    public function currentRoom(Player $player): string
    {
        $room = collect($this->challenge->challenge_data['rooms'])->filter(fn ($room) => $room === $player->id)->keys()->first();

        return $room ?? 'Hallway';
    }

    public function availableRooms(Player $player): array
    {
        $current_room = $this->currentRoom($player);

        if ($current_room === 'Hallway') {
            return collect(array_keys($this->challenge->challenge_data['rooms']))
                ->filter(fn ($room) => $this->challenge->challenge_data['rooms'][$room] === null)
                ->mapWithKeys(fn ($room) => [$room => $room])
                ->toArray();
        }

        return ['Hallway' => 'Hallway'];
    }
}
