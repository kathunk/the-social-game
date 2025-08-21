<?php

namespace Tests;

use App\Events\GameModeAdded;
use App\Events\GameTemplateAdded;
use App\Models\Game;
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    public Game $game;

    public User $game_admin;

    public Player $player;

    public GameMode $game_mode;

    public GameTemplate $game_template;

    public function createGame(?GameMode $game_mode = null, ?GameTemplate $template = null, ?Carbon $starts_at = null)
    {
        if (! isset($this->game_admin)) {
            $this->game_admin = $this->createUser(name: 'game_admin', email: 'game_admin@example.com', encrypted_password: 'password');
        }

        $this->game = Game::fromTemplate(
            template: $template ?? $this->game_template,
            game_mode: $game_mode ?? $this->game_mode,
            starts_at: $starts_at ?? now(),
            user: $this->game_admin,
            is_public: true,
            requires_admin_approval_to_join: false,
        );

        return $this->game;
    }

    public function createUser(string $name, string $email, string $encrypted_password)
    {
        return User::fromTemplate(
            name: $name,
            email: $email,
            encrypted_password: $encrypted_password,
        );
    }

    public function createPlayer()
    {
        if (! isset($this->game)) {
            $this->createGame();
        }

        $user = $this->createUser(
            name: fake()->name(),
            email: fake()->email(),
            encrypted_password: 'password',
        );

        $user->requestToJoinGame($this->game);

        if ($this->game->requires_admin_approval_to_join) {
            $user->admitToGame($this->game, $this->game_admin);
        }

        $this->player = $user->fresh()->currentPlayer;

        return $this->player;
    }

    public function mockGameTemplate(
        array $challenges,
        string $type,
        ?array $modifiers = null,
        ?string $description = null,
        ?string $pre_game_lobby_message = null,
        ?bool $players_can_join_late = null,
        ?string $name = null,
        ?int $min_players = null,
        ?int $max_players = null,
        ?bool $is_public = null,
        ?array $team_names = null,
        ?string $scoreboard_type = null,
    ) {
        if (! isset($team_names)) {
            $team_names = $type === 'team' ? ['team1', 'team2', 'team3'] : [];
        }

        $mode_id = GameModeAdded::fire(
            name: $name ?? fake()->name(),
            description: $description ?? fake()->sentence(),
            pre_game_lobby_message: $pre_game_lobby_message ?? fake()->sentence(),
            type: $type,
            min_players: $min_players ?? null,
            max_players: $max_players ?? null,
            is_public: $is_public ?? true,
            players_can_join_late: $players_can_join_late ?? true,
            scoreboard_type: $scoreboard_type ?? 'individual',
        )->game_mode_id;

        $this->game_mode = GameMode::find($mode_id);

        $id = GameTemplateAdded::fire(
            game_mode_id: $mode_id,
            name: $name ?? fake()->name(),
            type: $type,
            is_public: $is_public ?? true,
            team_names: $team_names ?? [],
            challenges: $challenges,
            modifiers: $modifiers ?? [],
        )->game_template_id;

        $this->game_template = GameTemplate::find($id);

        return $this->game_template;
    }
}
