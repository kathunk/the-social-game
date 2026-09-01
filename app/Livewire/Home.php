<?php

namespace App\Livewire;

use App\Challenges\ElephantInTheRoom\Support\ImpossibleBotReward;
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

    /**
     * The impossible-bot bounty promo modal: the offer plus the bot's
     * record, with a CTA straight into a bot game. Hidden once this user
     * has earned their code, or when the code pool is empty.
     */
    #[Computed]
    public function elephantBounty(): ?array
    {
        if (! ImpossibleBotReward::isPromoActiveFor($this->user)) {
            return null;
        }

        // The promo needs a public bot mode to send players into
        $bot_mode = GameMode::where('is_public', true)->get()->first(function ($mode) {
            $normalized = strtolower(preg_replace('/[^a-z]/i', '', $mode->name));

            return str_contains($normalized, 'elephant') && $mode->max_players === 1;
        });

        if (! $bot_mode) {
            return null;
        }

        $record = ImpossibleBotReward::botRecord();

        return [
            'offer' => ImpossibleBotReward::OFFER,
            'offer_url' => ImpossibleBotReward::OFFER_URL,
            'record' => $record,
            'bot_mode_id' => (string) $bot_mode->id,
            // Keyed by date: dismissing hides it for the rest of the day
            'dismiss_key' => 'elephant-bounty-dismissed:'.now()->toDateString(),
        ];
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

        // Instant-start modes skip the lobby entirely. Single-player modes
        // (max_players 1) always do — there's nobody to wait for — so they
        // don't depend on the flag being set. Guarded by min_players so a
        // misconfigured flag falls back safely.
        $skips_lobby = $mode->skips_pre_game_lobby || $mode->max_players === 1;

        if ($skips_lobby && $game->players()->count() >= ($mode->min_players ?? 1)) {
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
