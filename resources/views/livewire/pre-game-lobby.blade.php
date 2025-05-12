<div
    wire:poll="checkStatus"
    class="flex flex-col gap-4"
>
    @if ($this->game->status === 'upcoming')
        <div class="mx-auto w-full text-center">
            <flux:text>
                @if ($this->game->starts_at->isFuture())
                    Starts {{ $this->game->starts_at->diffForHumans() }}
                @else
                    Game did not start because the player count is not correct.
                @endif
            </flux:text>
        </div>
    @endif

    @if (! $this->user)
        <x-card>
            <flux:heading class="text-center mx-auto">Login to join the game</flux:heading>

            <flux:button.group class="flex justify-center mt-2">
                <flux:button variant="primary" href="{{ route('register', ['game' => $this->game->id]) }}">Register</flux:button>
                <flux:button href="{{ route('login', ['game' => $this->game->id]) }}">Login</flux:button>
            </flux:button.group>
        </x-card>
    @endif

    @if ($this->user && ! $this->player && $this->is_joinable)
        <x-card class="flex justify-center">
            @if (! $this->application)
                <flux:button variant="primary" wire:click="joinGame" class="w-full">
                    @if($this->requires_admin_approval_to_join)
                        Request to join
                    @else
                        Join
                    @endif
                </flux:button>
            @endif

            @if ($this->application?->status === 'rejected')
                <flux:heading>You were rejected from the game.</flux:heading>
            @endif

            @if ($this->application?->status === 'pending')
                <flux:heading>Waiting for an admin to approve your application.</flux:heading>
            @endif
        </x-card>
    @endif

    @if ($this->application?->status === 'accepted' && $this->game->status === 'active')
        <flux:button variant="primary" href="{{ route('game-dashboard', ['game' => $this->game]) }}">
            Go to game
        </flux:button>
    @endif

    <x-card class="!text-zinc-700">
        <div class="flex flex-col gap-2">
            {!! $this->description !!}
        </div>
    </x-card>

    <x-card>
        <flux:heading class="mb-2">Invite your friends</flux:heading>
        <div class="flex gap-2">
            <flux:input icon="link" value="{{ $this->game->url }}" readonly copyable />
            <flux:modal.trigger name="qr-code">
                <flux:button variant="filled">Show QR <x-icons.qr class="w-4 h-4" /></flux:button>
            </flux:modal.trigger>
        </div>
    </x-card>

    @if ($this->hasTooManyPlayers)
        <flux:callout variant="warning" icon="exclamation-circle" heading="{{ $this->game->gameTemplate->name }} only allows {{ $this->game->gameTemplate->max_players }} players. Remove some players, or change the game template." />
    @endif

    @if ($this->hasTooFewPlayers)
        <flux:callout variant="warning" icon="exclamation-circle" heading="{{ $this->game->gameTemplate->name }} requires {{ $this->game->gameTemplate->min_players }} players. Add more players, or change the game template." />
    @endif

    @if ($this->is_game_admin && $this->game->status === 'upcoming')
        <x-card x-data="{editGameSettings: false}">
            <flux:heading class="mb-4">Game Settings</flux:heading>
            <div x-show="!editGameSettings" class="flex gap-2">
                <flux:button
                    variant="primary"
                    wire:click="startGame"
                    icon="rocket-launch"
                    :disabled="$this->hasTooManyPlayers || $this->hasTooFewPlayers"
                >
                    Start Game Now
                </flux:button>
                <flux:button @click="editGameSettings = true" icon="pencil">Edit</flux:button>
            </div>
            <div x-show="editGameSettings">
                <x-datetime
                    label="Start time"
                    name="game_start_timecode"
                    wire:model="game_start_timecode"
                    min="{{ now()->addMinute()->second(0)->toIsoString() }}"
                    required
                />
                <flux:select wire:model="game_template_id" variant="listbox" label="Game template" searchable placeholder="Choose game template...">
                    @foreach ($this->gameTemplates as $gameTemplate)
                        <flux:select.option :value="(string) $gameTemplate->id">
                            {{ $gameTemplate->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex flex-col gap-2 mt-4">
                    <flux:checkbox label="Open to all" wire:model="is_public" />
                    <flux:checkbox label="Requires your approval to join" wire:model="requires_admin_approval_to_join" />
                </div>

                <div class="flex justify-end mt-4 gap-2" x-data="{cancelGame: false}">
                    <div x-show="cancelGame">
                        <flux:button variant="danger" wire:click="cancelGame">Seriously cancel?</flux:button>
                    </div>
                    <div x-show="!cancelGame">
                        <flux:button variant="ghost" @click="cancelGame = true">Cancel Game</flux:button>
                    </div>
                    <flux:button @click="editGameSettings = false" wire:click="updateGameSettings">Update</flux:button>
                </div>
            </div>
        </x-card>
    @endif

    @if ($this->is_game_admin && $this->game->requires_admin_approval_to_join)
        <x-card>
            <flux:heading class="mb-4">Pending Players</flux:heading>

            <flux:select wire:model="selected_application_id" variant="listbox" searchable placeholder="Choose player...">
                @foreach ($this->newApplications as $application)
                    <flux:select.option :value="(string) $application->id">
                        {{ $application->user->name }} ({{ $application->user->email }})
                        @if ($this->acceptedUserNames->contains($application->user->name))
                            <flux:badge class="ml-2" size="sm" color="red">Duplicate Name</flux:badge>
                        @endif
                    </flux:select.option>
                @endforeach
            </flux:select>
            <div class="flex justify-end mt-4 gap-2">
                <flux:button variant="primary" wire:click="approveUser">Approve</flux:button>
                <flux:button variant="danger" wire:click="rejectUser">Reject</flux:button>
            </div>
        </x-card>
    @endif

    <x-card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Players</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->players as $player)
                    <flux:table.row wire:key="player-{{ $player->id }}">
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:text>{{ $player->name }}</flux:text>
                                @if ($this->admins->pluck('id')->contains($player->user->id))
                                    <x-icons.crown class="text-yellow-500 w-6 h-6" />
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-1 justify-end">
                                @if (
                                    ($this->creator->id === $this->user?->id || $this->is_game_admin)
                                    && $player->user_id !== $this->user?->id
                                    && $player->user_id !== $this->creator->id
                                    && (
                                        ! $this->admins->pluck('id')->contains($player->user_id)
                                        || $this->creator->id === $this->user->id
                                    )
                                )
                                    <div class="flex gap-1" x-data="{showConfirmation: false}">
                                        <div x-show="showConfirmation">
                                            <flux:button
                                                variant="danger"
                                                size="sm"
                                                wire:click="removePlayer('{{ $player->id }}')"
                                            >
                                                Seriously?
                                            </flux:button>
                                        </div>
                                        <div x-show="!showConfirmation">
                                            <flux:button
                                                variant="subtle"
                                                size="sm"
                                                icon="x-circle"
                                                @click="showConfirmation = true"
                                            >
                                                Remove
                                            </flux:button>
                                        </div>
                                    </div>
                                @endif

                                @if (
                                    $this->user?->id === $this->creator->id &&
                                    ! $this->admins->pluck('id')->contains($player->user_id) &&
                                    $this->user?->id !== $player->user_id
                                )
                                    <div class="flex gap-1">
                                        <flux:button variant="subtle" size="sm" wire:click="promoteToAdmin('{{ $player->id }}')">
                                            <div class="flex items-center gap-2">
                                                <x-icons.crown class="w-4 h-4" />
                                                Promote
                                            </div>
                                        </flux:button>
                                    </div>
                                @endif

                                @if (
                                    $this->user?->id === $this->creator->id &&
                                    $this->admins->pluck('id')->contains($player->user_id) &&
                                    $this->user?->id !== $player->user_id
                                )
                                    <div class="flex gap-1">
                                        <flux:button variant="subtle" size="sm" wire:click="demoteFromAdmin('{{ $player->id }}')">
                                            <div class="flex items-center gap-2">
                                                <x-icons.crown-slash class="w-4 h-4" />
                                                Demote
                                            </div>
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </x-card>

    <flux:modal name="qr-code">
        <div class="flex justify-center p-6">
            <x-qr :url="$this->game->url" />
        </div>
    </flux:modal>

    <flux:toast />
</div>
