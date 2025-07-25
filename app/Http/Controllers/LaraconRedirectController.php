<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class LaraconRedirectController extends Controller
{
    public function handle(Request $request)
    {
        $game = Game::where('name', 'Laracon 2025')->latest()->first();

        if (! $game) {
            return redirect()->route('dashboard')->with('error', 'No game found.');
        }

        return redirect()->route('pre-game-lobby', $game);
    }
}
