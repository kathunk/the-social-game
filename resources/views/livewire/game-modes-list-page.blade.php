<div>
    <flux:card>
        <div class="flex flex-row justify-between items-center mb-4">
            <flux:heading size="lg" class="mb-4">Manage Game Modes</flux:heading>
            <flux:link :href="route('game-modes.create')">
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
                        <flux:table.column>Templates</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->gameModes->filter(fn($m) => $m->is_public) as $m)
                            <flux:table.row>
                                <flux:table.cell class="align-top">
                                    <flux:link :href="route('game-modes.show', $m->id)">{{ $m->name }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell class="align-top">{{ ucfirst($m->type) }}</flux:table.cell>
                                <flux:table.cell class="align-top">
                                    <div class="flex flex-row items-center space-x-2">
                                        {{ $m->min_players ?? "no min" }} - {{ $m->max_players ?? "no max" }}
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell class="align-top">
                                    <flux:text class="whitespace-normal text-xs">
                                        <ul class="list-disc pl-4">
                                            @foreach ($this->gameTemplates->where('game_mode_id', $m->id) as $t)
                                                <li class="whitespace-normal break-words mb-2"><flux:link :href="route('game-templates.show', ['game_mode' => $m->id, 'game_template' => $t->id])">{{ $t->name }}</flux:link></li>
                                            @endforeach
                                        </ul>
                                    </flux:text>
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
                        @foreach ($this->gameModes->filter(fn($m) => !$m->is_public) as $m)
                            <flux:table.row>
                                <flux:table.cell>
                                    <flux:link :href="route('game-modes.show', $m->id)">{{ $m->name }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell>{{ ucfirst($m->type) }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex flex-row items-center space-x-2">
                                        {{ $m->min_players ?? "no min" }} - {{ $m->max_players ?? "no max" }}
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
                        @foreach ($this->archivedGameModes as $m)
                            <flux:table.row>
                                <flux:table.cell>
                                    <flux:link :href="route('game-modes.show', $m->id)">{{ $m->name }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell>{{ ucfirst($m->type) }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex flex-row items-center space-x-2">
                                        {{ $m->min_players ?? "no min" }} - {{ $m->max_players ?? "no max" }}
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button variant="filled" wire:click="unarchiveMode('{{ $m->id }}')">Unarchive</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:tab.panel>
        </flux:tab.group>
    </flux:card>
</div>
