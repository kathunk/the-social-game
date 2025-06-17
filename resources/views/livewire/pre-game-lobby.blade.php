<div
    wire:poll="checkStatus"
    class="flex flex-col gap-4"
    x-data="{ gameModeId: $wire.entangle('game_mode_id') }"
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
        <flux:card>
            <flux:heading class="text-center mx-auto">Login to join the game</flux:heading>

            <flux:button.group class="flex justify-center mt-2">
                <flux:button variant="primary" href="{{ route('register', ['game' => $this->game->id]) }}">Register</flux:button>
                <flux:button href="{{ route('login', ['game' => $this->game->id]) }}">Login</flux:button>
            </flux:button.group>
        </flux:card>
    @endif

    @if ($this->user && ! $this->player && $this->is_joinable)
        <flux:card class="flex justify-center">
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
        </flux:card>
    @endif

    @if ($this->application?->status === 'accepted' && $this->game->status !== 'upcoming')
        <flux:button variant="primary" href="{{ route('game-dashboard', ['game' => $this->game]) }}">
            Go to game
        </flux:button>
    @endif

    <flux:card>
        <div class="flex flex-col gap-2">
            {!! $this->description !!}
        </div>
    </flux:card>

    @if ($this->game->status !== 'ended')
        <flux:card>
            <flux:heading class="mb-2">Invite your friends</flux:heading>
            <div class="flex gap-2">
                <flux:input icon="link" value="{{ $this->game->url }}" readonly copyable />
                <flux:modal.trigger name="qr-code">
                    <flux:button variant="filled">Show QR <x-icons.qr class="w-4 h-4" /></flux:button>
                </flux:modal.trigger>
            </div>
        </flux:card>
    @endif

    @if ($this->hasTooManyPlayers)
        <flux:callout variant="warning" icon="exclamation-circle" heading="{{ $this->game->gameMode->name }} only allows {{ $this->game->gameMode->max_players }} players. Remove some players, or change the game mode." />
    @endif

    @if ($this->hasTooFewPlayers)
        <flux:callout variant="warning" icon="exclamation-circle" heading="{{ $this->game->gameMode->name }} requires {{ $this->game->gameMode->min_players }} players. Add more players, or change the game mode." />
    @endif

    @if ($this->is_game_admin && $this->game->status === 'upcoming')
        <flux:card x-data="{editGameSettings: false}">
            <flux:heading class="mb-4">Game Settings</flux:heading>
            <div x-show="!editGameSettings" class="flex gap-2">
                @unless ($this->hasTooManyPlayers || $this->hasTooFewPlayers)
                    <flux:button
                        variant="primary"
                        wire:click="startGame"
                        icon="rocket-launch"
                        :disabled="$this->hasTooManyPlayers || $this->hasTooFewPlayers"
                    >
                        Start Game Now
                    </flux:button>
                @endunless
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

                <div class="flex flex-col gap-2 mb-4" x-data="{use_challenge_length_override: $wire.entangle('use_challenge_length_override')}">
                    <flux:field variant="inline">
                        <flux:checkbox wire:model="use_challenge_length_override" />
                        <flux:label>Use custom round lengths</flux:label>
                    </flux:field>

                    <div x-show="use_challenge_length_override" class="mb-4">
                        <flux:field>
                            <flux:description>
                                The game has {{ count($this->game_template->challenges) }} rounds,
                                and a total length of {{ $this->game->total_duration }} minutes.
                                If you fill this field, you will override that and set a new standard length for each round.
                            </flux:description>
                            <flux:input wire:model="challenge_length_override" />
                            <flux:error name="challenge_length_override" />
                        </flux:field>
                    </div>
                </div>

                <flux:radio.group
                    label="Select Game Mode"
                    variant="cards"
                    wire:model="game_mode_id"
                    class="flex-col"
                >
                    @foreach ($this->game_modes as $game_mode)
                        <flux:radio
                            name="game_mode_id"
                            value="{{ $game_mode->id }}"
                            label="{{ $game_mode->name }}"
                            description="{{ $game_mode->description }}"
                            x-bind:checked="gameModeId === '{{ $game_mode->id }}'"
                        />
                    @endforeach
                </flux:radio.group>

                @if ($this->user->is_super_admin)
                    <div class="mt-4">
                        <flux:select
                            wire:model="game_template_id"
                            variant="listbox"
                            label="Game template"
                            searchable
                            placeholder="Choose game template..."
                        >
                            @foreach ($this->gameTemplates as $gameTemplate)
                                <flux:select.option
                                    :value="(string) $gameTemplate->id"
                                    x-show="gameModeId === '{{ $gameTemplate->game_mode_id }}'"
                                >
                                    {{ $gameTemplate->name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="mt-4 flex gap-2 items-end">
                        <flux:input wire:model="bots_to_add" label="Add bots" min="0" />
                        <flux:button variant="primary" wire:click="fillGameWithBots()">Fill game with bots</flux:button>
                    </div>
                @endif

                <div class="flex flex-col gap-2 mt-4">
                    <flux:checkbox label="Requires your approval to join" wire:model="requires_admin_approval_to_join" />
                </div>

                <div class="flex justify-end mt-4 gap-2" x-data="{cancelGame: false}">
                    <div x-show="cancelGame">
                        <flux:button variant="danger" wire:click="cancelGame">Seriously cancel?</flux:button>
                    </div>
                    <div x-show="!cancelGame">
                        <flux:button variant="ghost" @click="cancelGame = true">Cancel Game</flux:button>
                    </div>
                    <flux:button wire:click="updateGameSettings">Update</flux:button>
                </div>
            </div>
        </flux:card>
    @endif

    @if ($this->is_game_admin && $this->game->requires_admin_approval_to_join && $this->game->status !== 'ended')
        <flux:card>
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
        </flux:card>
    @endif

    <flux:card>
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
                                    && $this->game->status === 'active'
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
                                    $this->user?->id !== $player->user_id &&
                                    $this->game->status !== 'ended'
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
                                    $this->user?->id !== $player->user_id &&
                                    $this->game->status !== 'ended'
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
    </flux:card>

    <flux:modal name="qr-code">
        <div class="flex justify-center p-6">
            <x-qr :url="$this->game->url" />
        </div>
    </flux:modal>

    <flux:toast />
</div>
