<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerChosePointsOrHiddenPoints;
use App\Models\Player;
use App\States\GameState;

class IndividualChoosePointsOrHidden extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Choose Points or Hidden Points';

    const DESCRIPTION = 'There are {points} points and {hidden_points} hidden points up for grabs. Players who make the same choice as you will split the points evenly (rounded down).';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_choose_points_or_hidden';
    }

    public function dataArrayForState(): array
    {
        return [
            'choices' => $this->challenge_state->game()->players()->mapWithKeys(fn ($p) => [$p->id => null])->toArray(),
            'votes' => $this->challenge_state->game()->players()->mapWithKeys(fn ($p) => [$p->id => [
                'downvote_player_id' => null,
                'upvote_player_id' => null,
            ]])->toArray(),
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $players = $player->game->players;
        $has_chosen = $this->challenge->challenge_data['choices'][$player->id] !== null;
        $has_voted = $this->challenge->challenge_data['votes'][$player->id]['upvote_player_id'] !== null;

        $player_count = $players->count();

        $description = strtr(self::DESCRIPTION, [
            '{points}' => $player_count * 2,
            '{hidden_points}' => $player_count,
        ]);

        return $this->form()
            ->title(self::NAME)
            ->subtitle($description)
            ->when($has_chosen, fn ($form) => $form->subtitle('You have made your choice.')
            )
            ->when(! $has_chosen, fn ($form) => $form
                ->buttonGroup()
                ->button('Points', 'choose_points')
                ->button('Hidden Points', 'choose_hidden')
                ->endGroup()
            )
            ->when(! $has_chosen || ! $has_voted, fn ($form) => $form->divider()
            )
            ->when($has_voted, fn ($form) => $form->subtitle('You have already voted.')
            )
            ->when(! $has_voted, fn ($form) => $form->peckingOrderBallot(
                upvote_targets: $players->reject(fn ($p) => $p->id === $player->id),
                downvote_targets: $players->reject(fn ($p) => $p->id === $player->id)
            )
            )
            ->build();
    }

    public function choose_points(Player $player, array $params)
    {
        PlayerChosePointsOrHiddenPoints::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            choice: 'points',
        );
    }

    public function choose_hidden(Player $player, array $params)
    {
        PlayerChosePointsOrHiddenPoints::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            choice: 'hidden',
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $this->applyVotesToScore($game_state);

        $player_count = $game_state->player_ids->count();
        $points = $player_count * 2;
        $hidden_points = $player_count;
        $choices = $this->challenge_state->challenge_data['choices'];
        $number_of_players_who_chose_points = collect($choices)->filter(fn ($choice) => $choice === 'points')->count();
        $number_of_players_who_chose_hidden = collect($choices)->filter(fn ($choice) => $choice === 'hidden')->count();
        
        // Handle cases where no players chose either option
        $points_per_player = $number_of_players_who_chose_points > 0 
            ? floor($points / $number_of_players_who_chose_points)
            : 0;
        $hidden_points_per_player = $number_of_players_who_chose_hidden > 0
            ? floor($hidden_points / $number_of_players_who_chose_hidden)
            : 0;

        $game_state->players()->each(function ($player) use ($choices, $points_per_player, $hidden_points_per_player) {
            if ($choices[$player->id] === 'points') {
                $player->addToScoreHistory($points_per_player, 'Chose points');
            } else {
                $player->addToScoreHistory($hidden_points_per_player, 'Chose hidden points', true);
            }
        });
    }
}
