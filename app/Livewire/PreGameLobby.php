<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Player;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;
use App\Support\HtmlTransformer;
use Livewire\Attributes\Computed;
use App\Events\UserPromotedToGameAdmin;

class PreGameLobby extends Component
{
    public Game $game;

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function players()
    {
        return $this->game->players()->with('user')->get();
    }

    #[Computed]
    public function player()
    {
        return $this->game->players->where('user_id', $this->user->id)->first();
    }

    #[Computed]
    public function admins()
    {
        return $this->game->admins;
    }

    #[Computed]
    public function application()
    {
        return $this->user?->gameApplications->where('game_id', $this->game->id)->first();
    }

    #[Computed]
    public function is_super_admin()
    {
        return $this->user->is_super_admin;
    }

    #[Computed]
    public function is_game_admin()
    {
        return $this->user->is_admin($this->game);
    }

    #[Computed]
    public function requires_admin_approval_to_join()
    {
        return $this->game->requires_admin_approval_to_join;
    }

    #[Computed]
    public function is_joinable()
    {
        return $this->game->is_joinable;
    }

    #[Computed]
    public function description()
    {
        return (new HtmlTransformer($this->game->gameTemplate->pre_game_lobby_message))->formatted();
    }

    public function mount(Game $game)
    {
        $this->game = $game;

        if (! $this->is_joinable) {
            return redirect()->route('home');
        }

        if ($this->application) {
            $this->checkStatus();
        }
    }

    public function checkStatus()
    {
        if (!$this->application) {
            return;
        }

        unset($this->application);

        if ($this->application->status === 'accepted' && $this->game->status === 'active') {
            return redirect()->route('game-dashboard', ['game' => $this->game]);
        }
    }

    public function joinGame()
    {
        $this->user->requestToJoinGame($this->game);

        Verbs::commit();

        unset($this->application);
        unset($this->player);
    }

    public function removePlayer(string $player_id)
    {
        //
    }

    public function promoteToAdmin(string $player_id)
    {
        $player = Player::find($player_id);

        UserPromotedToGameAdmin::fire(
            user_id: $player->user_id,
            game_id: $this->game->id,
        );

        Verbs::commit();
        unset($this->admins);
    }

    public function render()
    {
        return view('livewire.pre-game-lobby');
    }
}
