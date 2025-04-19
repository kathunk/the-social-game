<div class="flex flex-col gap-4">
    @if (! $this->current_team && $this->player->status === 'active')
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
    <x-game-components.scoreboard :teams="$this->teams" />
    @if ($this->current_team)
        @if ($this->player->canSwitchTeams())
            <flux:card>
                <flux:heading>Switch teams</flux:heading>
                <flux:subheading>
                    Join a new team.
                    <strong>You will never be able to rejoin your current team if you do this.</strong>
                </flux:subheading>
                <flux:select wire:model="selected_team_id" class="my-4">
                    <flux:select.option value="">Select a team</flux:select.option>
                    @foreach ($this->remaining_teams as $team)
                        @if(isset($this->player->historicalTeams) && $this->player->historicalTeams->contains($team->id))
                            <flux:select.option :value="(string) $team->id" disabled>{{ $team->name }} (previous team)</flux:select.option>
                        @else
                            <flux:select.option :value="(string) $team->id">{{ $team->name }}</flux:select.option>
                        @endif
                    @endforeach
                </flux:select>
                <flux:button wire:click="joinTeam">Join</flux:button>
            </flux:card>
        @endif

        <flux:card>
            <flux:heading>Had enough?</flux:heading>
            <flux:subheading class="mb-4">You can quit the game at any time. When you quit, you can give your team +3 or -3 points.</flux:subheading>
            <flux:modal.trigger name="quit" class="flex justify-end">
                <flux:button variant="danger">I've had enough</flux:button>
            </flux:modal.trigger>
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
</div>
