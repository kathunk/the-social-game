<div class="space-y-4">
    @if ($this->games->filter(fn ($game) => $game->status === 'active')->count() > 0)
        <x-menus.game-card :games="$this->games->filter(fn ($game) => $game->status === 'active')" status="active" />
    @endif

    @if ($this->games->filter(fn ($game) => $game->status === 'upcoming')->count() > 0)
        <x-menus.game-card :games="$this->games->filter(fn ($game) => $game->status === 'upcoming')" status="upcoming" />
    @endif

    <flux:button variant="primary" :href="route('create-game')" icon="plus" class="w-full">New Game</flux:button>

    @if ($this->games->filter(fn ($game) => $game->status === 'ended')->count() > 0)
        <x-menus.game-card :games="$this->games->filter(fn ($game) => $game->status === 'ended')" status="ended" />
    @endif
</div>
