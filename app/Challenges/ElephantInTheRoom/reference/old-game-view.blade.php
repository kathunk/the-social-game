@script
<script type="module">
    window.gameBoard = function() {
        const defaults = {
            is_player_turn: {{ $this->is_player_turn ? 'true' : 'false' }},
            game_status: '{{ $this->game_status }}',
            phase: '{{ $this->phase }}',
            elephant_space: {{ $this->elephant_space }},
            player_hand: {{ $this->player_hand }},
            opponent_hand: {{ $this->opponent_hand }},
            opponent_is_friend: '{{ $this->opponent_is_friend }}',
            victor_ids: @json($this->victor_ids),
            winning_spaces: @json($this->winning_spaces),
            player_is_victor: {{ $this->player_is_victor ? 'true' : 'false' }},
            opponent_is_victor: {{ $this->opponent_is_victor ? 'true' : 'false' }},
            player_forfeits_at: @json($this->player_forfeits_at ?? null),
            player_victory_shape: '{{ $this->player->victory_shape }}',
            opponent_victory_shape: '{{ $this->opponent->victory_shape }}',
            player_id: '{{ $this->player->id }}',
            opponent_id: '{{ $this->opponent->id }}',
            player_rating: {{ $this->player->user->rating }},
            opponent_rating: {{ $this->opponent->user->rating }},
            player_wants_rematch: {{ $this->player->wants_rematch ? 'true' : 'false' }},
            known_move_ids: @json($this->moves->map(fn($move) => (string) $move->id)),
        };

        return {
            known_move_ids: defaults.known_move_ids,
            player_wants_rematch: defaults.player_wants_rematch,
            player_rating: defaults.player_rating,
            opponent_rating: defaults.opponent_rating,
            player_id: defaults.player_id,
            opponent_id: defaults.opponent_id,
            player_victory_shape: defaults.player_victory_shape,
            opponent_victory_shape: defaults.opponent_victory_shape,
            player_forfeits_at: defaults.player_forfeits_at,
            victor_ids: defaults.victor_ids,
            player_is_victor: defaults.player_is_victor,
            opponent_is_victor: defaults.opponent_is_victor,
            winning_spaces: defaults.winning_spaces,
            is_player_turn: defaults.is_player_turn,
            phase: @entangle('phase'),
            animating: false,
            game_status: @entangle('game_status'),
            tiles: [],
            elephant_space: @entangle('elephant_space'),
            nextId: 1,
            init: 'true',
            player_hand: @entangle('player_hand'),
            opponent_hand: @entangle('opponent_hand'),
            valid_elephant_moves: @entangle('valid_elephant_moves'),
            valid_slides: @entangle('valid_slides'),
            opponent_is_friend: defaults.opponent_is_friend,
            isMuted: localStorage.getItem('gameAudioMuted') === 'true' || false,
            get tile_phase() {
                return !this.animating && this.is_player_turn && this.phase === 'tile' && this.game_status === 'active';
            },
            get elephant_phase() {
                return !this.animating && this.is_player_turn && this.phase === 'move' && this.game_status === 'active';
            },

            playSound(filename) {
                if (!this.isMuted) {
                    const audio = new Audio(`/audio/${filename}`);
                    audio.play();
                }
            },
            toggleMute() {
                this.isMuted = !this.isMuted;
                localStorage.setItem('gameAudioMuted', this.isMuted);
            },

            // Methods
            spaceToCoords(space) {
                const row = Math.floor((space - 1) / 4);
                const col = (space - 1) % 4;
                return {
                    x: col * 60 + col,
                    y: row * 60 + row
                };
            },

            initializeTilesAndElephant() {
                @foreach($this->tiles as $space => $playerId)
                    this.tiles.push({
                        id: this.nextId++,
                        x: this.spaceToCoords({{ $space }}).x,
                        y: this.spaceToCoords({{ $space }}).y,
                        playerId: '{{ $playerId }}',
                        space: {{ $space }}
                    });
                @endforeach

                const elephant_coords = this.spaceToCoords(this.elephant_space);
                this.$refs.elephant.style.transform = `translate(${elephant_coords.x}px, ${elephant_coords.y}px)`;
                
                setTimeout(() => {
                    this.init = false;
                }, 100);
            },

            moveElephant(player_id, space, previous_space) {
                if (space !== previous_space) {
                    this.playSound(`elephant_${Math.floor(Math.random() * 2) + 1}.mp3`);
                }

                if (player_id === this.player_id && this.opponent_hand > 0) {
                    this.player_forfeits_at = null;
                }

                if (player_id === this.player_id && this.opponent_hand === 0) {
                    this.player_forfeits_at = new Date(Date.now() + 30000).toISOString();
                }

                this.animating = true;
                this.elephant_space = space;
                const coords = this.spaceToCoords(space);
                this.$refs.elephant.style.transform = `translate(${coords.x}px, ${coords.y}px)`;
                
                this.phase = 'tile';

                if (this.is_player_turn && this.opponent_hand > 0) {
                    this.is_player_turn = false;
                } 
                
                else if (!this.is_player_turn && this.player_hand > 0) {
                    this.is_player_turn = true;
                }
                
                this.animating = false;
            },

            playTile(direction, position, player_id) {
                this.playSound(`slide_${Math.floor(Math.random() * 5) + 1}.mp3`);
                this.animating = true;
                this.phase = 'move';

                if (player_id === this.player_id) {
                    this.player_hand--;
                } else {
                    this.opponent_hand--;
                }

                const startPosition = {
                    from_left:   { x: -60,     y: position * 60 },
                    from_right:  { x: 240,     y: position * 60 },
                    down:    { x: position * 60, y: -60 },
                    up: { x: position * 60, y: 240 }
                };

                const targetSpace = {
                    from_left:   position * 4 + 1,
                    from_right:  (position * 4) + 4,
                    down:    position + 1,
                    up: position + 13
                }[direction];

                const shiftTilesFrom = (space, depth = 0) => {
                    const existingTile = this.tiles.find(tile => tile.space === space);
                    if (!existingTile) return;

                    const currentRow = Math.floor((space - 1) / 4);
                    const nextSpace = {
                        from_left: space + 1,
                        from_right: space - 1,
                        down: space + 4,
                        up: space - 4
                    }[direction];
                    
                    const isValidForRow = (spaceNum, row) => {
                        const spaceRow = Math.floor((spaceNum - 1) / 4);
                        return spaceRow === row;
                    };

                    if (direction === 'from_left' || direction === 'from_right') {
                        if (!isValidForRow(space, currentRow)) {
                            return;
                        }
                    }

                    if ((direction === 'from_left' || direction === 'from_right') && isValidForRow(nextSpace, currentRow)) {
                        shiftTilesFrom(nextSpace, depth + 1);
                    } else if (direction === 'up' || direction === 'down') {
                        shiftTilesFrom(nextSpace, depth + 1);
                    }

                    if (depth === 3) {
                        const currentX = existingTile.x || 0;
                        const currentY = existingTile.y || 0;

                        const exitPosition = {
                            from_left:   { x: 240, y: currentY },
                            from_right:  { x: -60, y: currentY },
                            down:    { x: currentX, y: 240 },
                            up: { x: currentX, y: -60 }
                        }[direction];

                        if (exitPosition) {
                            const updatedTiles = this.tiles.map(tile => {
                                if (tile.id === existingTile.id) {
                                    return {
                                        ...tile,
                                        x: exitPosition.x,
                                        y: exitPosition.y,
                                        opacity: 0,
                                        scale: 0.5
                                    };
                                }
                                return tile;
                            });
                            this.tiles = updatedTiles;

                            if(existingTile.playerId === this.player_id) {
                                this.player_hand++;
                            } else {
                                this.opponent_hand++;
                            }

                            setTimeout(() => {
                                this.tiles = this.tiles.filter(tile => tile.id !== existingTile.id);
                            }, 700);
                        }
                    } else {
                        const nextCoords = this.spaceToCoords(nextSpace);
                        const updatedTiles = this.tiles.map(tile => {
                            if (tile.id === existingTile.id) {
                                return {
                                    ...tile,
                                    space: nextSpace,
                                    x: nextCoords.x,
                                    y: nextCoords.y
                                };
                            }
                            return tile;
                        });
                        this.tiles = updatedTiles;
                    }
                };

                shiftTilesFrom(targetSpace);

                const tile_coords = this.spaceToCoords(targetSpace);
                const finalPosition = {
                    x: tile_coords.x,
                    y: tile_coords.y
                };

                const newTile = {
                    id: this.nextId++,
                    x: startPosition[direction].x,
                    y: startPosition[direction].y,
                    playerId: player_id, 
                    space: targetSpace
                };
                this.tiles.push(newTile);
                
                setTimeout(() => {
                    const updatedTiles = this.tiles.map(tile => {
                        if (tile.id === newTile.id) {
                            return {
                                id: tile.id,
                                playerId: tile.playerId,
                                space: tile.space,
                                x: finalPosition.x,
                                y: finalPosition.y
                            };
                        }
                        return tile;
                    });
                    this.tiles = updatedTiles;
                }, 50);

                setTimeout(() => {
                    this.animating = false;
                }, 500);  // 1000ms = 1 second

                setTimeout(() => {
                    player_victory_status = victory_status(this.tiles, this.player_victory_shape, this.player_id);
                    opponent_victory_status = victory_status(this.tiles, this.opponent_victory_shape, this.opponent_id);

                    if (player_victory_status.has_won) {
                        this.victor_ids.push(this.player_id);
                        this.game_status = 'complete';
                        this.winning_spaces.push(...player_victory_status.winning_spaces);
                        this.player_forfeits_at = null;
                        this.player_is_victor = true;
                    }

                    if (opponent_victory_status.has_won) {
                        this.victor_ids.push(this.opponent_id);
                        this.game_status = 'complete';
                        this.winning_spaces.push(...opponent_victory_status.winning_spaces);
                        this.player_forfeits_at = null;
                        this.opponent_is_victor = true;
                    }

                    if (this.player_hand === 0 && this.opponent_hand === 0 && this.game_status === 'active') {
                        this.game_status = 'complete';
                        this.player_forfeits_at = null;
                    }
                }, 500);
            },

            init() {
                this.initializeTilesAndElephant();

                // Watch for victory
                this.$watch('player_is_victor', value => {
                    if (value === true) {
                        this.playSound(`victory.mp3`);
                    }
                });

                this.$watch('opponent_is_victor', value => {
                    if (value === true) {
                        this.playSound(`defeat.mp3`);
                    }
                });

                // Set up watchers
                this.$watch('phase', value => {
                    this.phase = value;
                });

                this.$watch('game_status', value => {
                    this.game_status = value;
                });

                this.$watch('valid_elephant_moves', value => {
                    this.valid_elephant_moves = value;
                });

                this.$watch('valid_slides', value => {
                    this.valid_slides = value;
                });

                this.$watch('player_hand', value => {
                    this.player_hand = value;
                });

                this.$watch('opponent_hand', value => {
                    this.opponent_hand = value;
                });

                this.$wire.on('opponent-played-tile', (data) => {
                    this.playTile(data[0].direction, data[0].position, data[0].player_id);
                });

                this.$wire.on('opponent-moved-elephant', (data) => {
                    if (!this.known_move_ids.includes(data[0].tile_move_id)) {
                        this.playTile(data[0].tile_direction, data[0].tile_position, data[0].player_id);
                        this.known_move_ids.push(data[0].tile_move_id);
                    } 

                    if (!this.known_move_ids.includes(data[0].elephant_move_id)) {
                        setTimeout(() => {
                            this.moveElephant(data[0].player_id, data[0].elephant_move_position, data[0].previous_elephant_space);
                            this.known_move_ids.push(data[0].elephant_move_id);
                        }, 700);
                    } 

                    this.player_forfeits_at = data[0].player_forfeits_at;
                });

                this.$wire.on('friend-status-changed', (data) => {
                    this.opponent_is_friend = data[0].status;
                });

                this.$wire.on('game-ended', (data) => {
                    if (!this.animating) {
                        this.game_status = data[0].status;
                        this.victor_ids = data[0].victor_ids;
                        this.winning_spaces = data[0].winning_spaces;
                        this.player_is_victor = data[0].player_is_victor;
                        this.opponent_is_victor = data[0].opponent_is_victor;
                        this.player_rating = data[0].player_rating;
                        this.opponent_rating = data[0].opponent_rating;
                    } else {
                        setTimeout(() => {
                            this.game_status = data[0].status;
                            this.victor_ids = data[0].victor_ids;
                            this.winning_spaces = data[0].winning_spaces;
                            this.player_is_victor = data[0].player_is_victor;
                            this.opponent_is_victor = data[0].opponent_is_victor;
                            this.player_rating = data[0].player_rating;
                            this.opponent_rating = data[0].opponent_rating;
                        }, 700);
                    }
                });
            }
        }
    }
</script>
@endscript

<div 
    x-data="gameBoard()"
    wire:ignore
    class="flex items-center justify-center flex-col space-y-8"
    wire:poll.4000ms="updateFromPoll"
>
    {{-- player info --}}
    <div class="flex flex-col items-center justify-center mt-10 space-y-4 w-[300px]">
        <!-- Player section -->
        <div class="w-full p-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg"
            :class="{ 
                'victory-wave-glow': player_is_victor,
                'animate-pulse': is_player_turn && game_status === 'active' 
            }"
        >
            <div class="flex justify-between items-center text-zinc-800 dark:text-zinc-200">
                <div class="space-y-2">
                    <flux:subheading class="text-left" size="sm">
                        {{ $this->player->user->name }}
                    </flux:subheading>
                    <div class="flex items-center space-x-2">
                        <div class="bg-orange dark:bg-dark-orange w-6 h-6 rounded-lg flex items-center justify-center">
                            <p class="font-bold text-white" x-text="player_hand"></p>
                        </div>
                        <flux:badge color="gray" size="sm" variant="outline" icon="star">
                            <span x-text="player_rating"></span>
                        </flux:badge>
                    </div>
                </div>
                
                <x-dynamic-component 
                    :component="'svg.' . $this->player->victory_shape"
                    class="w-14 h-14"
                />
            </div>
        </div>

        <!-- Opponent section -->
        <div class="w-full p-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg"
            :class="{ 
                'victory-wave-glow': opponent_is_victor,
                'animate-pulse': !is_player_turn && game_status === 'active' 
            }"
        >
            <div class="flex justify-between items-center text-zinc-800 dark:text-zinc-200">
                <div class="space-y-2">
                    <flux:subheading class="text-left" size="sm">
                        {{ $this->opponent->user->name }}
                    </flux:subheading>
                    <div class="flex items-center space-x-2">
                        <div class="bg-light-teal dark:bg-dark-teal w-6 h-6 rounded-lg flex items-center justify-center">
                            <p class="font-bold text-white" x-text="opponent_hand"></p>
                        </div>
                        @unless($this->opponent->user->email === 'bot@bot.bot')
                            <flux:badge color="gray" size="sm" variant="outline" icon="star">
                                <span x-text="opponent_rating"></span>
                            </flux:badge>
                            <template x-if="opponent_is_friend === 'request_incoming'">
                                <flux:badge as="button" variant="ghost" inset size="sm" wire:click="sendFriendRequest" icon="user-plus">Confirm</flux:badge>
                            </template>
                            <template x-if="opponent_is_friend === 'request_outgoing'">
                                <flux:badge size="sm" color="gray" icon="user">Request sent</flux:badge>
                            </template>
                            <template x-if="opponent_is_friend === 'not_friends'">
                                <flux:badge as="button" variant="ghost" inset size="sm" wire:click="sendFriendRequest" icon="user-plus">Add</flux:badge>
                            </template>
                            <template x-if="opponent_is_friend === 'friends'">
                                <flux:badge size="sm" color="green" icon="user">Friends</flux:badge>
                            </template>
                        @endunless
                    </div>
                </div>
                
                <x-dynamic-component 
                    :component="'svg.' . $this->opponent->victory_shape"
                    class="w-14 h-14"
                />
            </div>
        </div>
    </div>

    {{-- Gameboard --}}
    <div class="inline-grid grid-cols-[auto_240px_auto] grid-rows-[auto_240px_auto] gap-1">
        <!-- Top area -->
        <div class="col-start-2 h-8">
            <div class="grid grid-cols-4 gap-1" x-show="tile_phase">
                <template x-for="i in 4">
                    <div>
                        <button 
                            @click="playTile('down', i-1, player_id); $wire.playTile('down', i)"
                            class="w-[58px] h-8 animate-pulse flex items-center justify-center"
                            x-show="Object.values(valid_slides).some(slide => slide['space'] === i && slide['direction'] === 'down')"
                        >
                            ↓
                        </button>
                        <div x-show="!Object.values(valid_slides).some(slide => slide['space'] === i && slide['direction'] === 'down')">
                            <div class="w-[58px] h-8"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Left area -->
        <div class="col-start-1 w-8">
            <div class="grid grid-rows-4 gap-1" x-show="tile_phase">
                <template x-for="i in 4" >
                    <div>
                        <button 
                            @click="playTile('from_left', i-1, player_id); $wire.playTile('right', i)"
                            class="h-[58px] w-8 animate-pulse flex items-center justify-center"
                            x-show="Object.values(valid_slides).some(slide => slide['space'] === 1 + (i - 1) * 4 && slide['direction'] === 'right')"
                        >
                            →
                        </button>
                        <div x-show="!Object.values(valid_slides).some(slide => slide['space'] === 1 + (i - 1) * 4 && slide['direction'] === 'right')">
                            <div class="h-[58px] w-8"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Main grid -->
        <div class="relative h-[240px] w-[240px] grid grid-cols-4 grid-rows-4 gap-1">
            <!-- Grid spaces -->
            <template x-for="i in 16">
                <div class="relative">
                    <button 
                        x-show="elephant_phase && valid_elephant_moves.includes(i) && game_status === 'active' && is_player_turn"
                        @click="moveElephant(player_id, i, elephant_space); $wire.moveElephant(i)" 
                        class="absolute inset-0 bg-slate-800 dark:bg-slate-100 opacity-20 animate-pulse rounded-lg z-20"
                    ></button>
                    <div 
                        class="absolute inset-0 bg-gray-100 dark:opacity-20 dark:bg-zinc-700 rounded-lg"
                        x-show="!elephant_phase || !valid_elephant_moves.includes(i)"
                    ></div>
                </div>
            </template>
            
            <!-- Tiles -->
            <template x-for="tile in tiles" :key="tile.id">
                <div 
                    class="absolute w-[58px] h-[58px] rounded-lg transition-all duration-700 ease-in-out"
                    :class="{
                        'bg-orange dark:bg-dark-orange': tile.playerId === player_id,
                        'bg-light-teal dark:bg-dark-teal': tile.playerId === opponent_id,
                        'victory-wave-glow': winning_spaces.includes(tile.space)
                    }"
                    :style="`
                        --x: ${tile.x}px;
                        --y: ${tile.y}px;
                        transform: translate(${tile.x}px, ${tile.y}px);
                        opacity: ${tile.opacity === undefined ? 1 : tile.opacity};
                    `"
                ></div>
            </template>

            <!-- Elephant -->
            <div 
                x-ref="elephant"
                class="absolute w-[58px] h-[58px]"
                :class="{ 'transition-all duration-700 ease-in-out': !init }"
            >
                <x-svg.elephant class="w-11 h-11 dark:text-white text-gray-900 mx-auto mt-2 z-90"/>
            </div>
        </div>

        <!-- Right area -->
        <div class="col-start-3 w-8">
            <div class="grid grid-rows-4 gap-1" x-show="tile_phase">
                <template x-for="i in 4">
                    <div>
                        <button 
                            @click="playTile('from_right', i-1, player_id); $wire.playTile('left', i)"
                            class="h-[58px] w-8 animate-pulse rounded-lg flex items-center justify-center"
                            x-show="Object.values(valid_slides).some(slide => slide['space'] === i * 4 && slide['direction'] === 'left')"
                        >
                            ←
                        </button>
                        <div x-show="!Object.values(valid_slides).some(slide => slide['space'] === i * 4 && slide['direction'] === 'left')">
                            <div class="h-[58px] w-8"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Bottom area -->
        <div class="col-start-2 h-8">
            <div class="grid grid-cols-4 gap-1" x-show="tile_phase">
                <template x-for="i in 4">
                    <div>
                        <button 
                            @click="playTile('up', i-1, player_id); $wire.playTile('up', i)"
                            class="w-[58px] h-8 animate-pulse flex items-center justify-center"
                            x-show="Object.values(valid_slides).some(slide => slide['space'] === i +12 && slide['direction'] === 'up')"
                        >
                            ↑
                        </button>
                        <div x-show="!Object.values(valid_slides).some(slide => slide['space'] === i + 12 && slide['direction'] === 'up')">
                            <div class="w-[58px] h-8"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div x-show="player_forfeits_at === null && game_status === 'active'">
        <p class="text-sm text-slate-800 -mt-2 dark:text-gray-200 animate-pulse">Opponent is thinking...</p>
    </div>

    <template x-if="player_forfeits_at">
        <div class="w-[240px] mt-4">
            <div 
                x-data="{
                    progress: 0,
                    isUrgent: false,
                    hasExpired: false,
                    updateProgress() {
                        if (! player_forfeits_at) {
                            clearInterval(this.progressInterval)

                            return
                        }
                        const now = new Date();
                        const forfeitTime = new Date(this.player_forfeits_at);
                        const startTime = new Date(forfeitTime - 35000);
                        const totalDuration = forfeitTime - startTime;
                        const elapsed = now - startTime;
                        this.progress = Math.min(100, Math.max(0, (elapsed / totalDuration) * 100));
                        this.isUrgent = (forfeitTime - now) / 1000 <= 10;
                        
                        // Check if timer just hit zero and hasn't been handled yet
                        if (!this.hasExpired && now >= forfeitTime) {
                            this.hasExpired = true;
                            this.player_forfeits_at = null;
                            this.game_status = 'complete';
                            this.opponent_is_victor = true;
                            $wire.handleForfeit();
                        }
                    },
                    progressInterval: null
                }"
                x-init="
                    updateProgress();
                    progressInterval = setInterval(() => updateProgress(), 100)
                "
                class="h-2 bg-gray-200 dark:bg-zinc-700 rounded-full overflow-hidden"
            >
                <div 
                    class="h-full transition-all duration-100 ease-linear"
                    :class="{ 
                        'animate-pulse bg-red-500': isUrgent,
                        'bg-gray-700 dark:bg-gray-500': !isUrgent 
                    }"
                    :style="`width: ${progress}%`"
                ></div>
            </div>
        </div>
    </template>

    {{-- Mute button --}}
    <div class="fixed bottom-4 right-4">
        <template x-if="isMuted">
            <flux:button @click="toggleMute()" variant="subtle" icon="speaker-x-mark" />
        </template>
        <template x-if="!isMuted">
            <flux:button @click="toggleMute()" variant="subtle" icon="speaker-wave" />
        </template>
    </div>
    <template x-if="game_status === 'complete'">
        <div class="relative" style="top: -2rem;">
            <template x-if="player_wants_rematch">
                <div>
                    <flux:subheading>Rematch requested</flux:subheading>
                </div>
            </template>
            <template x-if="!player_wants_rematch">
                <flux:button 
                    wire:click="requestRematch" 
                    @click="player_wants_rematch = true"
                >
                    Rematch
                </flux:button>
            </template>
        </div>
    </template>
</div>