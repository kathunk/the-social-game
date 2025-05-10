<flux:card>
    <div class="flex flex-col space-y-4">
        <div class="flex space-x-2 items-center">
            <flux:heading size="lg">Challenge</flux:heading>
            <flux:text variant="subtle">ends {{ $this->challenge->ends_at->diffForHumans() }}</flux:text>
        </div>
        @foreach ($challengeComponent['elements'] as $element)
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
                    <flux:input
                        label="{{ $element['label']}}"
                        placeholder="{{$element['placeholder']}}"
                        wire:model="challenge_properties.{{ $element['property_name']}}"
                    />
                    @break
                @case('button_group')
                    <div class="flex space-x-2 mt-4 justify-end">
                        @foreach ($element['buttons'] as $btn)
                            <flux:button
                                wire:click="callChallengeAction('{{ $btn['action'] }}')"
                                class="px-4 py-2 bg-blue-600 text-white rounded"
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
                        wire:model="challenge_properties.{{ $element['property_name']}}"
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
</flux:card>
