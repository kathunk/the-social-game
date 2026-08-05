<?php

namespace App\Support\FormBuilderTraits\ElephantInTheRoom;

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use App\Models\Player;
use Illuminate\Support\Collection;

trait ElephantFormElements
{
    public function elephantBoard(
        Player $player,
        array $challenge_data,
        Collection $players,
        int $game_id,
    ): static {
        $names = $players->mapWithKeys(fn ($p) => [(string) $p->id => $p->name])->all();

        if ($challenge_data['is_bot_game'] ?? false) {
            $names[ElephantMatch::BOT_ID] = 'The Bot';
        }

        $handler = $this->challenge_class;

        $this->elements[] = [
            'type' => 'elephant_board',
            'class_key' => ElephantMatch::key(),
            'me' => (string) $player->id,
            'names' => $names,
            'game_id' => (string) $game_id,
            'turn_seconds' => ElephantMatch::TURN_SECONDS,
            'forfeit_grace_seconds' => ElephantMatch::FORFEIT_GRACE_SECONDS,
            'state' => $handler->stateSnapshot($challenge_data),
            // Recent tail of the move log so a catching-up client can animate
            // what it missed instead of snapping
            'moves' => array_slice($challenge_data['moves'] ?? [], -6),
        ];

        return $this;
    }
}
