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
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\Models\Player;
use App\Support\HtmlTransformer;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;

class PreGameLobby extends Component
{
    public Game $game;

    public string $selected_application_id = '';

    public Carbon $game_start_timecode;

    public string $game_mode_id;

    public string $game_template_id;

    public GameTemplate $game_template;

    public GameMode $game_mode;

    public bool $requires_admin_approval_to_join;

    public Collection $game_templates;

    public int $bots_to_add = 0;

    public ?int $challenge_length_override = null;

    public bool $use_challenge_length_override = false;

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function gameModes()
    {
        return GameMode::all()
            ->sortBy('name')
            ->filter(function ($mode) {
                return $this->user->is_super_admin || $mode->is_public;
            });
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
        return (new HtmlTransformer($this->game->gameMode->pre_game_lobby_message))->formatted();
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
        $this->game_template = $game->gameTemplate;
        $this->game_mode = $game->gameMode;
        $this->game_templates = $this->game_mode->gameTemplates;
        $this->game_start_timecode = $game->starts_at;
        $this->requires_admin_approval_to_join = $game->requires_admin_approval_to_join;
        $this->game_mode_id = (string) $game->gameMode->id;
        $this->game_template_id = (string) $game->gameTemplate->id;
        $this->challenge_length_override = $game->challenge_length_override;
        $this->use_challenge_length_override = $game->challenge_length_override ? true : false;
        if ($this->application) {
            $this->checkStatus();
        }

        if ($this->game->status === 'canceled') {
            return redirect()->route('dashboard');
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
        $mode = GameMode::find($this->game_mode_id);

        if (! $mode->gameTemplates->pluck('id')->contains($this->game_template_id)) {
            $this->game_template_id = (string) $mode->selectTemplateForUser($this->user)->id;
        }

        $this->validate([
            'game_template_id' => 'required|exists:game_templates,id',
            'game_mode_id' => 'required|exists:game_modes,id',
            'game_start_timecode' => 'required|date',
            'challenge_length_override' => 'nullable|integer|min:1',
        ]);

        if (! $this->use_challenge_length_override) {
            $this->challenge_length_override = null;
        }

        $game_template = GameTemplate::find($this->game_template_id);

        $duration = $this->challenge_length_override
            ? $this->challenge_length_override * count($game_template->challenges)
            : $game_template->total_duration;

        $ends_at = Carbon::parse($this->game_start_timecode)->addMinutes($duration);

        GameUpdated::fire(
            game_id: $this->game->id,
            user_id: $this->user->id,
            game_template_id: (int) $this->game_template_id,
            game_mode_id: (int) $this->game_mode_id,
            starts_at: Carbon::parse($this->game_start_timecode),
            ends_at: $ends_at,
            is_public: true,
            requires_admin_approval_to_join: $this->requires_admin_approval_to_join,
            challenge_length_override: $this->challenge_length_override,
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
            game_mode_id: (int) $this->game_mode_id,
            starts_at: now(),
            ends_at: $ends_at,
            is_public: true,
            requires_admin_approval_to_join: $this->requires_admin_approval_to_join,
            challenge_length_override: $this->game->challenge_length_override,
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

    public function fillGameWithBots()
    {
        try {
            Artisan::call('app:fill-game-with-bots', ['game_id' => $this->game->id, 'amount' => $this->bots_to_add]);
        } catch (\Exception $e) {
            Flux::toast(variant: 'error', heading: 'Error', text: 'Error filling game with bots: '.$e->getMessage());
        }

        return redirect()->route('pre-game-lobby', ['game' => $this->game]);
    }

    public function render()
    {
        return view('livewire.pre-game-lobby');
    }
}
