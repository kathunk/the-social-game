@props(['games', 'status'])

<flux:card>
    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ ucfirst($status) }} Games</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($games as $game)
                <flux:table.row>
                    <flux:table.cell class="align-top">
                        <div class="flex flex-col space-y-2">
                            <div class="flex flex-col">
                                <div class="flex justify-between" x-data="{ show_confirmation: false }">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <flux:link wire:click="goToGame('{{ $game->id }}')">
                                            {{ $game->name }}
                                        </flux:link>
                                        <flux:text variant="subtle" class="text-xs">{{ $game->starts_at->diffForHumans() }}</flux:text>
                                    </div>
                                    @if ($game->status === 'upcoming')
                                        @if ($this->user->isGameAdmin($game))
                                            <flux:button
                                                icon="trash"
                                                variant="ghost"
                                                size="sm"
                                                @click="show_confirmation = true"
                                                x-show="!show_confirmation"
                                            />
                                            <flux:button
                                                icon="trash"
                                                variant="danger"
                                                size="sm"
                                                wire:click="cancelGame('{{ $game->id }}')"
                                                x-show="show_confirmation"
                                            >
                                                Cancel game
                                            </flux:button>
                                        @else ($this->user->isGameAdmin($game))
                                            <flux:button
                                                icon="trash"
                                                variant="ghost"
                                                size="sm"
                                                @click="show_confirmation = true"
                                                x-show="!show_confirmation"
                                            />
                                            <flux:button
                                                icon="trash"
                                                variant="danger"
                                                size="sm"
                                                wire:click="abandonGame('{{ $game->id }}')"
                                                x-show="show_confirmation"
                                            >
                                                Abandon game
                                            </flux:button>
                                        @endif
                                    @endif
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
</flux:card>