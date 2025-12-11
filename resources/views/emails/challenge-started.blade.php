@component('mail::message')

@if ($isFinalChallenge ?? false)
# 🏁 Final Round: {{ $game->name }}

This is the final round! 

The challenge ends {{ $challenge->ends_at->diffForHumans() }}.
@else
# A new challenge has begun!

The next challenge starts now: {{ $challenge->handler()::NAME }}. The challenge ends {{ $challenge->ends_at->diffForHumans() }}.
@endif

<x-button href="{{ route('game-dashboard', ['game' => $game]) }}">View Game</x-button>

@endcomponent
