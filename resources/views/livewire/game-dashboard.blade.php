<div class="flex flex-col gap-4">
    @if ($this->is_game_admin)
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
            <flux:button wire:click="joinTeam" class="mt-4">Join</flux:button>
        </flux:card>
    @endif
    @if ($this->challengeComponent)
        <livewire:next-challenge-button />
        <x-game-components.challenge :challenge="$this->challenge" :challenge-component="$this->challengeComponent" />
    @endif
    <x-game-components.scoreboard :teams="$this->teams" :players="$this->players" :type="$this->template->type" />
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

    @foreach ($this->modifiers as $modifier)
        <x-game-components.modifier :modifier="$modifier" :modifierComponent="$modifier->handler()->frontendComponent($this->player)" />
    @endforeach

    <flux:modal name="qr-code">
        <div class="flex justify-center p-6">
            <x-qr :url="$this->game->url" />
        </div>
    </flux:modal>
</div>
