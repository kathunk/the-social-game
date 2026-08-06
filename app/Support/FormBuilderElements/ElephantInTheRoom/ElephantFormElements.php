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

    /**
     * The post-game rematch card: match result plus the opt-in state. Bot
     * games show a single "Play again"; 2-player games show who has opted in
     * and who we're waiting on.
     */
    public function elephantRematch(
        FormBuilder $form,
        Player $player,
        array $modifier_data,
        array $match_data,
        Collection $players,
    ): void {
        $me = (string) $player->id;
        $victors = array_map('strval', $match_data['victor_ids'] ?? []);
        $is_bot_game = $match_data['is_bot_game'] ?? false;

        $names = $players->mapWithKeys(fn ($p) => [(string) $p->id => $p->name])->all();
        $names[ElephantMatch::BOT_ID] = 'The Bot';

        $result_text = match (true) {
            $match_data === [] => 'Game over.',
            $victors === [] => "It's a draw — nobody made their shape.",
            in_array($me, $victors, true) && count($victors) > 1 => "It's a draw — you both made your shape!",
            in_array($me, $victors, true) => 'You won! 🎉',
            default => ($names[$victors[0]] ?? 'Your opponent').' won.',
        };

        $votes = array_map('strval', $modifier_data['rematch_votes'] ?? []);
        $requester_names = collect($votes)
            ->filter(fn ($id) => $id !== $me)
            ->map(fn ($id) => $names[$id] ?? 'Your opponent')
            ->values()
            ->all();
        $waiting_on_names = $players
            ->filter(fn ($p) => (string) $p->id !== $me && ! in_array((string) $p->id, $votes, true))
            ->map(fn ($p) => $p->name)
            ->values()
            ->all();

        $rematch_game_id = $modifier_data['rematch_game_id'] ?? null;

        $form->addElement([
            'type' => 'elephant_rematch',
            'class_key' => \App\Modifiers\ElephantInTheRoom\ElephantRematch::key(),
            'result_text' => $result_text,
            'is_bot_game' => $is_bot_game,
            'i_voted' => in_array($me, $votes, true),
            'requester_names' => $requester_names,
            'waiting_on' => implode(' and ', $waiting_on_names),
            'rematch_url' => $rematch_game_id
                ? route('game-dashboard', ['game' => $rematch_game_id])
                : null,
        ]);
    }
}
