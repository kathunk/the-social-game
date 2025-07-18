<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\User;
use App\Models\Modifier;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Console\Command;
use App\Challenges\Classes\TeamBounty;
use App\Challenges\Classes\PyramidScheme;
use App\Challenges\Classes\StayOnMessage;
use App\Challenges\Classes\TeamHotPotato;
use App\Challenges\Classes\FlattenTheCurve;
use App\Challenges\Classes\TeamBrinksmanship;
use App\Challenges\Classes\TheGreatRealignment;
use App\Challenges\Classes\TeamPrisonersDilemma;

class FakeLaraconActivity extends Command
{
    protected $game;

    protected $teams;

    protected $challenge;

    protected $challenge_handler;

    protected $players;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fake-laracon-activity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Do lots of fake activity for the Laracon US 2025 event';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Verbs::commitImmediately();

        $this->game = Game::where('name', 'Laracon 2025')->get()->last();

        $this->challenge = $this->game->currentChallenge;

        if ($this->challenge === null) {
            return;
        }

        $this->challenge_handler = $this->challenge->handler();

        $this->teams = $this->game->teams;

        $this->addNewUsersToEveryTeam();
        $this->players = $this->game->fresh()->players->where('status', 'active');

        $this->resignSomePlayers();

        $this->players = $this->game->fresh()->players->where('status', 'active');
        $this->takeChallengeActions();
    }

    public function addNewUsersToEveryTeam()
    {
        $new_user_count = rand(0, 100);
        $admin = $this->game->admins->first();

        for ($i = 0; $i < $new_user_count; $i++) {
            $user = User::fromTemplate(
                name: fake()->name(),
                email: fake()->email(),
                encrypted_password: bcrypt('password'),
            );

            $user->requestToJoinGame($this->game);
            $user->admitToGame($this->game, $admin);

            $user->fresh()->currentPlayer->joinTeam($this->teams->random());
        }
    }

    public function resignSomePlayers()
    {
        $players_to_resign = $this->players->count() * 0.1;

        $resignation_modifier = Modifier::where('class_key', 'team_resignation')->first();
        $handler = $resignation_modifier->handler();

        $this->players->shuffle()
            ->reject(fn ($p) => $p->name === 'John Rudolph Drexler')
            ->filter(fn ($p) => $p->team_id !== null)
            ->take($players_to_resign)
            ->each(function ($player) use ($handler) {
                $points = rand(0, 1) === 0 ? -3 : 3;
                $handler->resign($player, ['points' => $points]);
            });
    }

    // @todo add secret codes and secret alliances

    public function takeChallengeActions()
    {
        return match ($this->challenge->class_key) {
            PyramidScheme::key() => $this->takePyramidSchemeActions(),
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

    public function takePyramidSchemeActions()
    {
        $number_of_swappers = $this->players->count() * 0.4;

        $this->players->filter(fn ($p) => $p->team_id !== null)
            ->shuffle()
            ->take($number_of_swappers)
            ->each(function ($player) {
            $new_team = $this->teams->where('id', '!=', $player->team_id)->random();
            $this->challenge_handler->swapTeams($player, ['team_id' => $new_team->id]);
        });
    }

    public function takeStayOnMessageActions()
    {
        $number_of_responders = $this->players->count() * 0.7;

        $this->players->filter(fn ($p) => $p->team_id !== null)
            ->shuffle()
            ->take($number_of_responders)
            ->each(function ($player) {
            $message = rand(1, 0) === 1 
                ? 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' 
                : fake()->sentence(50);

            $this->challenge_handler->submitString($player, ['string_input' => $message]);
        });
    }

    public function takeTeamPrisonersDilemmaActions()
    {
        $number_of_players = $this->players->count() * 0.5;

        $this->players->filter(fn ($p) => $p->team_id !== null)
            ->shuffle()
            ->take($number_of_players)
            ->each(function ($player) {
            $this->challenge_handler->playDirty($player, []);
        });
    }

    public function takeTeamBountyActions()
    {
        $bounty_data = $this->challenge->challenge_data['team_bounties'];

        $bounty_player_ids = collect($bounty_data)->flatten()->all();

        $players = $this->players->filter(fn ($p) => $p->team_id !== null)
            ->whereIn('id', $bounty_player_ids);

        $players->each(function ($player) {
            $new_team = $this->teams->where('id', '!=', $player->team_id)->random();
            $this->challenge_handler->swapTeams($player, ['team_id' => $new_team->id]);
        });
    }

    public function takeFlattenTheCurveActions()
    {
        $number_of_swappers = $this->players->count() * 0.4;

        $this->players->filter(fn ($p) => $p->team_id !== null)
            ->shuffle()
            ->take($number_of_swappers)
            ->each(function ($player) {
            $new_team = $this->teams->where('id', '!=', $player->team_id)->random();
            $this->challenge_handler->swapTeams($player, ['team_id' => $new_team->id]);
        });
    }

    public function takeTeamHotPotatoActions()
    {
        // @todo this is a pain
    }

    public function takeTeamBrinksmanshipActions()
    {
        $this->teams->each(function ($team) {
            $player = $team->players->random();
            $ally_team_id = $this->challenge->challenge_data[$team->id]['ally_team_id'];
            $code = $this->challenge->challenge_data[$ally_team_id]['code'];

            if (rand(1, 0) === 1) {
                $this->challenge_handler->nukeAlly($player, ['target_code' => $code]);
            } else {
                $this->challenge_handler->carpetBomb($player, ['target_code' => $code]);
            }
        });
    }

    public function takeTheGreatRealignmentActions()
    {
        $number_of_swappers = $this->players->count() * 0.4;

        $this->players->filter(fn ($p) => $p->team_id !== null)
            ->shuffle()
            ->take($number_of_swappers)
            ->each(function ($player) {
            $new_team = $this->teams->where('id', '!=', $player->team_id)->random();
            $this->challenge_handler->swapTeams($player, ['team_id' => $new_team->id]);
        });
    }
}
