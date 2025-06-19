<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\User;
use Illuminate\Console\Command;

class FillGameWithBots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fill-game-with-bots {game_id} {amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $game = Game::find($this->argument('game_id'));
        $max = ($game->gameTemplate->max_players ?? 1000) - $game->players->count();

        if (! is_numeric($this->argument('amount'))) {
            $this->error('Amount must be an integer');

            return;
        }

        if ($this->argument('amount') > $max) {
            $this->error('Amount cannot be greater than '.$max);

            return;
        }

        if ($this->argument('amount') <= 0) {
            $this->error('Amount must be greater than 0');

            return;
        }

        $bots_needed = $this->argument('amount');
        $requires_approval = $game->requires_admin_approval_to_join;
        $game_admin = $game->admins->first();

        User::where('email', 'like', 'bot%@bot.bot')
            ->limit($bots_needed)
            ->get()
            ->each(function ($user) use ($game, $requires_approval, $game_admin) {
                $user->requestToJoinGame($game);

                if ($requires_approval) {
                    $user->admitToGame($game, $game_admin);
                }
            });
    }
}
