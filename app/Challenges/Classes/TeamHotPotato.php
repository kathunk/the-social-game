<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use App\States\GameState;
use App\Events\PlayerPassedPotato;
use Illuminate\Support\Collection;

class TeamHotPotato extends BaseChallengeClass
{
    const NAME = 'Hot Potato';

    const DESCRIPTION = 'One player on your team has the hot potato, and can pass it to any teammate. 
        Your team will receive (% of teammates who held the potato - 50%) * 100 points.
        If any player holds it twice the challenge will end, and your team will receive -50 points.';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'team_hot_potato';
    }

    public function isInvalidForTemplate(array $challenges, array $modifiers, string $type, array $team_names)
    {
        $keys_for_first_challenge = collect($challenges)->first()['challenge_keys'];

        if (in_array(static::key(), $keys_for_first_challenge)) {
            return "Hot Potato cannot go first.";
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        $teams = $this->challenge_state->game()->teams()->sortByDesc('score')->pluck('id');

        return $teams->mapWithKeys(fn ($team_id) => [$team_id => [
            'potato_holder_id' => null,
            'remaining_player_ids' => [],
            'all_holder_ids' => [],
            'status' => 'active',
        ]])->toArray();
    }

    public function frontendComponent(Player $player): array
    {
        $form = $this->form()->title(self::NAME)
            ->subtitle(self::DESCRIPTION);

        $challenge_data = $this->challenge->challenge_data[$player->team_id];

        $has_potato = $challenge_data['potato_holder_id'] === $player->id;
        $challenge_failed = $challenge_data['status'] === 'failed';
        $challenge_succeeded = $challenge_data['status'] === 'succeeded';
        $challenge_forfeited = $challenge_data['status'] === 'forfeited';

        $form
            ->when($has_potato, fn ($form) => $form->select(
                property_name: 'recipient_player_id',
                options: $player->team->players->reject(fn ($p) => $p->id === $player->id)->mapWithKeys(fn ($p) => [$p->id => $p->name])->toArray(),
                label: 'Pass the potato to...',
                placeholder: 'Select a player...',
                validation_rules: 'required|in:'.implode(',', $player->team->players->reject(fn ($p) => $p->id === $player->id)->pluck('id')->toArray()),
                validation_messages: [
                    'required' => 'Must select a player',
                    'in' => 'Must select a valid player',
                ],
            )
                ->buttonGroup()
                ->button('Pass the potato', 'passThePotato')
                ->endGroup()
            );

        $form->when($challenge_failed, fn ($form) => $form->subtitle('Sorry folks, try again next time.'));

        $form->when($challenge_succeeded, fn ($form) => $form->subtitle('You did it!'));

        $form->when($challenge_forfeited, fn ($form) => $form->subtitle('Challenge forfeited. No players were on the team when the challenge started.'));

        return $form->build();
    }

    public function onChallengeStarted(GameState $game_state)
    {
        $challenge_state = $this->challenge_state;

        $game_state->teams()->each(function ($team) use ($challenge_state) {
            if ($team->player_ids->count() === 0) {
                $challenge_state->challenge_data[$team->id]['status'] = 'forfeited';

                return;
            }

            $all_player_ids = $team->players()->pluck('id')->toArray();
            $random_player_id = collect($all_player_ids)->random();

            $challenge_state->challenge_data[$team->id]['potato_holder_id'] = $random_player_id;
            $challenge_state->challenge_data[$team->id]['all_holder_ids'][] = $random_player_id;
            $challenge_state->challenge_data[$team->id]['remaining_player_ids'] = array_diff($all_player_ids, [$random_player_id]);
        });
    }

    public function passThePotato(Player $player, array $params): void
    {
        PlayerPassedPotato::fire(
            player_id: $player->id,
            recipient_id: (int) $params['recipient_player_id'],
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
            team_id: $player->team_id,
        );
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $teams_to_resolve = $game_state->teams()
            ->filter(fn ($team) => $this->challenge_state->challenge_data[$team->id]['status'] === 'active');

        $teams_to_resolve->each(function ($team) {
            $team_data = $this->challenge_state->challenge_data[$team->id];

            $held = count($team_data['all_holder_ids']);
            $total = $team->player_ids->count();
            $percentage = $held / $total;
            $points = ($percentage - 0.5) * 100;

            $team->addToScoreHistory(round($points), "Completed the Hot Potato Challenge. $held of $total players held the potato.");
        });
    }
}
