<?php

namespace App\Console\Commands;

use App\Jobs\AddFakeUserToLaraconGame;
use App\Jobs\ResignFakePlayerFromLaraconGame;
use App\Jobs\TakeChallengeActionInFakeLaraconGame;
use App\Models\Game;
use App\Models\Modifier;
use App\Modifiers\Classes\Laracon2025\TeamSecretAlliance;
use Illuminate\Console\Command;
use Thunk\Verbs\Facades\Verbs;

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
    protected $signature = 'app:fake-laracon-activity {game_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Do lots of fake activity for a Laracon game';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $game_id = $this->argument('game_id');

        $this->game = Game::findOrFail($game_id);

        $this->challenge = $this->game->currentChallenge;

        if ($this->challenge === null) {
            $this->error('No active challenge found for this game.');

            return;
        }

        $this->challenge_handler = $this->challenge->handler();

        $this->teams = $this->game->teams;

        $this->addNewUsersToEveryTeam();
        Verbs::commit();
        $this->players = $this->game->fresh()->players->where('status', 'active');

        $this->resignSomePlayers();
        Verbs::commit();
        $this->players = $this->game->fresh()->players->where('status', 'active');

        $this->players->each(function ($player) {
            TakeChallengeActionInFakeLaraconGame::dispatch(
                player: $player,
                challenge: $this->challenge,
                game: $this->game,
            );
        });
    }

    public function addNewUsersToEveryTeam()
    {
        $new_user_count = rand(0, 100);
        $admin = $this->game->admins->first();
        $secret_alliance_modifier = Modifier::where('class_key', TeamSecretAlliance::key())->first();

        for ($i = 0; $i < $new_user_count; $i++) {
            AddFakeUserToLaraconGame::dispatch(
                team: $this->teams->random(),
                admin: $admin,
                game: $this->game,
                secret_alliance_modifier: $secret_alliance_modifier,
            );
        }
    }

    public function resignSomePlayers()
    {
        $players_to_resign = $this->players->count() * 0.1;

        $resignation_modifier = Modifier::where('class_key', 'team_resignation')->first();

        $this->players->shuffle()
            ->reject(fn ($p) => $p->name === 'John Rudolph Drexler')
            ->filter(fn ($p) => $p->team_id !== null)
            ->take($players_to_resign)
            ->each(function ($player) use ($resignation_modifier) {
                ResignFakePlayerFromLaraconGame::dispatch(
                    player: $player,
                    resignation_modifier: $resignation_modifier,
                );
            });
    }

    // @todo add secret codes
}
