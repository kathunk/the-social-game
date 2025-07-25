<div class="space-y-4">
    <x-card class="flex flex-col items-center justify-center">
        <flux:button variant="primary" :href="route('create-game')" class="mt-4">New Game</flux:button>
    </x-card>
    @if ($this->games->filter(fn ($game) => $game->status === 'active')->count() > 0)
        <x-menus.game-card :games="$this->games->filter(fn ($game) => $game->status === 'active')" status="active" />
    @endif

    @if ($this->games->filter(fn ($game) => $game->status === 'upcoming')->count() > 0)
        <x-menus.game-card :games="$this->games->filter(fn ($game) => $game->status === 'upcoming')" status="upcoming" />
    @endif

    @if ($this->games->filter(fn ($game) => $game->status === 'ended')->count() > 0)
        <x-menus.game-card :games="$this->games->filter(fn ($game) => $game->status === 'ended')" status="ended" />
    @endif
</div>
