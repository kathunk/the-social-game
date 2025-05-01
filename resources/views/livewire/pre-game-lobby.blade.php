<div wire:poll="checkStatus" class="flex flex-col gap-4">
    @if ($this->game->status === 'upcoming')
        <div class="mx-auto w-full text-center">
            <flux:text>Starts {{ $this->game->starts_at->diffForHumans() }}</flux:text>
        </div>
    @endif

    @if (! $this->user)
        <flux:card>
            <flux:heading class="text-center mx-auto">Login to join the game</flux:heading>

            <flux:button.group class="flex justify-center mt-2">
                <flux:button variant="primary" href="{{ route('register', ['game' => $this->game->id]) }}">Register</flux:button>
                <flux:button href="{{ route('login', ['game' => $this->game->id]) }}">Login</flux:button>
            </flux:button.group>
        </flux:card>
    @endif

    @if ($this->user && ! $this->player && $this->is_joinable)
        <flux:card class="flex justify-center">
            @if (! $this->application)
                <flux:button variant="primary" wire:click="joinGame" class="w-full">
                    @if($this->requires_admin_approval_to_join)
                        Request to join
                    @else
                        Join
                    @endif
                </flux:button>
            @endif

            @if ($this->application?->status === 'rejected')
                <flux:heading>You were rejected from the game.</flux:heading>
            @endif
        </flux:card>
    @endif

    <flux:card>
        <div class="flex flex-col gap-2">
            {!! $this->description !!}
        </div>
    </flux:card>

    <flux:card>
        <flux:heading class="mb-2">Invite your friends</flux:heading>
        <div class="flex gap-2">
            <flux:modal.trigger name="qr-code">
                <flux:button variant="filled">Show QR <x-icons.qr class="w-4 h-4" /></flux:button>
            </flux:modal.trigger>
            <flux:input icon="link" value="{{ $this->game->url }}" readonly copyable />
            <flux:input icon="key" value="{{ $this->game->code }}" readonly copyable />
        </div>
    </flux:card>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Players</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->players as $player)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <flux:text>{{ $player->name }}</flux:text>
                                @if ($this->admins->pluck('id')->contains($player->user->id))
                                    <x-icons.crown class="text-yellow-500 w-6 h-6" />
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>button</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="qr-code">
        <div class="flex justify-center p-6">
            <x-qr :url="$this->game->url" />
        </div>
    </flux:modal>
</div>

{{-- @todo --}}
{{-- guest visits page --}}
{{-- auth'd user with no application --}}
{{-- rejected user --}}
{{-- accepted user --}}
{{-- game needs reschedule --}}
{{-- admin can move start time --}}
{{-- admin can add admins --}}
{{-- admin can remove admins --}}
{{-- cancel game? --}}
{{-- change game template? feels like that's just a cancel --}}
{{-- toggle ability  --}}