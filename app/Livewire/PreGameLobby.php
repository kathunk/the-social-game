<?php

namespace App\Livewire;

use Flux\Flux;
use App\Models\Game;
use App\Models\Player;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;
use App\Models\GameApplication;
use App\Support\HtmlTransformer;
use Livewire\Attributes\Computed;
use App\Events\UserAdmittedToGame;
use App\Events\UserRejectedFromGame;
use App\Events\PlayerRemovedFromGame;
use App\Events\UserPromotedToGameAdmin;
use App\Events\UserDemotedFromGameAdmin;

class PreGameLobby extends Component
{
    public Game $game;

    public string $selected_application_id = '';

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function players()
    {
        return $this->game->players()->where('status', 'active')->with('user')->get();
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
    public function creator()
    {
        return $this->game->admins->first();
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
        return $this->user?->isGameAdmin($this->game);
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

    #[Computed]
    public function newApplications()
    {
        return GameApplication::with('user')
            ->where([
                'game_id' => $this->game->id,
                'status' => 'pending',
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function acceptedUserNames()
    {
        return $this->game->players->map(fn ($player) => $player->user->name);
    }

    public function mount(Game $game)
    {
        $this->game = $game;

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
        $player = Player::find($player_id);

        PlayerRemovedFromGame::fire(
            player_id: $player->id,
            user_id: $player->user_id,
            game_id: $this->game->id,
            admin_id: $this->user->id,
            application_id: $player->user->gameApplications->firstWhere('game_id', $this->game->id)->id,
        );

        Verbs::commit();
        unset($this->players);
    }

    public function promoteToAdmin(string $player_id)
    {
        $player = Player::find($player_id);

        UserPromotedToGameAdmin::fire(
            user_id: $player->user_id,
            game_id: $this->game->id,
            admin_id: $this->user->id,
        );

        Verbs::commit();
        unset($this->admins);
    }

    public function demoteFromAdmin(string $player_id)
    {
        $player = Player::find($player_id);

        UserDemotedFromGameAdmin::fire(
            user_id: $player->user_id,
            game_id: $this->game->id,
            admin_id: $this->user->id,
        );

        Verbs::commit();
        unset($this->admins);
    }

    public function approveUser()
    {
        $application = $this->newApplications->firstWhere('id', (int) $this->selected_application_id);

        UserAdmittedToGame::fire(
            user_id: (int) $application->user_id,
            admin_id: $this->user->id,
            game_id: $this->game->id,
            application_id: $application->id,
        );

        $this->selected_application_id = '';
        unset($this->newApplications);

        Flux::toast(variant: 'success', heading: 'User approved', text: 'The user has been approved to join the game.');
    }

    public function rejectUser()
    {
        $application = $this->newApplications->firstWhere('id', (int) $this->selected_application_id);

        UserRejectedFromGame::fire(
            user_id: (int) $application->user_id,
            admin_id: $this->user->id,
            game_id: $this->game->id,
            application_id: $application->id,
        );

        $this->selected_application_id = '';
        unset($this->newApplications);

        Flux::toast(variant: 'success', heading: 'User rejected', text: 'The user has been rejected from the game.');
    }

    public function render()
    {
        return view('livewire.pre-game-lobby');
    }
}
