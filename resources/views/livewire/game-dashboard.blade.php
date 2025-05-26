<div class="flex flex-col gap-4">
    @if ($this->is_game_admin && $this->game->status !== 'ended')
        <flux:button icon="cog" :href="route('pre-game-lobby', $this->game)" variant="filled">Manage game</flux:button>
    @endif

    @if (! $this->current_team && $this->template->type === 'team' && $this->player->status === 'active')
        <flux:card>
            <flux:heading>Join a team</flux:heading>
            <flux:subheading>To start playing, join a team. At certain points in the game, you will be able to switch teams.</flux:subheading>
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
        </flux:card>
    @endif
    @if ($this->game->status !== 'ended')
        <livewire:next-challenge-button />
    @endif
    @if ($this->challengeComponent)
        <x-game-components.form :form="$this->challengeComponent" type="challenge" class_key="{{ $this->challenge->class_key }}" />
    @endif
    @if ($this->game->status === 'active')
        @foreach ($this->modifiers as $modifier)
            <x-game-components.form :form="$modifier->handler()->frontendComponent($this->player)" type="modifier" class_key="{{ $modifier->class_key }}" />
        @endforeach
    @endif
    @if ($this->showScoreboard)
        <x-game-components.scoreboard :teams="$this->teams" :players="$this->players" :type="$this->template->type" />
    @endif
    @if ($this->game->status === 'active')
        @if ($this->template->players_can_join_late)
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
    @endif

    <flux:modal name="qr-code">
        <div class="flex justify-center p-6">
            <x-qr :url="$this->game->url" />
        </div>
    </flux:modal>
</div>
