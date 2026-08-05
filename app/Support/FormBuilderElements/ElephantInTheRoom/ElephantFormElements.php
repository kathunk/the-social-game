<?php

namespace App\Support\FormBuilderElements\ElephantInTheRoom;

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use App\Models\Player;
use App\Support\FormBuilder;
use App\Support\FormElementProvider;
use Illuminate\Support\Collection;

class ElephantFormElements implements FormElementProvider
{
    public function elephantBoard(
        FormBuilder $form,
        Player $player,
        array $challenge_data,
        Collection $players,
        int $game_id,
    ): void {
        $names = $players->mapWithKeys(fn ($p) => [(string) $p->id => $p->name])->all();

        if ($challenge_data['is_bot_game'] ?? false) {
            $names[ElephantMatch::BOT_ID] = 'The Bot';
        }

        $handler = $form->challenge_class;

        $form->addElement([
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
        ]);
    }
}
