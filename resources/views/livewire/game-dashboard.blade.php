<div class="flex flex-col gap-4">
    @if ($this->is_game_admin)

            <flux:button icon="cog" :href="route('pre-game-lobby', $this->game)" variant="filled">Manage game</flux:button>

    @endif

    @if (! $this->current_team && $this->game->gameTemplate->type === 'team' && $this->player->status === 'active')
        <flux:card>
            <flux:heading>Join a team</flux:heading>
            <flux:subheading>To start playing, join a team. At certain points in the game, you will be able to switch teams.</flux:subheading>
            <flux:select wire:model="selected_team_id" class="my-4">
                <flux:select.option value="">Select a team</flux:select.option>
                @foreach ($this->teams as $team)
                    <flux:select.option :value="(string) $team->id">{{ $team->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button wire:click="joinTeam">Join</flux:button>
        </flux:card>
    @endif
    @if ($this->challenge && $this->current_team)
        <x-game-components.challenge :challenge="$this->challenge" :challenge-component="$this->challenge_component" />
    @endif
    <x-game-components.scoreboard :teams="$this->teams" />
    @if ($this->current_team)
        <flux:card>
            <flux:heading>Had enough?</flux:heading>
            <flux:subheading class="mb-4">You can quit the game at any time. When you quit, you can give your team +3 or -3 points.</flux:subheading>
            <flux:modal.trigger name="quit" class="flex justify-end">
                <flux:button variant="danger">I've had enough</flux:button>
            </flux:modal.trigger>
        </flux:card>
    @endif
    @if ($this->game->gameTemplate->players_can_join_late)
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

    <flux:modal name="quit" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Quit the game</flux:heading>
                <flux:text class="mt-2">We get it. Not everyone is a champion.</flux:text>
            </div>

            <flux:radio.group wire:model="quit_points" label="When I quit, I want to give my team:">
                <flux:radio value="3" label="+3 points" checked />
                <flux:radio value="-3" label="-3 points" />
            </flux:radio.group>

            <div class="flex">
                <flux:spacer />

                <flux:button variant="danger" wire:click="resign">Resign</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="qr-code">
        <div class="flex justify-center p-6">
            <x-qr :url="$this->game->url" />
        </div>
    </flux:modal>
</div>
