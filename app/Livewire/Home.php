<?php

namespace App\Livewire;

use App\Events\GameCanceled;
use App\Events\PlayerAbandonedGame;
use App\Events\UserSwitchedCurrentGame;
use App\Models\Game;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;

class Home extends Component
{
    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function games()
    {
        $statusOrder = [
            'active' => 1,
            'upcoming' => 2,
            'ended' => 3,
            'canceled' => 4,
        ];

        return $this->user->games()
            ->orderByRaw("CASE 
                WHEN games.status = 'active' THEN 1
                WHEN games.status = 'upcoming' THEN 2
                WHEN games.status = 'ended' THEN 3
                WHEN games.status = 'canceled' THEN 4
                ELSE 5
            END")
            ->orderBy('starts_at', 'desc')
            ->get();
    }

    public function goToGame(string $game_id)
    {
        $game = Game::find($game_id);

        if ($this->user->current_game_id !== (int) $game_id) {
            UserSwitchedCurrentGame::fire(
                user_id: $this->user->id,
                player_id: $game->players->firstWhere('user_id', $this->user->id)->id,
                game_id: $game->id,
            );
        }

        return redirect()->route('game-dashboard', ['game' => $game]);
    }

    public function cancelGame(string $game_id)
    {
        $game = Game::find($game_id);

        GameCanceled::fire(
            game_id: $game->id,
            admin_id: $this->user->id,
        );

        Verbs::commit();

        return redirect()->route('dashboard');
    }

    public function abandonGame(string $game_id)
    {
        $game = Game::find($game_id);

        PlayerAbandonedGame::fire(
            player_id: $this->user->current_player_id,
            game_id: $game->id,
            user_id: $this->user->id,
        );

        Verbs::commit();

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.home');
    }
}
