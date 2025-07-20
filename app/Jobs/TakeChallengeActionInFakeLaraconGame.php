<?php

namespace App\Jobs;

use App\Challenges\Classes\FlattenTheCurve;
use App\Challenges\Classes\StayOnMessage;
use App\Challenges\Classes\TeamBounty;
use App\Challenges\Classes\TeamBrinksmanship;
use App\Challenges\Classes\TeamHotPotato;
use App\Challenges\Classes\TeamPrisonersDilemma;
use App\Challenges\Classes\TeamWarmUp;
use App\Challenges\Classes\TheGreatRealignment;
use App\Models\Challenge;
use App\Models\Game;
use App\Models\Player;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class TakeChallengeActionInFakeLaraconGame implements ShouldQueue
{
    use Queueable;

    public $teams;

    public $challenge_handler;

    public function __construct(
        public Player $player,
        public Game $game,
        public Challenge $challenge,
    ) {
        $this->teams = $this->game->fresh()->teams;
        $this->challenge_handler = $this->challenge->handler();
    }

    public function handle(): void
    {
        match ($this->challenge->class_key) {
            TeamWarmUp::key() => $this->takeTeamWarmUpActions(),
            StayOnMessage::key() => $this->takeStayOnMessageActions(),
            TeamPrisonersDilemma::key() => $this->takeTeamPrisonersDilemmaActions(),
            TeamBounty::key() => $this->takeTeamBountyActions(),
            FlattenTheCurve::key() => $this->takeFlattenTheCurveActions(),
            TeamHotPotato::key() => $this->takeTeamHotPotatoActions(),
            TeamBrinksmanship::key() => $this->takeTeamBrinksmanshipActions(),
            TheGreatRealignment::key() => $this->takeTheGreatRealignmentActions(),
            default => null,
        };
    }

    public function takeTeamWarmUpActions()
    {
        if (rand(1, 10) > 5) {
            return;
        }

        $new_team = $this->teams
            ->where('id', '!=', $this->player->team_id)
            ->random();
        $this->challenge_handler->swapTeams($this->player, [
            'team_id' => $new_team->id,
        ]);
    }

    public function takeStayOnMessageActions()
    {
        if (rand(1, 10) > 9) {
            return;
        }

        $message = rand(1, 10) > 3
                ? 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
                : Str::random(50);

        $this->challenge_handler->submitString($this->player, [
            'string_input' => $message,
        ]);
    }

    public function takeTeamPrisonersDilemmaActions()
    {
        if (rand(1, 10) > 4) {
            return;
        }

        $this->challenge_handler->playDirty($this->player, []);
    }

    public function takeTeamBountyActions()
    {
        $bounty_data = $this->challenge->challenge_data['team_bounties'];

        $bounty_player_ids = collect($bounty_data)->flatten()->all();

        if (! in_array($this->player->id, $bounty_player_ids)) {
            return;
        }

        $new_team = $this->teams
            ->where('id', '!=', $this->player->team_id)
            ->random();
        $this->challenge_handler->swapTeams($this->player, [
            'team_id' => $new_team->id,
        ]);
    }

    public function takeFlattenTheCurveActions()
    {
        if (rand(1, 10) > 4) {
            return;
        }

        $new_team = $this->teams
            ->where('id', '!=', $this->player->team_id)
            ->random();
        $this->challenge_handler->swapTeams($this->player, [
            'team_id' => $new_team->id,
        ]);
    }

    public function takeTeamHotPotatoActions()
    {
        $potato_holder =
            $this->challenge->challenge_data[$this->player->team_id][
                'potato_holder_id'
            ];

        if ($potato_holder !== $this->player->id) {
            return;
        }

        $remaining_player_ids =
            $this->challenge->challenge_data[$this->player->team_id][
                'remaining_player_ids'
            ];

        $recipient_player_id = collect($remaining_player_ids)->random();

        $this->challenge_handler->passThePotato($this->player, [
            'recipient_player_id' => $recipient_player_id,
        ]);
    }

    public function takeTeamBrinksmanshipActions()
    {
        $has_taken_action =
            $this->challenge->challenge_data[$this->player->team_id][
                'has_launched'
            ];

        if ($has_taken_action) {
            return;
        }

        $ally_team_id =
            $this->challenge->challenge_data[$this->player->team_id][
                'ally_team_id'
            ];
        $code = $this->challenge->challenge_data[$ally_team_id]['code'];

        if (rand(0, 1) === 1) {
            $this->challenge_handler->nukeAlly($this->player, [
                'target_code' => $code,
            ]);
        } else {
            $this->challenge_handler->carpetBomb($this->player, [
                'target_code' => $code,
            ]);
        }
    }

    public function takeTheGreatRealignmentActions()
    {
        if (rand(1, 10) > 6) {
            return;
        }

        $new_team = $this->teams
            ->where('id', '!=', $this->player->team_id)
            ->random();
        $this->challenge_handler->swapTeams($this->player, [
            'team_id' => $new_team->id,
        ]);
    }
}
