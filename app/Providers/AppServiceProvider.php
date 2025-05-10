<?php

namespace App\Providers;

use App\Models\Player;
use App\Models\Challenge;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom validation rule for @nuclear_code
        Validator::extend('nuclear_code', function ($attribute, $value, $parameters, $validator) {
            $player = auth()?->user()?->currentPlayer;
            $challenge = auth()?->user()?->currentGame?->currentChallenge;

            if (!$player || !$challenge) {
                return false;
            }

            if (! $player instanceof Player || ! $challenge instanceof Challenge) {
                return false;
            }

            $team_id = $player->team_id;

            if (!isset($challenge->challenge_data['teams'][$team_id])) {
                return false;
            }

            $ally_team_id = $challenge->challenge_data['teams'][$team_id]['ally_team_id'];

            if (!isset($challenge->challenge_data['teams'][$ally_team_id])) {
                return false;
            }

            $ally_code = $challenge->challenge_data['teams'][$ally_team_id]['code'];

            return $value === $ally_code;
        });
    }
}
