<div>
    <flux:card>
        @if ($this->games->isEmpty())
            <flux:text>No games found</flux:text>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Game</flux:table.column>
                    <flux:table.column>Players</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->games as $game)
                        <flux:table.row>
                            <flux:table.cell class="align-top">
                                <div class="flex flex-col space-y-2">
                                    <div class="flex flex-row items-center space-x-2">
                                        @if ($game->status === 'upcoming')
                                            <flux:link :href="route('pre-game-lobby', $game)">
                                                {{ $game->name }}
                                            </flux:link>
                                        @else
                                            <flux:link :href="route('game-dashboard', $game)">
                                                {{ $game->name }}
                                            </flux:link>
                                        @endif
                                        <flux:badge :color="$game->status === 'upcoming' ? 'yellow' : ($game->status === 'active' ? 'green' : 'gray')" size="sm" inset="top bottom">
                                            {{ $game->status }}
                                        </flux:badge>
                                    </div>
                                    <div class="flex flex-row items-center space-x-2">
                                        <flux:text variant="subtle" size="xs">{{ $game->starts_at->diffForHumans() }}</flux:text>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="align-top">
                                <div class="flex flex-col justify-start h-full w-full">
                                    <flux:text size="xs" class="whitespace-normal break-words">
                                        {{ $game->players->take(12)->pluck('name')->join(', ') }}
                                        {{ $game->players->count() > 12 ? '...' : '' }}
                                    </flux:text>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
