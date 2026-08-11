@component('mail::message')

@if ($isFinalChallenge ?? false)
# 🏁 Final Round: {{ $game->name }}

This is the final round!
@if ($challenge->ends_at)

The challenge ends {{ $challenge->ends_at->diffForHumans() }}.
@endif
@else
# A new challenge has begun!

The next challenge starts now: {{ $challenge->handler()::NAME }}.@if ($challenge->ends_at) The challenge ends {{ $challenge->ends_at->diffForHumans() }}.@endif
@endif

@component('mail::button', ['url' => route('game-dashboard', ['game' => $game])])
View Game
@endcomponent

@endcomponent
