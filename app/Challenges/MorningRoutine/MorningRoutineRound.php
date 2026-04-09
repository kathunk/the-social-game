<?php

namespace App\Challenges\MorningRoutine;

use App\Challenges\BaseChallengeClass;
use App\Events\GameUpdatedForReverb;
use App\Events\MorningRoutine\PlayerEnteredRoom;
use App\Events\MorningRoutine\PlayerExitedRoom;
use App\Models\Player;
use Thunk\Verbs\Facades\Verbs;

class MorningRoutineRound extends BaseChallengeClass
{
    const NAME = 'Morning Routine';

    const DESCRIPTION = 'Navigate the hallway and rooms before time runs out.';

    const TYPE = 'individual';

    const HIDE_SCOREBOARD = true;

    const ROOMS = ['bathroom', 'laundry', 'study', 'kitchen'];

    public static function key(): string
    {
        return 'morning_routine_round';
    }

    public function dataArrayForState(): array
    {
        $players = $this->challenge->game->players;

        // Everyone starts in the hallway
        $player_locations = $players->mapWithKeys(fn ($player) => [
            $player->id => 'hallway',
        ])->toArray();

        return [
            'player_locations' => $player_locations,
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $data = $this->challenge->challenge_data;
        $player_locations = $data['player_locations'] ?? [];
        $current_location = $player_locations[$player->id] ?? 'hallway';

        return $this->form()
            ->morningRoutine(
                player: $player,
                current_location: $current_location,
                player_locations: $player_locations,
                rooms: self::ROOMS,
                players: $this->challenge->game->players,
            )
            ->poll(2000)
            ->build();
    }

    public function enterRoom(Player $player, array $params): void
    {
        $room = $params['room'] ?? null;
        $data = $this->challenge->challenge_data;
        $player_locations = $data['player_locations'] ?? [];
        $current_location = $player_locations[$player->id] ?? 'hallway';

        if ($current_location !== 'hallway') {
            throw new \RuntimeException('You must be in the hallway to enter a room.');
        }

        if (! in_array($room, self::ROOMS)) {
            throw new \RuntimeException('Invalid room.');
        }

        // Check if room is occupied
        $occupant = collect($player_locations)->first(fn ($loc) => $loc === $room);
        if ($occupant !== null) {
            throw new \RuntimeException('That room is occupied.');
        }

        PlayerEnteredRoom::fire(
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
            player_id: $player->id,
            room: $room,
        );

        Verbs::commit();

        event(new GameUpdatedForReverb($player->game->fresh()));
    }

    public function exitRoom(Player $player, array $params): void
    {
        $data = $this->challenge->challenge_data;
        $player_locations = $data['player_locations'] ?? [];
        $current_location = $player_locations[$player->id] ?? 'hallway';

        if ($current_location === 'hallway') {
            throw new \RuntimeException('You are already in the hallway.');
        }

        PlayerExitedRoom::fire(
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
            player_id: $player->id,
        );

        Verbs::commit();

        event(new GameUpdatedForReverb($player->game->fresh()));
    }
}
