<div class="flex flex-col gap-4">
    @if ($this->is_game_admin && $this->game->status !== 'ended')
        <x-button icon="cog" :href="route('pre-game-lobby', $this->game)" variant="filled">Manage game</x-button>
    @endif

    @if ($this->postGameMessage)
        <x-card>
            {!! $this->postGameMessage !!}
        </x-card>
    @endif

    @if ($this->template->type === 'team' && $this->player->status === 'active' && $this->game->status === 'active' && $this->gameMode->requires_team_membership    )
        <x-card>
            @if (! $this->current_team)
                <flux:heading class="!text-faded-gray !text-xxs font-semibold">Join a team</flux:heading>
                <span class="join-team-select">
                    <flux:select label="Select a team" class="[&>[data-flux-label]]:!sr-only mt-2" wire:model="selected_team_id">
                    <flux:select.option value="" selected class="placeholder">Select a team</flux:select.option>
                        @foreach ($this->teams as $team)
                            <flux:select.option :value="(string) $team->id">{{ $team->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </span>
                <div class="mt-4 flex justify-end">
                    <x-button variant="primary" wire:click="joinTeam">Join</x-button>
                </div>
            @else
                <flux:heading>You are on team <flux:link :href="route('teams.show', [$this->game, $this->current_team])" variant="ghost">{{ $this->current_team->name }}</flux:link></flux:heading>
            @endif
        </x-card>
    @endif
    @if ($this->game->status !== 'ended')
        <livewire:next-challenge-button />
    @endif
    @if ($this->challenge_component && $this->player->status === 'active' && $this->game->status === 'active')
        <x-game-components.form :form="$this->challenge_component" type="challenge" class_key="{{ $this->challenge->class_key }}" />
    @endif
    @if (in_array($this->game->status, ['active', 'ended']) && count($this->modifier_components) > 0 && $this->player->status === 'active')
        {{-- For ended games these are the modifiers' postGameComponent
             surfaces (initializeProperties swaps the source); modifiers
             without one produce empty components and render nothing --}}
        @foreach ($this->modifier_components as $class_key => $modifier)
            <x-game-components.form :form="$modifier" type="modifier" class_key="{{ $class_key }}" />
        @endforeach
    @endif
    @foreach ($this->getErrorBag()->toArray() as $field => $messages)
        @foreach ($messages as $message)
            <div class="text-red-600 text-sm">{{ $message }}</div>
        @endforeach
    @endforeach
    @if ($this->gameMode->scoreboard_type !== 'none')
        @if ($this->showScoreboard)
            <x-game-components.scoreboard
            :teams="$this->teams"
            :players="$this->players->filter(fn ($p) => $p->status === 'active')"
            :type="$this->template->type"
            :scoreboard_type="$this->gameMode->scoreboard_type"
            />
        @else
            <x-card>
                <flux:heading>Scoreboard</flux:heading>
                <flux:subheading>The scoreboard is hidden for this challenge.</flux:subheading>
            </x-card>
        @endif
    @endif
    @if ($this->game->status === 'active')
        @if ($this->template->players_can_join_late || $this->socialLink)
            <x-card x-data="{showQr: false}">
                @if ($this->template->players_can_join_late)
                    <flux:heading class="mb-2">Invite your friends</flux:heading>
                    <div class="flex gap-2">
                        <flux:input icon="link" value="{{ $this->game->url }}" readonly copyable />
                        <x-button variant="filled" @click="showQr = ! showQr">Show QR <x-icons.qr class="w-4 h-4" /></x-button>
                    </div>

                    <div x-show="showQr" class="mt-2">
                        <x-qr :url="$this->game->url" />
                    </div>
                @endif

                @if ($this->socialLink)
                    <div class="mt-4 flex">
                        <x-button href="{{ $this->game->social_links[0] }}" target="_blank">Join game chat</x-button>
                    </div>
                @endif

                <flux:text class="mt-4">Manage notifications preferences in your <flux:link variant="ghost" href="{{ route('settings.profile') }}">user profile</flux:link></flux:text>
            </x-card>
        @endif
    @endif

    @if ($this->footerMessage)
        <x-card>
            {!! $this->footerMessage !!}
        </x-card>
    @endif


    <flux:modal name="qr-code">
        <div class="flex justify-center p-6">
            <x-qr :url="$this->game->url" />
        </div>
    </flux:modal>
</div>
