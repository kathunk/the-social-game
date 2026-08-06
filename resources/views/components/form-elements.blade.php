@props([
    'element' => null,
    'form',
    'type' => null,
    'class_key'
])

@switch($element['type'])
    @case('title')
        <x-forms.heading class="!text-lg">{{ $element['text'] }}</x-forms.heading>
        @break
    @case('subtitle')
        <x-forms.subheading class="text-black">{{ $element['text'] }}</x-forms.subheading>
        @break
    @case('image')
        <img src="{{ $element['url'] }}" alt="{{ $element['alt'] }}" class="w-full h-auto rounded-lg my-4" />
        @break
    @case('message')
        <flux:text class="mt-1 text-xs">{{ $element['text'] }}</flux:text>
        @break
    @case('divider')
        <flux:separator class="my-4" />
        @break
    @case('table')
        <flux:table>
            <flux:table.columns>
                @foreach ($element['headers'] as $header)
                    <flux:table.column class="text-xs break-words whitespace-normal">{{ $header }}</flux:table.column>
                @endforeach
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($element['rows'] as $row)
                    <flux:table.row>
                        @foreach ($row as $cell)
                            <flux:table.cell class="text-xs break-words whitespace-normal">{{ $cell }}</flux:table.cell>
                        @endforeach
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
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
                <x-button
                    wire:loading.attr="disabled"
                    wire:key="button-{{ $class_key }}-{{ $btn['action'] }}"
                    variant="primary"
                    wire:click="callClassAction('{{ $btn['action'] }}', '{{ $type }}', '{{ $class_key }}', {{ json_encode($form) }})"
                >
                    {{ $btn['label'] }}
                </x-button>
            @endforeach
        </div>
        @break
    @case('select')
    <flux:field>
        <div class="*:!text-faded-gray *:!text-xxs md:*:!text-xs">
            <flux:label>{{ $element['label'] }}</flux:label>
        </div>
        <flux:select
            wire:key="select-{{ $class_key }}-{{ $element['property_name'] }}"
            wire:model="round_properties.{{ $class_key }}.{{ $element['property_name']}}"
            variant="listbox"
            :searchable="$element['searchable']"
            class="-mt-0.5"
        >
        @isset($element['placeholder'])
            <flux:select.option value="" selected class="placeholder">{{ $element['placeholder'] }}</flux:select.option>
        @endisset
            @foreach($element['options'] as $key => $value)
                <flux:select.option wire:key="select-option-{{ $class_key }}-{{ $element['property_name'] }}-{{ $key }}" value="{{ $key }}">{{ $value }}</flux:select.option>
            @endforeach
        </flux:select>
    </flux:field>
        @break
    @case('radio_group')
        <flux:radio.group label="{{ $element['label'] }}" variant="cards" class="flex-col" wire:model="round_properties.{{ $class_key }}.{{ $element['property_name'] }}">
            @foreach ($element['options'] as $option)
                <flux:radio
                    value="{{ $option['value'] }}"
                    label="{{ $option['label'] }}"
                    description="{{ $option['description'] }}"
                    :disabled="$option['disabled']"
                />
            @endforeach
        </flux:radio.group>
        @break
    @default
        @php
            $customComponent = 'game-components.custom-form-elements.' . str_replace('_', '-', $element['type']);
        @endphp
        @if (View::exists('components.' . $customComponent))
            <x-dynamic-component :component="$customComponent" :element="$element" />
        @endif
        @break
@endswitch
