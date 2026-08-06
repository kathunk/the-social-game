<div class="space-y-6">
    @if ($this->games->filter(fn ($game) => $game->status === 'active')->count() > 0)
        <x-menus.game-card :games="$this->games->filter(fn ($game) => $game->status === 'active')" status="active" />
    @endif

    @if ($this->games->filter(fn ($game) => $game->status === 'upcoming')->count() > 0)
        <x-menus.game-card :games="$this->games->filter(fn ($game) => $game->status === 'upcoming')" status="upcoming" />
    @endif

    {{-- GAME MODE CARDS - start a new game --}}
    @if ($this->gameModeCards->isNotEmpty())
        <section>
            <h2 class="text-xs font-bold uppercase tracking-wider text-faded-gray mb-3">Start a new game</h2>
            <div class="space-y-3">
                @foreach ($this->gameModeCards as $card)
                    <x-dynamic-component :component="$card['component']" :modes="$card['modes']" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($this->games->filter(fn ($game) => $game->status === 'ended')->count() > 0)
        <x-menus.game-card :games="$this->games->filter(fn ($game) => $game->status === 'ended')" status="ended" />
    @endif
</div>
