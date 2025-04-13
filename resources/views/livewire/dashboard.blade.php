<div class="flex flex-col gap-4">
    @if (! $this->current_team)
        <flux:card>
            <flux:heading>Join a team</flux:heading>
            <flux:subheading>To start playing, join a team. At certain points in the game, you will be able to switch teams.</flux:subheading>
            <flux:select wire:model="selected_team_id" class="my-4">
                @foreach ($this->teams as $team)
                    <flux:select.option :value="(string) $team->id">{{ $team->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button wire:click="joinTeam">Join</flux:button>
        </flux:card>
    @endif
    <x-game-components.scoreboard :teams="$this->teams" />
</div>
