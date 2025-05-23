<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('games.{id}', function ($user, $id) {
    return $user->gameApplications->pluck('game_id')->contains($id);
});
