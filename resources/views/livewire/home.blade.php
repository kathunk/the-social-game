<div>
    <x-card>
        @if ($this->games->isEmpty())
            <flux:text>No games found</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Games</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->games as $game)
                        <flux:table.row>
                            <flux:table.cell class="align-top">
                                <div class="flex flex-col space-y-2">
                                    <div class="flex flex-col">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:link class="pr-4" wire:click="goToGame('{{ $game->id }}')">
                                                {{ $game->name }}
                                            </flux:link>
                                            <flux:badge :color="$game->status === 'upcoming' ? 'yellow' : ($game->status === 'active' ? 'green' : 'gray')" size="sm" inset="top bottom">
                                                {{ $game->status }}
                                            </flux:badge>
                                            <flux:text variant="subtle" size="xs" class="ml-2">{{ $game->starts_at->diffForHumans() }}</flux:text>
                                        </div>
                                        <flux:text class="mt-2 whitespace-normal break-words text-xs">
                                            {{ $game->players->take(12)->pluck('name')->join(', ') }}
                                            {{ $game->players->count() > 12 ? '...' : '' }}
                                        </flux:text>
                                    </div>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </x-card>
</div>
