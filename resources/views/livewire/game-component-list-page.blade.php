<div x-data="{ game_type: 'all' }">
    <flux:tab.group>
        <flux:tabs variant="segmented" class="mx-auto w-full">
            <flux:tab name="challenges">Challenges</flux:tab>
            <flux:tab name="modifiers">Modifiers</flux:tab>
            <flux:tab name="templates">Templates</flux:tab>
        </flux:tabs>

        <div class="mt-4">
            <flux:radio.group label="Game type" variant="cards" class="max-sm:flex-col" x-model="game_type">
                <flux:radio value="all" label="All" />
                <flux:radio value="team" label="Team" />
                <flux:radio value="individual" label="Individual" />
            </flux:radio.group>
        </div>

        <flux:tab.panel name="challenges">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column width="5%">Name</flux:table.column>
                    <flux:table.column width="70%">Description</flux:table.column>
                    <flux:table.column width="25%">Used in</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->challenges as $challenge)
                        <flux:table.row x-show="game_type === 'all' || game_type === '{{ $challenge::TYPE }}'">
                            <flux:table.cell class="align-top">
                                <flux:text class="whitespace-normal text-xs">{{ $challenge::NAME }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell class="align-top">
                                <flux:text class="whitespace-normal text-xs">{{ $challenge::DESCRIPTION }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell class="align-top">
                                <flux:text class="whitespace-normal text-xs">
                                    <ul class="list-disc pl-4">
                                        @foreach($this->templates->filter(fn($t) => in_array($challenge::key(), $t['challenges'])) as $template)
                                            <li><flux:link :href="route('game-templates.show', $template['id'])">{{ $template['name'] }}</flux:link></li>
                                        @endforeach
                                    </ul>
                                </flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:tab.panel>

        <flux:tab.panel name="modifiers">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column width="5%">Name</flux:table.column>
                    <flux:table.column width="70%">Description</flux:table.column>
                    <flux:table.column width="25%">Used in</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->modifiers as $modifier)
                        <flux:table.row x-show="game_type === 'all' || game_type === '{{ $modifier::TYPE }}'">
                            <flux:table.cell class="align-top">
                                <flux:text class="whitespace-normal text-xs">{{ $modifier::NAME }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell class="align-top">
                                <flux:text class="whitespace-normal text-xs">{{ $modifier::DESCRIPTION }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell class="align-top">
                                <flux:text class="whitespace-normal text-xs">
                                    <ul class="list-disc pl-4">
                                        @foreach($this->templates->filter(fn($t) => in_array($modifier::key(), $t['modifiers'])) as $template)
                                            <li><flux:link :href="route('game-templates.show', $template['id'])">{{ $template['name'] }}</flux:link></li>
                                        @endforeach
                                    </ul>
                                </flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:tab.panel>

        <flux:tab.panel name="templates">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column width="20%">Name</flux:table.column>
                    <flux:table.column width="30%">Description</flux:table.column>
                    <flux:table.column width="25%">Challenges</flux:table.column>
                    <flux:table.column width="25%">Modifiers</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->templates as $template)
                        <flux:table.row x-show="game_type === 'all' || game_type === '{{ $template['type'] }}'">
                            <flux:table.cell class="align-top">
                                <flux:link class="text-xs whitespace-normal" :href="route('game-templates.show', $template['id'])">{{ $template['name'] }}</flux:link>
                            </flux:table.cell>
                            <flux:table.cell class="align-top">
                                <flux:text class="whitespace-normal text-xs">{{ $template['description'] }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell class="align-top">
                                <flux:text class="whitespace-normal text-xs">
                                    <ul class="list-disc pl-4">
                                        @foreach($template['challenges'] as $challenge)
                                            <li>{{ App\Challenges\ChallengeRegistry::retrieveFromKey($challenge)::NAME }}</li>
                                        @endforeach
                                    </ul>
                                </flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:tab.panel>
    </flux:tab.group>
</div>
