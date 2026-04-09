@props(['element'])

@php
    $currentLocation = $element['current_location'];
    $currentRoomMess = $element['current_room_mess'];
    $currentRoomHeader = $element['current_room_header'];
    $roomStates = $element['room_states'];
    $hallwayPlayers = $element['hallway_players'];
    $roomRewards = $element['room_rewards'];
    $alreadyTookInCurrentRoom = $element['already_took_in_current_room'];
    $canTakeRewardHere = $element['can_take_reward_here'];
    $hasExtraRewardFlag = $element['has_extra_reward_flag'];
    $cleaningState = $element['cleaning_state'];
    $secondsPerMessClean = $element['seconds_per_mess_clean'];
    $playerPoints = $element['player_points'];
    $playerPenalties = $element['player_penalties'];
    $toasts = $element['toasts'];
    $playerName = $element['player_name'];
    $classKey = \App\Challenges\MorningRoutine\MorningRoutineRound::key();

    // Build a map of reward key → flavor for the reveal animation
    $rewardFlavorMap = collect(\App\Challenges\MorningRoutine\Rewards\RewardRegistry::all())
        ->mapWithKeys(fn ($r) => [$r->key => [
            'name' => $r->name,
            'flavor' => $r->flavor,
            'points' => $r->points,
            'mess' => $r->mess,
            'effect_description' => $r->effect_description,
        ]])
        ->all();
@endphp

<div
    x-data="{
        cleaningStartedAt: @js($cleaningState['started_at'] ?? null),
        cleaningRoom: @js($cleaningState['room'] ?? null),
        secondsPerMess: {{ $secondsPerMessClean }},
        cleaningElapsed: 0,
        cleaningInterval: null,
        rewards: @js($rewardFlavorMap),
        currentRoomMess: {{ $currentRoomMess }},

        init() {
            this.startCleaningTicker();
            this.$watch('cleaningStartedAt', () => this.startCleaningTicker());
        },

        startCleaningTicker() {
            if (this.cleaningInterval) {
                clearInterval(this.cleaningInterval);
                this.cleaningInterval = null;
            }
            if (!this.cleaningStartedAt) {
                this.cleaningElapsed = 0;
                return;
            }
            const tick = () => {
                this.cleaningElapsed = Math.floor(Date.now() / 1000) - this.cleaningStartedAt;
            };
            tick();
            this.cleaningInterval = setInterval(tick, 250);
        },

        get cleaningProgressMess() {
            return Math.min(Math.floor(this.cleaningElapsed / this.secondsPerMess), this.currentRoomMess);
        },

        get displayMess() {
            return Math.max(0, this.currentRoomMess - this.cleaningProgressMess);
        },

        takeReward(rewardKey) {
            const reward = this.rewards[rewardKey];
            window.dispatchEvent(new CustomEvent('mr-reveal-reward', { detail: reward }));
            this.$wire.set('round_properties.{{ $classKey }}.reward_key', rewardKey);
            this.$nextTick(() => {
                this.$wire.callClassAction('takeReward', 'challenge', '{{ $classKey }}', {{ Js::from($this->challenge_component) }});
            });
        },

        enterRoom(room) {
            this.$wire.set('round_properties.{{ $classKey }}.room', room);
            this.$nextTick(() => {
                this.$wire.callClassAction('enterRoom', 'challenge', '{{ $classKey }}', {{ Js::from($this->challenge_component) }});
            });
        },

        queueForRoom(room) {
            this.$wire.set('round_properties.{{ $classKey }}.room', room);
            this.$nextTick(() => {
                this.$wire.callClassAction('queueForRoom', 'challenge', '{{ $classKey }}', {{ Js::from($this->challenge_component) }});
            });
        },

        leaveQueue(room) {
            this.$wire.set('round_properties.{{ $classKey }}.room', room);
            this.$nextTick(() => {
                this.$wire.callClassAction('leaveQueue', 'challenge', '{{ $classKey }}', {{ Js::from($this->challenge_component) }});
            });
        },

        exitRoom() {
            this.$wire.callClassAction('exitRoom', 'challenge', '{{ $classKey }}', {{ Js::from($this->challenge_component) }});
        },

        startCleaning() {
            this.$wire.callClassAction('startCleaning', 'challenge', '{{ $classKey }}', {{ Js::from($this->challenge_component) }});
        },

        stopCleaning() {
            this.$wire.callClassAction('stopCleaning', 'challenge', '{{ $classKey }}', {{ Js::from($this->challenge_component) }});
        }
    }"
    class="relative"
>
    {{-- TOAST NOTIFICATIONS - rendered per-toast with localStorage dedupe --}}
    <div class="fixed top-4 left-1/2 -translate-x-1/2 z-50 flex flex-col gap-2 pointer-events-none">
        @foreach ($toasts as $toast)
            @php
                $toastId = $toast['created_at'].'-'.$toast['type'].'-'.md5($toast['message'] ?? '');
                $toastClass = match ($toast['type']) {
                    'busted' => 'bg-red-500 text-white',
                    'busted_someone' => 'bg-green-500 text-white',
                    'junk_drawer' => 'bg-purple-500 text-white',
                    default => 'bg-gray-700 text-white',
                };
            @endphp
            <div
                wire:key="toast-{{ $toastId }}"
                x-data="{
                    visible: false,
                    init() {
                        const seen = new Set(JSON.parse(localStorage.getItem('mr_shown_toasts') || '[]'));
                        if (seen.has('{{ $toastId }}')) return;
                        seen.add('{{ $toastId }}');
                        localStorage.setItem('mr_shown_toasts', JSON.stringify([...seen].slice(-100)));
                        this.visible = true;
                        setTimeout(() => this.visible = false, 5000);
                    }
                }"
                x-show="visible"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-end="opacity-0"
                class="rounded-lg shadow-lg px-4 py-3 text-sm font-medium pointer-events-auto {{ $toastClass }}"
                style="display:none"
            >
                {{ $toast['message'] }}
            </div>
        @endforeach
    </div>

    {{-- REWARD REVEAL ANIMATION (separate Alpine sub-component with stable x-data
         so it survives Livewire morphs / re-renders) --}}
    <div
        x-data="{ reward: null }"
        @mr-reveal-reward.window="reward = $event.detail"
        @keydown.escape.window="reward = null"
    >
        <div
            x-show="reward !== null"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-cloak
            @click="reward = null"
            class="fixed inset-0 bg-black/60 z-40 flex items-center justify-center p-4 cursor-pointer"
        >
            <div
                x-show="reward !== null"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="scale-50 rotate-12 opacity-0"
                x-transition:enter-end="scale-100 rotate-0 opacity-100"
                class="bg-white rounded-2xl p-6 max-w-sm shadow-2xl border-4 border-yellow-400"
                @click.stop
            >
                <div class="text-center space-y-3">
                    <div class="text-3xl">⭐</div>
                    <h3 class="text-xl font-bold" x-text="reward?.name"></h3>
                    <p class="italic text-gray-600 text-sm" x-text="'&ldquo;' + (reward?.flavor ?? '') + '&rdquo;'"></p>

                    <div class="flex justify-center gap-3 pt-2">
                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                            +<span x-text="reward?.points"></span> pts
                        </span>
                        <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                            +<span x-text="reward?.mess"></span> mess
                        </span>
                    </div>

                    <template x-if="reward?.effect_description">
                        <p class="text-xs text-purple-700 bg-purple-50 rounded-lg p-2 mt-2" x-text="reward.effect_description"></p>
                    </template>

                    <button
                        @click="reward = null"
                        class="mt-3 w-full rounded-lg bg-gray-100 hover:bg-gray-200 px-4 py-2 text-sm font-medium"
                    >
                        Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- HEADER: SCORE --}}
    <div class="mb-4 flex justify-between items-center text-sm">
        <div class="flex gap-3">
            <span class="rounded-full bg-green-100 px-3 py-1 font-semibold text-green-700">
                {{ $playerPoints }} pts
            </span>
            @if ($playerPenalties > 0)
                <span class="rounded-full bg-red-100 px-3 py-1 font-semibold text-red-700">
                    -{{ $playerPenalties }} mess
                </span>
            @endif
        </div>
    </div>

    @if ($currentLocation === 'hallway')
        {{-- HALLWAY VIEW --}}
        <div class="space-y-4">
            <div class="text-center">
                <h3 class="text-lg font-bold text-gray-800">The Hallway</h3>
                <p class="text-xs text-gray-500">Choose a room. Open doors are empty.</p>
            </div>

            {{-- Room doors --}}
            <div class="grid grid-cols-2 gap-3">
                @foreach ($roomStates as $room => $state)
                    @php
                        $isQueuedHere = $state['queued_is_current_player'];
                        $canEnter = !$state['occupied'];
                        $canQueue = $state['occupied'] && !$state['queued'];
                    @endphp
                    <button
                        @if ($canEnter)
                            x-on:click="enterRoom('{{ $room }}')"
                        @elseif ($isQueuedHere)
                            x-on:click="leaveQueue('{{ $room }}')"
                        @elseif ($canQueue)
                            x-on:click="queueForRoom('{{ $room }}')"
                        @endif
                        @if (!$canEnter && !$canQueue && !$isQueuedHere) disabled @endif
                        wire:loading.attr="disabled"
                        class="relative rounded-lg border-2 p-4 text-center transition-all
                            @if ($isQueuedHere)
                                border-blue-400 bg-blue-50 hover:bg-blue-100 cursor-pointer
                            @elseif ($canEnter)
                                border-green-300 bg-green-50 hover:border-green-500 hover:bg-green-100 cursor-pointer
                            @elseif ($canQueue)
                                border-yellow-300 bg-yellow-50 hover:border-yellow-500 hover:bg-yellow-100 cursor-pointer
                            @else
                                border-gray-300 bg-gray-50 cursor-not-allowed opacity-70
                            @endif
                        "
                    >
                        <div class="mb-2 text-2xl">🚪</div>
                        <div class="font-semibold text-sm capitalize">{{ $room }}</div>
                        <div class="text-xs text-gray-500">{{ $state['header'] }}</div>

                        <div class="mt-1 text-xs">
                            @if ($state['occupied'])
                                <span class="text-red-600 font-medium">Closed</span>
                            @else
                                <span class="text-green-600 font-medium">Open</span>
                            @endif
                        </div>

                        @if ($state['mess_visible'])
                            <div class="mt-1 text-xxs text-orange-600">
                                Mess: {{ $state['mess'] }}
                            </div>
                        @endif

                        @if ($isQueuedHere)
                            <div class="mt-2 inline-block rounded-full bg-blue-200 px-2 py-0.5 text-xxs font-medium text-blue-800">
                                You're queued — tap to cancel
                            </div>
                        @elseif ($state['queued'])
                            <div class="mt-2 inline-block rounded-full bg-yellow-200 px-2 py-0.5 text-xxs font-medium text-yellow-800">
                                {{ $state['queued_player_name'] }} queued
                            </div>
                        @endif
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
                <p class="text-xs text-gray-500">{{ $currentRoomHeader }}</p>
            </div>

            {{-- Room mess meter (live: decreases as you clean) --}}
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-3">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-orange-700">Room mess</span>
                    <span class="text-xs font-bold text-orange-800" x-text="displayMess"></span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-orange-100">
                    <div class="h-full bg-orange-500 transition-all" :style="`width: ${Math.min(100, displayMess * 10)}%`"></div>
                </div>
            </div>

            {{-- Cleaning UI --}}
            @if ($currentRoomMess > 0 || $cleaningState !== null)
                <div class="rounded-lg border-2 border-blue-200 bg-blue-50 p-3">
                    <template x-if="cleaningStartedAt === null">
                        <button
                            @click="startCleaning()"
                            wire:loading.attr="disabled"
                            class="w-full rounded-lg bg-blue-500 hover:bg-blue-600 px-4 py-2 text-sm font-semibold text-white"
                        >
                            🧹 Start cleaning
                        </button>
                    </template>
                    <template x-if="cleaningStartedAt !== null">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs font-medium text-blue-700">
                                <span>Cleaning... <span x-text="cleaningProgressMess"></span> / {{ $currentRoomMess }} mess</span>
                                <span x-text="cleaningElapsed + 's'"></span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-blue-100">
                                <div class="h-full bg-blue-500 transition-all" :style="`width: ${Math.min(100, (cleaningProgressMess / Math.max(1, currentRoomMess)) * 100)}%`"></div>
                            </div>
                            <button
                                @click="stopCleaning()"
                                wire:loading.attr="disabled"
                                class="w-full rounded-lg border-2 border-blue-300 bg-white hover:bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700"
                            >
                                Stop cleaning
                            </button>
                        </div>
                    </template>
                </div>
            @endif

            {{-- Available rewards --}}
            @if ($canTakeRewardHere && count($roomRewards) > 0)
                <div class="space-y-2">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                        Choose one item for your morning routine
                        @if ($hasExtraRewardFlag)
                            <span class="ml-1 rounded-full bg-purple-100 px-2 py-0.5 text-xxs font-semibold text-purple-700">Bonus pick</span>
                        @endif
                    </div>
                    @foreach ($roomRewards as $reward)
                        <button
                            x-on:click="takeReward('{{ $reward['key'] }}')"
                            wire:loading.attr="disabled"
                            class="w-full rounded-lg border-2 border-gray-200 bg-white hover:border-yellow-400 hover:bg-yellow-50 px-4 py-3 text-left transition-all"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold text-sm">{{ $reward['name'] }}</span>
                                    <div class="flex gap-1 shrink-0">
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xxs font-semibold text-green-700">
                                            +{{ $reward['points'] }} pts
                                        </span>
                                        <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xxs font-semibold text-orange-700">
                                            +{{ $reward['mess'] }} mess
                                        </span>
                                    </div>
                                </div>
                                @if (! empty($reward['effect_description']))
                                    <p class="text-xxs text-purple-700 italic">{{ $reward['effect_description'] }}</p>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            @elseif (! $canTakeRewardHere)
                <div class="rounded-lg bg-gray-50 border border-gray-200 p-3 text-center text-xs text-gray-500">
                    You've already taken an action here.
                </div>
            @endif

            {{-- Leave room button --}}
            <button
                @click="exitRoom()"
                wire:loading.attr="disabled"
                class="w-full rounded-lg border-2 border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-400 transition-all"
            >
                Leave room
            </button>
        </div>
    @endif
</div>
