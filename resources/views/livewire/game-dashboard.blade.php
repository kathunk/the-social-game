<div class="flex flex-col gap-4">
    @if ($this->is_game_admin && $this->game->status !== 'ended')
        <flux:button icon="cog" :href="route('pre-game-lobby', $this->game)" variant="filled">Manage game</flux:button>
    @endif

    @if ($this->postGameMessage)
        <flux:card>
            {!! $this->postGameMessage !!}
        </flux:card>
    @endif

    @if ($this->template->type === 'team' && $this->player->status === 'active' && $this->game->status === 'active')
        <flux:card>
            @if (! $this->current_team)
                <flux:heading>Join a team</flux:heading>
                <span class="join-team-select">
                    <flux:select label="Select a team" class="[&>[data-flux-label]]:!sr-only mt-4" wire:model="selected_team_id">
                    <flux:select.option value="" selected class="placeholder">Select a team</flux:select.option>
                        @foreach ($this->teams as $team)
                            <flux:select.option :value="(string) $team->id">{{ $team->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </span>
                <div class="mt-4 flex justify-end">
                    <flux:button variant="primary" wire:click="joinTeam">Join</flux:button>
                </div>
            @else
                <flux:heading>You are on team {{ $this->current_team->name }}</flux:heading>
            @endif
        </flux:card>
    @endif
    @if ($this->game->status !== 'ended')
        <livewire:next-challenge-button />
    @endif
    @if ($this->challenge_component)
        <x-game-components.form :form="$this->challenge_component" type="challenge" class_key="{{ $this->challenge->class_key }}" />
    @endif
    <flux:error name="error" />
    @if ($this->game->status === 'active' && count($this->modifier_components) > 0)
        @foreach ($this->modifier_components as $class_key => $modifier)
            <x-game-components.form :form="$modifier" type="modifier" class_key="{{ $class_key }}" />
        @endforeach
    @endif
    @if ($this->showScoreboard)
        <x-game-components.scoreboard :teams="$this->teams" :players="$this->players" :type="$this->template->scoreboard_type" />
    @else
        <flux:card>
            <flux:heading>Scoreboard</flux:heading>
            <flux:subheading>The scoreboard is hidden for this challenge.</flux:subheading>
        </flux:card>
    @endif
    @if ($this->game->status === 'active')
        @if ($this->template->players_can_join_late || $this->socialLink)
            <flux:card x-data="{showQr: false}">
                @if ($this->template->players_can_join_late)
                    <flux:heading class="mb-2">Invite your friends</flux:heading>
                    <div class="flex gap-2">
                        <flux:input icon="link" value="{{ $this->game->url }}" readonly copyable />
                        <flux:button variant="filled" @click="showQr = ! showQr">Show QR <x-icons.qr class="w-4 h-4" /></flux:button>
                    </div>

                    <div x-show="showQr" class="mt-2">
                        <x-qr :url="$this->game->url" />
                    </div>
                @endif

                @if ($this->socialLink)
                    <flux:heading class="mb-4">Game chat</flux:heading>
                    <flux:button variant="primary" href="{{ $this->game->social_links[0] }}" target="_blank" class="{{ $this->template->players_can_join_late ? 'mt-2' : '' }}">Join game chat</flux:button>
                @endif
            </flux:card>
        @endif
    @endif

    @if ($this->footerMessage)
        <flux:card>
            {!! $this->footerMessage !!}
        </flux:card>
    @endif
    

    <flux:modal name="qr-code">
        <div class="flex justify-center p-6">
            <x-qr :url="$this->game->url" />
        </div>
    </flux:modal>
</div>
