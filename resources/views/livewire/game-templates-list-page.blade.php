<div>
    <flux:card>
        <div class="flex flex-row justify-between items-center mb-4">
            <flux:heading size="lg" class="mb-4">Manage Game Templates</flux:heading>
            <flux:link :href="route('game-templates.create')">
                <flux:button variant="filled" icon="plus">New</flux:button>
            </flux:link>
        </div>

        <flux:tab.group>
            <flux:tabs wire:model="tab">
                <flux:tab name="public">Public</flux:tab>
                <flux:tab name="private">Private</flux:tab>
                <flux:tab name="archived">Archived</flux:tab>
            </flux:tabs>

            <flux:tab.panel name="public">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Players</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->gameTemplates->filter(fn($t) => $t->is_public) as $t)
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
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:tab.panel>
            <flux:tab.panel name="private">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Players</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->gameTemplates->filter(fn($t) => !$t->is_public) as $t)
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
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:tab.panel>
            <flux:tab.panel name="archived">
                            <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Players</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->archivedGameTemplates as $t)
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
                                <flux:table.cell>
                                    <flux:button variant="filled" wire:click="unarchiveTemplate('{{ $t->id }}')">Unarchive</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:tab.panel>
        </flux:tab.group>
    </flux:card>
</div>
