@props(['games', 'status'])

<flux:card>
    <x-forms.heading size="xl" class="mb-4">
        {{ ucfirst($status) }} games
    </x-forms.heading>

    <div class="flex flex-col space-y-5">
        @foreach ($games as $game)
            <div class="flex flex-col">
                <div class="flex flex-col">
                    <div class="flex justify-between" x-data="{ show_confirmation: false }">
                        <div class="flex flex-wrap items-center gap-1 text-blue-500">
                            <flux:link variant="ghost" wire:click="goToGame('{{ $game->id }}')">
                                {{ $game->name }}
                            </flux:link>
                            <flux:icon size="sm" name="chevron-right" />
                        </div>
                        <div class="flex items-center">
                            <div class="flex items-center space-x-2">
                                <flux:text>{{ $game->players->count() }}</flux:text>
                                <flux:icon variant="solid" name="user" class="size-4" />
                                <flux:separator vertical />
                            </div>
                            @if ($game->status === 'active')
                                <div class="pl-2">
                                    <flux:text variant="subtle" class="text-sm">{{ $game->starts_at?->diffForHumans() }}</flux:text>
                                </div>
                            @endif

                            @if ($game->status === 'upcoming')
                                @if ($this->user->isGameAdmin($game))
                                    <div class="pl-1">
                                        <flux:button
                                            icon="trash"
                                            variant="ghost"
                                            size="xs"
                                            @click="show_confirmation = true"
                                            x-show="!show_confirmation"
                                        />
                                        <flux:button
                                        icon="trash"
                                        variant="danger"
                                        size="xs"
                                        wire:click="cancelGame('{{ $game->id }}')"
                                        x-show="show_confirmation"
                                        >
                                            Cancel game
                                        </flux:button>
                                    </div>
                                @else ($this->user->isGameAdmin($game))
                                    <div class="pl-1">
                                        <flux:button
                                            icon="trash"
                                            variant="ghost"
                                            size="xs"
                                            @click="show_confirmation = true"
                                            x-show="!show_confirmation"
                                        />
                                        <flux:button
                                            icon="trash"
                                            variant="danger"
                                            size="xs"
                                            wire:click="abandonGame('{{ $game->id }}')"
                                            x-show="show_confirmation"
                                        >
                                            Abandon game
                                        </flux:button>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</flux:card>