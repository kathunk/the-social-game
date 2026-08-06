@props(['form', 'type' => null, 'class_key'])

@if (isset($form['elements']))
<x-card>
    @if (isset($form['poll_interval']))
        <div wire:poll.{{ $form['poll_interval'] }}ms="refreshChallenge" class="flex flex-col space-y-1">
    @else
        <div class="flex flex-col space-y-1">
    @endif
        @if ($type === 'challenge')
            @php
                $challenges = $this->game->challenges;
                $activated_challenges = $challenges->where('status', 'active')->count() + $challenges->where('status', 'ended')->count();
                $total_challenges = $challenges->count();
            @endphp
            <flux:text class="flex flex-wrap items-baseline gap-1 text-faded-gray text-tiny md:text-xxs font-medium">
                <span>CHALLENGE</span>
                <span>({{ $activated_challenges }} of {{ $total_challenges }})</span>
                @if ($this->challenge->ends_at)
                    <x-game-components.countdown-timer :time="$this->challenge->ends_at->toIsoString()" type="ends" />
                @endif
            </flux:text>
        @endif
        @foreach ($form['elements'] as $element)
            @if ($element['type'] === 'hideable')
                <div x-data="{ show: false}">
                    <div class="flex justify-end">
                        <x-button
                            variant="ghost"
                            @click="show = !show"
                            class="flex items-center gap-2"
                        >
                            <span x-show="!show">{{ isset($element['trigger']) ? $element['trigger']['more_text'] : 'more' }}</span>
                            <span x-cloak x-show="show">{{ isset($element['trigger']) ? $element['trigger']['less_text'] : 'less' }}</span>

                            @if (isset($element['trigger']) ? $element['trigger']['show_caret'] : true)
                                <x-toggle />
                            @endif
                        </x-button>
                    </div>

                    @if($element['hidden'])
                        <div x-show="show" x-cloak class="w-full flex flex-col gap-4">
                            @foreach($element['hidden'] as $hidden)
                                <x-form-elements :element="$hidden" :form="$form" :type="$type" :class_key="$class_key" />
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <x-form-elements :element="$element" :form="$form" :type="$type" :class_key="$class_key" />
            @endif
        @endforeach
    </div>
</x-card>
@endif
