@props(['form', 'type' => null, 'class_key'])

@if (isset($form['elements']))
<flux:card>
    <div class="flex flex-col space-y-4">
        @if ($type === 'challenge')
            @php
                $challenges = $this->game->challenges;
                $activated_challenges = $challenges->where('status', 'active')->count() + $challenges->where('status', 'ended')->count();
                $total_challenges = $challenges->count();
            @endphp
            <div class="flex space-x-2 items-baseline">
                <flux:heading size="lg">Challenge</flux:heading>
                <flux:text class="text-sm">
                    ({{ $activated_challenges }} of {{ $total_challenges }})
                </flux:text>

                <flux:text variant="subtle" class="flex items-baseline">
                    <x-game-components.countdown-timer :time="$this->challenge->ends_at->toIsoString()" type="ends" />
                </flux:text>
            </div>
        @endif
        @foreach ($form['elements'] as $element)
            @switch($element['type'])
                @case('title')
                    <flux:heading>{{ $element['text'] }}</flux:heading>
                    @break
                @case('subtitle')
                    <flux:subheading>{{ $element['text'] }}</flux:subheading>
                    @break
                @case('image')
                    <img src="{{ $element['url'] }}" alt="{{ $element['alt'] }}" class="w-full h-auto rounded-lg my-4" />
                    @break
                @case('message')
                    <flux:text class="mt-1">{{ $element['text'] }}</flux:text>
                    @break
                @case('divider')
                    <flux:separator class="my-4" />
                    @break
                @case('table')
                    <flux:table :rows="$element['rows']" />
                    @break
                @case('input')
                    @if ($element['size'] === 'large')
                        <flux:textarea
                            wire:key="input-{{ $class_key }}-{{ $element['property_name'] }}"
                            label="{{ $element['label']}}"
                            placeholder="{{$element['placeholder']}}"
                            wire:model="round_properties.{{ $class_key }}.{{ $element['property_name']}}"
                        />
                    @else
                        <flux:input
                            wire:key="input-{{ $class_key }}-{{ $element['property_name'] }}"
                            label="{{ $element['label']}}"
                            placeholder="{{$element['placeholder']}}"
                            wire:model="round_properties.{{ $class_key }}.{{ $element['property_name']}}"
                        />
                    @endif
                    @break
                @case('button_group')
                    <div class="flex flex-wrap gap-2 mt-4 justify-end">
                        @foreach ($element['buttons'] as $btn)
                            <flux:button
                                wire:key="button-{{ $class_key }}-{{ $btn['action'] }}"
                                variant="primary"
                                wire:click="callClassAction('{{ $btn['action'] }}', '{{ $type }}', '{{ $class_key }}', {{ json_encode($form) }})"
                            >
                                {{ $btn['label'] }}
                            </flux:button>
                        @endforeach
                    </div>
                    @break
                @case('select')
                    <flux:select
                        wire:key="select-{{ $class_key }}-{{ $element['property_name'] }}"
                        label="{{ $element['label'] }}"
                        wire:model="round_properties.{{ $class_key }}.{{ $element['property_name']}}"
                        variant="listbox"
                        :searchable="$element['searchable']"
                    >
                    @isset($element['placeholder'])
                        <flux:select.option value="" selected class="placeholder">{{ $element['placeholder'] }}</flux:select.option>
                    @endisset
                        @foreach($element['options'] as $key => $value)
                            <flux:select.option wire:key="select-option-{{ $class_key }}-{{ $element['property_name'] }}-{{ $key }}" value="{{ $key }}">{{ $value }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @break
            @endswitch
        @endforeach
    </div>
</flux:card>
@endif
