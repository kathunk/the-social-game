@props(['teams'])

<div>
    <flux:card>
        <flux:heading size="lg">Scoreboard</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Team</flux:table.column>
                <flux:table.column>Score</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($teams as $team)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:button :variant="((string) $team->id === (string) $this->player->team_id) ? 'filled' : 'ghost'" size="sm" class="p-0" :href="route('teams.show', $team->id)">
                                {{ $team->name }}
                                @if ((string) $team->id === (string) $this->player->team_id)
                                    (your team)
                                @endif
                            </flux:button>
                        </flux:table.cell>
                        <flux:table.cell>{{ $team->score }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
