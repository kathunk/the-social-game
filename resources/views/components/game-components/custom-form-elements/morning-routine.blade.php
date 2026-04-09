@props(['element'])

@php
    $currentLocation = $element['current_location'];
    $roomStates = $element['room_states'];
    $hallwayPlayers = $element['hallway_players'];
    $playerName = $element['player_name'];
    $classKey = \App\Challenges\MorningRoutine\MorningRoutineRound::key();
@endphp

<div x-data="{
    enterRoom(room) {
        $wire.set('round_properties.{{ $classKey }}.room', room);
        $nextTick(() => {
            $wire.callClassAction('enterRoom', 'challenge', '{{ $classKey }}', {{ Js::from($this->challenge_component) }});
        });
    },
    exitRoom() {
        $wire.callClassAction('exitRoom', 'challenge', '{{ $classKey }}', {{ Js::from($this->challenge_component) }});
    }
}">
    @if ($currentLocation === 'hallway')
        {{-- HALLWAY VIEW --}}
        <div class="space-y-4">
            <div class="text-center">
                <h3 class="text-lg font-bold text-gray-800">The Hallway</h3>
                <p class="text-xs text-gray-500">Choose a room to enter</p>
            </div>

            {{-- Room doors --}}
            <div class="grid grid-cols-2 gap-3">
                @foreach ($roomStates as $room => $state)
                    <button
                        @if (!$state['occupied'])
                            x-on:click="enterRoom('{{ $room }}')"
                        @endif
                        class="relative rounded-lg border-2 p-4 text-center transition-all
                            {{ $state['occupied']
                                ? 'border-red-300 bg-red-50 cursor-not-allowed opacity-70'
                                : 'border-green-300 bg-green-50 hover:border-green-500 hover:bg-green-100 cursor-pointer' }}"
                        wire:loading.attr="disabled"
                        @if ($state['occupied']) disabled @endif
                    >
                        {{-- Door status indicator --}}
                        <div class="mb-2 text-2xl">
                            @if ($state['occupied'])
                                🚪
                            @else
                                🚪
                            @endif
                        </div>

                        <div class="font-semibold text-sm capitalize">{{ $room }}</div>

                        <div class="mt-1 text-xs">
                            @if ($state['occupied'])
                                <span class="text-red-600 font-medium">Door closed</span>
                            @else
                                <span class="text-green-600 font-medium">Door open</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>

            {{-- Players in hallway --}}
            <div class="rounded-lg bg-gray-50 border border-gray-200 p-3">
                <div class="text-xs font-medium text-gray-500 mb-2">In the hallway:</div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($hallwayPlayers as $hp)
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                            {{ $hp['is_current_player'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }}">
                            {{ $hp['name'] }}
                            @if ($hp['is_current_player'])
                                <span class="ml-1 text-blue-500">(you)</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        {{-- ROOM VIEW --}}
        <div class="space-y-4">
            <div class="text-center">
                <h3 class="text-lg font-bold text-gray-800 capitalize">The {{ $currentLocation }}</h3>
                <p class="text-xs text-gray-500">You are alone in this room. The door is closed behind you.</p>
            </div>

            <div class="rounded-lg border-2 border-gray-200 bg-gray-50 p-8 text-center">
                <div class="text-4xl mb-3">
                    @switch($currentLocation)
                        @case('bathroom') 🚿 @break
                        @case('laundry') 🧺 @break
                        @case('study') 📚 @break
                        @case('kitchen') 🍳 @break
                    @endswitch
                </div>
                <p class="text-sm text-gray-600">Nothing to do here yet...</p>
            </div>

            <button
                x-on:click="exitRoom()"
                wire:loading.attr="disabled"
                class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-400 transition-all"
            >
                Leave room
            </button>
        </div>
    @endif
</div>
