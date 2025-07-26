@props(['games', 'status'])

<x-card>
    <x-forms.heading class="!text-lg mb-4">
        {{ ucfirst($status) }} games
    </x-forms.heading>

    <div class="flex flex-col space-y-5">
        @foreach ($games as $game)
            <div class="flex flex-col">
                <div class="flex flex-col">
                    <div class="flex items-center justify-between" x-data="{ show_confirmation: false }">
                        <div class="text-blue-500 text-xs md:text-sm">
                            <flux:link variant="ghost" wire:click="goToGame('{{ $game->id }}')" class="cursor-pointer">
                                <div class="flex flex-wrap items-center gap-1">
                                    <div>{{ $game->name }}</div>
                                    <flux:icon class="size-3 stroke-2" name="chevron-right" />
                                </div>
                            </flux:link>
                        </div>
                        <div class="flex items-center">
                            <div class="flex flex-col">
                                <div class="flex items-center space-x-1 *:!text-faded-gray *:!text-xxs md:*:!text-xs">
                                    <flux:icon variant="solid" name="user" class="size-2.5" />
                                    <flux:text>{{ $game->players->count() }}</flux:text>
                                    <flux:separator vertical />
                                    @if ($game->status !== 'upcoming')
                                        <flux:text>{{ $game->starts_at?->diffForHumans() }}</flux:text>
                                    @endif
                                </div>
                            </div>

                            @if ($game->status === 'upcoming')
                                @if ($this->user->isGameAdmin($game))
                                    <div class="pl-1">
                                        <x-button
                                            icon="trash"
                                            variant="ghost"
                                            size="xs"
                                            @click="show_confirmation = true"
                                            x-show="!show_confirmation"
                                        />
                                        <x-button
                                        icon="trash"
                                        variant="danger"
                                        size="xs"
                                        wire:click="cancelGame('{{ $game->id }}')"
                                        x-show="show_confirmation"
                                        >
                                            Cancel game
                                        </x-button>
                                    </div>
                                @else ($this->user->isGameAdmin($game))
                                    <div class="pl-1">
                                        <x-button
                                            icon="trash"
                                            variant="ghost"
                                            size="xs"
                                            @click="show_confirmation = true"
                                            x-show="!show_confirmation"
                                        />
                                        <x-button
                                            icon="trash"
                                            variant="danger"
                                            size="xs"
                                            wire:click="abandonGame('{{ $game->id }}')"
                                            x-show="show_confirmation"
                                        >
                                            Abandon game
                                        </x-button>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-card>
