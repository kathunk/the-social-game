<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Move;
use App\Models\Player;
use Livewire\Component;
use App\Events\GameForfeited;
use Illuminate\Support\Carbon;
use Thunk\Verbs\Facades\Verbs;
use App\Events\UserAddedFriend;
use App\Events\PlayerPlayedTile;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use App\Events\PlayerRequestedRematch;

class GameView extends Component
{
    public Game $game;

    public array $board;

    public int $elephant_space;

    public bool $is_player_turn;

    public string $phase;

    public string $game_status;

    public int $player_hand;

    public ?int $opponent_hand = 0;

    public array $valid_elephant_moves;

    public array $valid_slides;

    public string $opponent_is_friend;

    public ?array $victor_ids;

    public ?array $winning_spaces;

    public bool $player_is_victor;

    public bool $opponent_is_victor;

    public ?Carbon $player_forfeits_at;

    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    #[Computed]
    public function player()
    {
        return $this->game->players()->where('user_id', $this->user->id)->first();
    }

    #[Computed]
    public function opponent(): ?Player
    {
        return $this->game->players->firstWhere('user_id', '!=', $this->user->id);
    }

    #[Computed]
    public function tiles()
    {
        return collect($this->board)
            ->reject(fn ($space) => $space === null)
            ->toArray();
    }

    #[Computed]
    public function moves()
    {
        return $this->game->moves;
    }

    public function mount(Game $game)
    {
        $this->game = $game;

        if ($this->game->status === 'canceled') {
            return redirect()->route('dashboard');
        }

        if (! $this->player || ! $this->opponent || $this->game->status === 'created') {
            return redirect()->route('games.pre-game-lobby.show', $this->game);
        }

        $this->board = $this->game->board;

        $this->elephant_space = $this->game->elephant_space;

        $this->is_player_turn = $this->game->current_player_id === (string) $this->player->id && $this->game->status === 'active';

        $this->phase = $this->game->phase;

        $this->game_status = $this->game->status;

        $this->player_hand = $this->player->hand;

        $this->opponent_hand = $this->opponent?->hand;

        $this->valid_elephant_moves = $this->game->valid_elephant_moves;

        $this->valid_slides = $this->game->valid_slides;

        $this->opponent_is_friend = $this->user->friendship_status_with($this->opponent->user);

        $this->victor_ids = $this->game->victor_ids;

        $this->winning_spaces = $this->game->winning_spaces;

        $this->player_is_victor = in_array(
            (string) $this->player->id, 
            $this->game->victor_ids
        );

        $this->opponent_is_victor = in_array(
            (string) $this->opponent->id, 
            $this->game->victor_ids
        );

        $this->player_forfeits_at = $this->player->forfeits_at;
    }

    public function playTile($direction, $index)
    {
        $space = match($direction) {
            'down' => $index,
            'up' => $index + 12,
            'right' => 1 + ($index - 1) * 4,
            'left' => $index * 4,
        };

        PlayerPlayedTile::fire(
            game_id: $this->game->id,
            space: $space,
            direction: $direction,
            player_id: $this->player->id,
            board_before_slide: $this->board,
        );

        $this->game->refresh();
        $this->valid_slides = $this->game->valid_slides;
        $this->valid_elephant_moves = $this->game->valid_elephant_moves;
    }

    public function moveElephant($space)
    {
        $this->player->moveElephant($space, skip_bot_phase: true);
        Verbs::commit();

        $this->opponent->takeBotTurnIfNecessary();

        $this->game->refresh();
        $this->valid_slides = $this->game->valid_slides;
        $this->valid_elephant_moves = $this->game->valid_elephant_moves;
    }

    public function getListeners()
    {
        return [
            "echo-private:games.{$this->game->id}:PlayerMovedElephantBroadcast" => 'handleElephantMove',
            "echo-private:games.{$this->game->id}:PlayerPlayedTileBroadcast" => 'handleTilePlayed',
            "echo-private:games.{$this->game->id}:GameEndedBroadcast" => 'handleGameEnded',
            "echo-private:users.{$this->user->id}:UserAddedFriendBroadcast" => 'handleFriendRequest',
        ];
    }

    public function handleElephantMove($event)
    {
        $move = Move::find($event['elephant_move_id']);

        $prior_tile_move = Move::find($event['tile_move_id']);

        if ($move->player_id === $this->player->id) {
            return;
        }

        $tile_direction = match($prior_tile_move->initial_slide['direction']) {
            'down' => 'down',
            'up' => 'up',
            'left' => 'from_right',
            'right' => 'from_left',
        };

        $tile_position = match($prior_tile_move->initial_slide['direction']) {
            'down' => $prior_tile_move->initial_slide['space'] - 1,
            'up' => $prior_tile_move->initial_slide['space'] - 13,
            'right' => ($prior_tile_move->initial_slide['space'] - 1) / 4,
            'left' => ($prior_tile_move->initial_slide['space'] / 4) - 1,
        };

        $this->dispatch('opponent-moved-elephant', [
            'elephant_move_id' => (string) $move->id,
            'elephant_move_position' => $move->elephant_after,
            'player_id' => (string) $move->player_id,
            'player_forfeits_at' => $this->player->fresh()->forfeits_at,
            'tile_move_id' => (string) $prior_tile_move->id,
            'tile_direction' => $tile_direction,
            'tile_position' => $tile_position,
            'previous_elephant_space' => $move->elephant_before,
        ]);

        $this->game->refresh();

        $this->elephant_space = $move->elephant_after;

        $this->valid_slides = $this->game->valid_slides;

        $this->valid_elephant_moves = $this->game->valid_elephant_moves;

        $this->phase = $this->game->phase;

        $this->game_status = $this->game->status;

        $this->is_player_turn = $this->game->current_player_id === (string) $this->player->id && $this->game->status === 'active';

        $this->player_forfeits_at = $this->player->fresh()->forfeits_at;
    }

    public function handleTilePlayed($event)
    {
        $move = Move::find($event['move_id']);

        if ($move->player_id === $this->player->id) {
            return;
        }

        $direction = match($move->initial_slide['direction']) {
            'down' => 'down',
            'up' => 'up',
            'left' => 'from_right',
            'right' => 'from_left',
        };

        $position = match($move->initial_slide['direction']) {
            'down' => $move->initial_slide['space'] - 1,
            'up' => $move->initial_slide['space'] - 13,
            'right' => ($move->initial_slide['space'] - 1) / 4,
            'left' => ($move->initial_slide['space'] / 4) - 1,
        };

        $this->game->refresh();

        $this->valid_slides = $this->game->valid_slides;

        $this->valid_elephant_moves = $this->game->valid_elephant_moves;

        $this->phase = $this->game->phase;

        $this->game_status = $this->game->status;

        $this->is_player_turn = $this->game->current_player_id === (string) $this->player->id && $this->game->status === 'active';

        $this->player_is_victor = in_array(
            (string) $this->player->id, 
            $this->game->victor_ids
        );

        $this->opponent_is_victor = in_array(
            (string) $this->opponent->id, 
            $this->game->victor_ids
        );

        $this->dispatch('opponent-played-tile', [
            'direction' => $direction,
            'position' => $position,
            'player_id' => (string) $move->player_id,
            'victor_ids' => $this->game->victor_ids,
            'winning_spaces' => $this->game->winning_spaces,
            'game_status' => $this->game->status,
        ]);
    }

    public function handleGameEnded($event)
    {
        $this->dispatch('game-ended', [
            'status' => $this->game->status,
            'victor_ids' => $this->game->victor_ids,
            'winning_spaces' => $this->game->winning_spaces,
            'player_is_victor' => in_array((string) $this->player->id, $this->game->victor_ids),
            'opponent_is_victor' => in_array((string) $this->opponent->id, $this->game->victor_ids),
            'player_rating' => $this->player->user->fresh()->rating,
            'opponent_rating' => $this->opponent->user->fresh()->rating,
        ]);
    }

    public function handleFriendRequest($event)
    {
        $this->opponent_is_friend = $this->user->fresh()->friendship_status_with($this->opponent->user->fresh());

        $this->dispatch('friend-status-changed', [
            'status' => $this->opponent_is_friend,
        ]);
    }

    public function handleForfeit()
    {
        GameForfeited::fire(
            game_id: $this->game->id,
            loser_id: $this->player->id,
            winner_id: $this->opponent->id,
        );
    }

    public function sendFriendRequest()
    {
        UserAddedFriend::fire(
            user_id: $this->user->id,
            friend_id: $this->opponent->user->id,
        );

        Verbs::commit();

        $this->opponent_is_friend = $this->user->fresh()->friendship_status_with($this->opponent->user->fresh());
    }

    public function updateFromPoll()
    {
        $this->opponent->takeBotTurnIfNecessary();

        if ($this->game->fresh()->rematch_game_id) {
            return redirect()->route('games.show', $this->game->rematch_game_id);
        }
    }

    public function requestRematch()
    {
        PlayerRequestedRematch::fire(
            player_id: $this->player->id,
            user_id: $this->user->id,
            game_id: $this->game->id,
        );

        if ($this->opponent->fresh()->wants_rematch) {
            $game = Game::fromTemplate(
                user: $this->user, 
                is_bot_game: $this->opponent->is_bot, 
                is_friends_only: $this->game->is_friends_only,
                is_ranked: $this->game->is_ranked,
                is_rematch_from_game_id: $this->game->id,
                is_first_player: ! $this->player_is_victor,
            );

            return redirect()->route('games.show', $game->id);
        }
    }

    public function render()
    {
        return view('livewire.game-view');
    }
}
