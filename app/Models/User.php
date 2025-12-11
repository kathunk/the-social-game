<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Events\UserAdmittedToGame;
use App\Events\UserCreated;
use App\Events\UserPromotedToGameAdmin;
use App\Events\UserRejectedFromGame;
use App\Events\UserRequestedToJoinGame;
use App\States\UserState;
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Thunk\Verbs\Facades\Verbs;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasFactory, HasSnowflakes, Notifiable;

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
        'provider_id',
        'provider_name',
        'avatar',
        'status',
        'current_game_id',
        'current_player_id',
        'is_super_admin',
        'subscribed_to_newsletter',
        'phone_number',
        'default_discord_webhook',
        'notification_preferences',
        'telegram_chat_id',
        'telegram_username',
        'telegram_verification_token',
        'telegram_connected_at',
        'push_subscriptions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_super_admin' => 'boolean',
        'notification_preferences' => 'array',
        'telegram_connected_at' => 'datetime',
        'push_subscriptions' => 'array',
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

    public static function fromTemplate(
        string $name,
        string $email,
        string $encrypted_password,
        ?string $provider_id = null,
        ?string $provider_name = null,
        ?string $avatar = null,
    ) {
        return UserCreated::commit(
            name: $name,
            email: $email,
            encrypted_password: $encrypted_password,
            provider_id: $provider_id,
            provider_name: $provider_name,
            avatar: $avatar,
        );
    }

    public function state(): UserState
    {
        return UserState::load($this->id);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function games()
    {
        return $this->hasManyThrough(
            Game::class,
            Player::class,
            'user_id', // Foreign key on players table
            'id', // Local key on games table
            'id', // Local key on users table
            'game_id', // Foreign key on players table
        );
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

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function getIsMemberAttribute(): bool
    {
        return $this->memberships
            ->filter(fn ($m) => $m->isActive())
            ->isNotEmpty();
    }

    public function isGameAdmin(Game $game): bool
    {
        return $game->admins->pluck('id')->contains($this->id);
    }

    public function promoteToGameAdmin(Game $game, User $admin)
    {
        UserPromotedToGameAdmin::fire(
            user_id: $this->id,
            game_id: $game->id,
            admin_id: $admin->id,
        );

        return $this->fresh();
    }

    public function requestToJoinGame(Game $game)
    {
        UserRequestedToJoinGame::fire(user_id: $this->id, game_id: $game->id);

        Verbs::commit();

        if ($game->requires_admin_approval_to_join) {
            return $this->fresh();
        }

        $this->fresh()->admitToGame($game, $game->admins->first());
    }

    public function admitToGame(Game $game, ?User $admin = null)
    {
        $application = $this->gameApplications->firstWhere(
            'game_id',
            $game->id,
        );

        if (! $application) {
            throw new \Exception('User has not applied to this game');
        }

        return UserAdmittedToGame::commit(
            user_id: $this->id,
            admin_id: $admin->id,
            game_id: $game->id,
            application_id: $application->id,
        );
    }

    public function rejectFromGame(Game $game, User $admin)
    {
        $application = $this->gameApplications->firstWhere(
            'game_id',
            $game->id,
        );

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

    public function getAvatarUrlAttribute(): string
    {
        // Use social provider avatar if available
        if ($this->avatar) {
            return $this->avatar;
        }

        // Fallback to gravatar
        return $this->gravatar;
    }

    public function getGravatarAttribute(): string
    {
        // Return gravatar with 404 fallback for frontend handling
        return 'https://www.gravatar.com/avatar/'.
            md5(strtolower(trim($this->email))).
            '?d=404&s=200';
    }

    public function getDefaultAvatarUrlAttribute(): string
    {
        // Local default avatar for frontend fallback
        return url('/build/images/default-avatar.png');
    }

    public function getHasActiveGameAttribute(): bool
    {
        return $this->games->where('status', 'active')->isNotEmpty();
    }

    public function wantsNotificationFor(string $event): bool
    {
        return $this->notification_preferences[$event] ?? false;
    }

    public function wantsNotificationVia(string $type): bool
    {
        return $this->notification_preferences[$type] ?? false;
    }

    public function wantsNotifications()
    {
        return $this->wantsNotificationVia('notify_via_email') ||
            $this->wantsNotificationVia('notify_via_sms') ||
            $this->wantsNotificationVia('notify_via_discord') ||
            $this->wantsNotificationVia('notify_via_telegram') ||
            $this->wantsNotificationVia('notify_via_browser');
    }

    public function hasNotificationContactConfigured(): bool
    {
        return ! empty($this->phone_number) ||
            ! empty($this->default_discord_webhook) ||
            ! empty($this->telegram_chat_id) ||
            ! empty($this->push_subscriptions);
    }

    public function hasTelegramConnected(): bool
    {
        return ! empty($this->telegram_chat_id);
    }

    public function hasPushSubscriptions(): bool
    {
        return ! empty($this->push_subscriptions);
    }

    public function addPushSubscription(array $subscription): void
    {
        $subscriptions = $this->push_subscriptions ?? [];

        // Check if subscription already exists
        $exists = collect($subscriptions)->contains(function ($sub) use ($subscription) {
            return $sub['endpoint'] === $subscription['endpoint'];
        });

        if (! $exists) {
            $subscriptions[] = $subscription;
            $this->push_subscriptions = $subscriptions;
            $this->save();
        }
    }

    public function removePushSubscription(string $endpoint): void
    {
        $subscriptions = collect($this->push_subscriptions ?? [])->filter(function ($sub) use ($endpoint) {
            return $sub['endpoint'] !== $endpoint;
        })->values()->toArray();

        $this->push_subscriptions = $subscriptions;
        $this->save();
    }
}
