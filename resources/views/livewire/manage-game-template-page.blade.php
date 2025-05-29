<div x-data="{ game_type: @entangle('game_type') }">
    <flux:card>
        <div class="flex flex-col gap-6">
            <flux:input wire:model="name" label="Name" />
            <flux:textarea wire:model="description" label="Description" />
            <flux:editor wire:model="pre_game_lobby_message" label="Pre Game Lobby Message" description="This message will be sent to players when they join the game lobby." />
            <div class="flex flex-row gap-4">
                <flux:input wire:model="min_players" label="Minimum Players" placeholder="Can be left blank"/>
                <flux:input wire:model="max_players" label="Maximum Players" placeholder="Can be left blank"/>
            </div>

            <flux:input wire:model="team_names" label="Team Names" placeholder="Comma separated. Leave blank for no teams."/>
            <flux:field variant="inline">
                <flux:checkbox wire:model="is_public" />
                <flux:label>Visible to all paying users</flux:label>
                <flux:error name="is_public" />
            </flux:field>
            <flux:field variant="inline">
                <flux:checkbox wire:model="players_can_join_late" />
                <flux:label>Players can join late</flux:label>
                <flux:error name="players_can_join_late" />
            </flux:field>
            <flux:radio.group wire:model="game_type" label="Victory Condition">
                <flux:radio value="individual" label="Individual" checked />
                <flux:radio value="team" label="Team" />
            </flux:radio.group>

            <div class="flex flex-row gap-2 items-center">
                <flux:heading size="sm">Modifiers</flux:heading>
                <flux:modal.trigger name="show-modifiers">
                    <flux:link class="text-sm">show all</flux:link>
                </flux:modal.trigger>
            </div>
            <div x-data="{ editingModifiers: false }">
                <div x-show="! editingModifiers" class="flex flex-row gap-2 items-center">
                    <flux:text>
                        @if (count($this->modifiers) > 0)
                            {{ collect($this->modifiers)->map(fn($m) => App\Modifiers\ModifierRegistry::retrieveFromKey($m)::NAME)->join(', ') }}
                        @else
                            No modifiers selected
                        @endif
                    </flux:text>
                    <flux:button size="xs" icon="pencil" variant="subtle" @click="editingModifiers = true"></flux:button>
                </div>
                <div x-show="editingModifiers">
                    <flux:select variant="listbox" multiple searchable wire:model="modifiers">
                        @foreach ($this->allModifiers as $modifier)
                            <flux:select.option value="{{ $modifier::key() }}">{{ $modifier::NAME }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:button size="xs" icon="check" class="mt-2 w-full" variant="filled" @click="editingModifiers = false" wire:click="saveTemplate">done editing</flux:button>
                </div>
            </div>


            <flux:table>
                <flux:table.columns>
                    <flux:table.column>
                        <div class="flex flex-col gap-2">
                            <div class="flex flex-row gap-2">
                                <flux:heading size="sm">Challenges</flux:heading>
                                <flux:modal.trigger name="show-challenges">
                                    <flux:link>show all</flux:link>
                                </flux:modal.trigger>
                            </div>
                            <flux:text class="text-xs">If multiple are selected for a row, one will be chosen at random.</flux:text>
                        </div>
                    </flux:table.column>
                    <flux:table.column>
                        <div class="flex flex-col gap-2">
                            <flux:heading size="sm">Duration</flux:heading>
                            <flux:text class="text-xs">(minutes)</flux:text>
                        </div>
                    </flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->challenges as $i => $challenge)
                        <flux:table.row x-data="{ editing: false }" wire:key="challenge-{{ $i }}">
                            <flux:table.cell>
                                <template x-if="!editing">
                                    <div class="flex flex-row space-x-2 items-center">
                                        <flux:text class="whitespace-normal break-words">{{ collect($challenge['challenge_keys'])->map(fn($key) => App\Challenges\ChallengeRegistry::retrieveFromKey($key)::NAME)->join(', ') }}</flux:text>
                                        <flux:button size="xs" icon="pencil" variant="subtle" @click="editing = true"></flux:button>
                                    </div>
                                </template>

                                <template x-if="editing">
                                    <div class="flex flex-col space-y-2">
                                        <flux:select variant="listbox" multiple searchable wire:model="challenges.{{ $i }}.challenge_keys">
                                            @foreach ($this->allChallenges as $challenge)
                                                <flux:select.option value="{{ $challenge::key() }}">{{ $challenge::NAME }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:button size="xs" icon="check" variant="filled" @click="editing = false" wire:click="saveTemplate">done editing</flux:button>
                                    </div>
                                </template>
                            </flux:table.cell>
                            <flux:table.cell class="max-w-4">
                                <flux:input wire:model="challenges.{{ $i }}.duration"/>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:button variant="filled" size="sm" icon="plus" wire:click="addChallenge">Add Challenge</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                </flux:table.rows>
            </flux:table>

            <flux:error name="challenges" />
            <flux:error name="error" />

            <flux:button variant="primary" wire:click="saveTemplate">Save</flux:button>
        </div>
    </flux:card>

    @if ($this->game_template !== null)
        <div class="mt-4 flex flex-row space-x-4 justify-end">
            <flux:button variant="filled" wire:click="duplicateTemplate">Create duplicate</flux:button>
            <flux:modal.trigger name="archive-template">
                <flux:button variant="filled">Archive</flux:button>
            </flux:modal.trigger>
        </div>
    @endif

    <flux:modal name="show-challenges" class="w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Challenges</flux:heading>
                <flux:text class="mt-2">This is filtered by the game type selected.</flux:text>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Description</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->allChallenges as $challenge)
                        <template x-if="game_type === '{{ $challenge::TYPE }}'">
                            <flux:table.row>
                                <flux:table.cell>{{ $challenge::NAME }}</flux:table.cell>
                                <flux:table.cell class="whitespace-normal break-words">{{ $challenge::DESCRIPTION }}</flux:table.cell>
                            </flux:table.row>
                        </template>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:modal>

    <flux:modal name="show-modifiers" class="w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Modifiers</flux:heading>
                <flux:text class="mt-2">This is filtered by the game type selected.</flux:text>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Description</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->allModifiers as $modifier)
                        <template x-if="game_type === '{{ $modifier::TYPE }}'">
                            <flux:table.row>
                                <flux:table.cell>{{ $modifier::NAME }}</flux:table.cell>
                                <flux:table.cell class="whitespace-normal break-words">{{ $modifier::DESCRIPTION }}</flux:table.cell>
                            </flux:table.row>
                        </template>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:modal>

    <flux:modal name="archive-template" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Archive this template</flux:heading>
                <flux:text class="mt-2">Are you sure you want to archive this template? This action cannot be undone.</flux:text>
            </div>
            <flux:button variant="danger" wire:click="archiveTemplate">Archive</flux:button>
        </div>
    </flux:modal>
</div>

