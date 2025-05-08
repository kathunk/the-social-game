<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Game;

class EnsureGameExists
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Check for either 'snowflake' or 'game' parameter
        $gameId = $request->route('snowflake') ?? $request->route('game');

        if (! $gameId || ! Game::where('id', $gameId)->exists()) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
