<div>
    <x-card>
        <div class="flex flex-row justify-between items-center mb-4">
            <flux:heading size="lg" class="mb-4">Manage Game Templates</flux:heading>
            <flux:link :href="route('game-templates.create')">
                <flux:button variant="filled" icon="plus">New</flux:button>
            </flux:link>
        </div>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Players</flux:table.column>
                <flux:table.column>Is Public</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->gameTemplates as $t)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:link :href="route('game-templates.show', $t->id)">{{ $t->name }}</flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ ucfirst($t->type) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-row items-center space-x-2">
                                {{ $t->min_players ?? "no min" }} - {{ $t->max_players ?? "no max" }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $t->is_public ? 'Yes' : 'No' }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </x-card>
</div>
