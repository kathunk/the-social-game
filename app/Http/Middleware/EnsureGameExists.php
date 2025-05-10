<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Game;
use Illuminate\Support\Facades\Log;

class EnsureGameExists
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Check for either 'snowflake' or 'game' parameter
        $gameId = $request->route('snowflake') ?? $request->route('game');

        Log::debug('EnsureGameExists middleware check', [
            'gameId' => $gameId,
            'exists' => $gameId ? Game::where('id', $gameId)->exists() : false,
            'route' => $request->route()->getName(),
            'params' => $request->route()->parameters(),
        ]);

        if (! $gameId || ! Game::where('id', $gameId)->exists()) {
            Log::debug('Redirecting to dashboard due to missing game');
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
