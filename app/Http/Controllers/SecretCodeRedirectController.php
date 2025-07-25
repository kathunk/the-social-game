<?php

namespace App\Http\Controllers;

use App\Modifiers\Classes\TeamSecretCodes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecretCodeRedirectController extends Controller
{
    public function handle(Request $request)
    {
        $user = Auth::user();
        $game = $user->currentGame;

        if (! $game || $game->status !== 'active') {
            return redirect()->route('dashboard')->with('error', 'No current game found.');
        }

        $modifier = $game->modifiers
            ->firstWhere('class_key', TeamSecretCodes::key());

        if ($modifier) {
            return redirect()->route('games.secrets', [
                'game' => $game->id,
                'modifier' => $modifier->id,
            ]);
        }

        return redirect()->route('dashboard')->with('error', 'No secret codes available for your game.');
    }
}
