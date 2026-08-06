<?php

namespace App\Livewire;

use App\Events\GameCanceled;
use App\Events\PlayerAbandonedGame;
use App\Events\UserSwitchedCurrentGame;
use App\Models\Game;
use App\Models\GameMode;
use App\Support\GameModeCardRegistry;
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
    public function gameModeCards()
    {
        $modes = $this->user->is_super_admin
            ? GameMode::all()
            : GameMode::where('is_public', true)->get();

        return GameModeCardRegistry::groupForDisplay($modes);
    }

    public function startGameFromMode(string $game_mode_id)
    {
        $mode = GameMode::find($game_mode_id);

        if (! $mode) {
            return;
        }

        // Hide non-public modes from non-admins
        if (! $mode->is_public && ! $this->user->is_super_admin) {
            return;
        }

        $template = $mode->selectTemplateForUser($this->user);

        if (! $template) {
            return;
        }

        $game = Game::fromTemplate(
            template: $template,
            game_mode: $mode,
            user: $this->user,
            requires_admin_approval_to_join: false,
            social_links: null,
        );

        // Instant-start modes (e.g. single-player) skip the lobby entirely —
        // guarded by min_players so a misconfigured flag falls back safely
        if ($mode->skips_pre_game_lobby && $game->players()->count() >= ($mode->min_players ?? 1)) {
            $game->fresh()->start();
            Verbs::commit();

            return redirect()->route('game-dashboard', ['game' => $game->id]);
        }

        return redirect()->route('pre-game-lobby', $game);
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
