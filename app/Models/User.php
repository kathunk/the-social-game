<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Events\UserAdmittedToGame;
use App\Events\UserAppliedToGame;
use App\Events\UserCreated;
use App\Events\UserPromotedToAdmin;
use App\Events\UserRejectedFromGame;
use App\States\UserState;
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Thunk\Verbs\Facades\Verbs;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasSnowflakes, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'status',
        'current_game_id',
        'current_player_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    public static function fromTemplate(string $name, string $email, string $encrypted_password, ?Game $game = null)
    {
        $user_id = UserCreated::fire(
            name: $name,
            email: $email,
            encrypted_password: $encrypted_password,
        )->user_id;

        Verbs::commit();

        $user = User::find($user_id);

        if ($game) {
            UserAppliedToGame::fire(
                user_id: $user_id,
                game_id: $game->id,
            );

            Verbs::commit();

            $user->refresh();
        }

        return $user;
    }

    public function state(): UserState
    {
        return UserState::load($this->id);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function gameApplications()
    {
        return $this->hasMany(GameApplication::class);
    }

    public function adminGames()
    {
        return $this->belongsToMany(Game::class, 'game_admins');
    }

    public function currentGame()
    {
        return $this->belongsTo(Game::class, 'current_game_id');
    }

    public function currentPlayer()
    {
        return $this->belongsTo(Player::class, 'current_player_id');
    }

    public function getIsAdminAttribute(): bool
    {
        $current_game = $this->currentGame;

        if (! $current_game) {
            return false;
        }

        return $current_game->admins->pluck('id')->contains($this->id);
    }

    public function promoteToAdmin(Game $game)
    {
        UserPromotedToAdmin::fire(
            user_id: $this->id,
            game_id: $game->id,
        );

        return $this->fresh();
    }

    public function applyToGame(Game $game)
    {
        UserAppliedToGame::fire(
            user_id: $this->id,
            game_id: $game->id,
        );

        Verbs::commit();

        return $this->fresh();
    }

    public function admitToGame(Game $game, User $admin)
    {
        $application = $this->gameApplications->firstWhere('game_id', $game->id);

        if (! $application) {
            throw new \Exception('User has not applied to this game');
        }

        $player_id = UserAdmittedToGame::fire(
            user_id: $this->id,
            admin_id: $admin->id,
            game_id: $game->id,
            application_id: $application->id,
        )->player_id;

        Verbs::commit();

        return Player::find($player_id);
    }

    public function rejectFromGame(Game $game, User $admin)
    {
        $application = $this->gameApplications->firstWhere('game_id', $game->id);

        if (! $application) {
            throw new \Exception('User has not applied to this game');
        }

        UserRejectedFromGame::fire(
            user_id: $this->id,
            admin_id: $admin->id,
            game_id: $game->id,
            application_id: $application->id,
        );

        Verbs::commit();

        return $this->fresh();
    }

    public function getGravatarAttribute(): string
    {
        return 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($this->email)));
    }
}
