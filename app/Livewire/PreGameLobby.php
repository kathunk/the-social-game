<?php

namespace App\Livewire;

use App\Events\GameUpdated;
use App\Events\PlayerRemovedFromGame;
use App\Events\UserAdmittedToGame;
use App\Events\UserDemotedFromGameAdmin;
use App\Events\UserPromotedToGameAdmin;
use App\Events\UserRejectedFromGame;
use App\Models\Game;
use App\Models\GameApplication;
use App\Models\GameTemplate;
use App\Models\Player;
use App\Support\HtmlTransformer;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;

class PreGameLobby extends Component
{
    public Game $game;

    public string $selected_application_id = '';

    public Carbon $game_start_timecode;

    public string $game_template_id;

    public bool $is_public;

    public bool $requires_admin_approval_to_join;

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function gameTemplates()
    {
        return GameTemplate::all()
            ->sortBy('name')
            ->filter(function ($template) {
                return $this->user->is_super_admin || $template->is_public;
            });
    }

    #[Computed]
    public function players()
    {
        return $this->game->players()
            ->where('status', '!=', 'rejected')
            ->where('status', '!=', 'removed')
            ->with('user')
            ->get()
            ->sort(function ($a, $b) {
                // First compare admin status
                $aIsAdmin = $this->admins->pluck('id')->contains($a->user_id);
                $bIsAdmin = $this->admins->pluck('id')->contains($b->user_id);

                if ($aIsAdmin !== $bIsAdmin) {
                    return $aIsAdmin ? -1 : 1;
                }

                // If admin status is the same, sort by name
                return strcasecmp($a->name, $b->name);
            })
            ->values();
    }

    #[Computed]
    public function player()
    {
        return $this->players->where('user_id', $this->user->id)->first();
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

    #[Computed]
    public function hasTooManyPlayers()
    {
        $max = $this->game->gameTemplate->max_players;

        return $max && $this->players->count() > $max;
    }

    #[Computed]
    public function hasTooFewPlayers()
    {
        $min = $this->game->gameTemplate->min_players;

        return $min && $this->players->count() < $min;
    }

    public function mount(Game $game)
    {
        $this->game = $game;
        $this->game_template_id = (string) $game->gameTemplate->id;
        $this->game_start_timecode = $game->starts_at;
        $this->is_public = $game->is_public;
        $this->requires_admin_approval_to_join = $game->requires_admin_approval_to_join;

        if ($this->application) {
            $this->checkStatus();
        }
    }

    public function checkStatus()
    {
        if (! $this->application) {
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

        Flux::toast(variant: 'success', heading: 'User removed', text: $player->user->name.' has been removed from the game.');
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

        Flux::toast(variant: 'success', heading: 'User promoted', text: $player->user->name.' has been promoted to admin.');
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

        Flux::toast(variant: 'success', heading: 'User demoted', text: $player->user->name.' has been demoted from admin.');
    }

    public function approveUser()
    {
        $this->validate([
            'selected_application_id' => 'required|exists:game_applications,id',
        ]);

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
        $this->validate([
            'selected_application_id' => 'required|exists:game_applications,id',
        ]);

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

    public function updateGameSettings()
    {
        $this->validate([
            'game_template_id' => 'required|exists:game_templates,id',
            'game_start_timecode' => 'required|date',
        ]);

        $duration = GameTemplate::find($this->game_template_id)->total_duration;
        $ends_at = Carbon::parse($this->game_start_timecode)->addMinutes($duration);

        GameUpdated::fire(
            game_id: $this->game->id,
            user_id: $this->user->id,
            game_template_id: (int) $this->game_template_id,
            starts_at: $this->game_start_timecode,
            ends_at: $ends_at,
            is_public: $this->is_public,
            requires_admin_approval_to_join: $this->requires_admin_approval_to_join,
        );

        Verbs::commit();

        return redirect()->route('pre-game-lobby', $this->game->id);
    }

    public function startGame()
    {
        $duration = GameTemplate::find($this->game_template_id)->total_duration;
        $ends_at = Carbon::parse(now())->addMinutes($duration);

        GameUpdated::fire(
            game_id: $this->game->id,
            user_id: $this->user->id,
            game_template_id: (int) $this->game_template_id,
            starts_at: now(),
            ends_at: $ends_at,
            is_public: $this->is_public,
            requires_admin_approval_to_join: $this->requires_admin_approval_to_join,
        );

        Verbs::commit();

        $this->game->fresh()->start();

        Verbs::commit();

        return redirect()->route('game-dashboard', $this->game->id);
    }

    public function cancelGame()
    {
        $this->game->cancel($this->user);

        Verbs::commit();

        return redirect()->route('dashboard');
    }

    #[On('echo-private:games.{game.id},GameUpdatedForReverb')]
    public function refreshGame()
    {
        return redirect()->route('pre-game-lobby', ['game' => $this->game]);
    }

    public function render()
    {
        return view('livewire.pre-game-lobby');
    }
}
