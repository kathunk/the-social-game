<div class="flex flex-col gap-4">
    <flux:link :href="route('dashboard')" variant="ghost">
        Back to dashboard
    </flux:link>

    <flux:card>
        <flux:heading>{{ $team->name }}</flux:heading>
        
        @if ($this->players->count() > 0)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Team Members</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->players as $player)
                        <flux:table.row>
                            <flux:table.cell>
                                <flux:avatar :src="$player->user->gravatar" />
                                {{ $player->user->name }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <flux:subheading>No players yet</flux:subheading>
        @endif
    </flux:card>

    <flux:card>
        <flux:heading>Score: {{ $team->score }}</flux:heading>
        
        @if ($this->scoreHistoryEntries->count() > 0)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column></flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->scoreHistoryEntries as $entry)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="flex flex-col">
                                    <flux:subheading>{{ $entry->description }}</flux:subheading>
                                    <flux:text>{{ $entry->created_at->diffForHumans() }}</flux:text>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $entry->points }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <flux:subheading>No score history yet</flux:subheading>
        @endif
    </flux:card>
</div>
