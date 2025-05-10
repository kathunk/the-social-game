<?php

namespace App\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\Game;

class MissingGameHandler
{
    public function __invoke(Request $request)
    {
        $params = collect($request->route()->parameters());

        if ($params->has('game')) {
            $game = $params->get('game');

            $game = $game instanceof Game
                ? $game
                : Game::firstWhere('id', $game);


            return isset($game)
                ? Redirect::route('game-dashboard', ['game' => $game])
                : Redirect::route('dashboard');
        }

        return Redirect::route('dashboard');
    }
}
