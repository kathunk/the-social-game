@if (isset($modifierComponent['elements']))
<x-card>
    <div class="flex flex-col space-y-4">
        @foreach ($modifierComponent['elements'] as $element)
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
                @case('divider')
                    <flux:separator class="my-4" />
                    @break
                @case('table')
                    <flux:table :rows="$element['rows']" />
                    @break
                @case('input')
                    <flux:input
                        label="{{ $element['label']}}"
                        placeholder="{{$element['placeholder']}}"
                        wire:model="modifier_properties.{{ $element['property_name']}}"
                    />
                    @break
                @case('button_group')
                    <div class="flex space-x-2 mt-4 justify-end">
                        @foreach ($element['buttons'] as $btn)
                            <flux:button
                                variant="primary"
                                wire:click="callModifierAction('{{ $modifier->key }}', '{{ $btn['action'] }}')"
                            >
                                {{ $btn['label'] }}
                            </flux:button>
                        @endforeach
                    </div>
                    @break
                @case('select')
                    {{-- @todo same issue here where first option in list is not placeholder, and is not real --}}
                    <flux:select
                        label="{{ $element['label'] }}"
                        wire:model="modifier_properties.{{ $element['property_name']}}"
                    >
                    @isset($element['placeholder'])
                        <flux:select.option value="" selected class="placeholder">{{ $element['placeholder'] }}</flux:select.option>
                    @endisset
                        @foreach($element['options'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{ $value }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @break
            @endswitch
        @endforeach
    </div>
</x-card>
@endif
