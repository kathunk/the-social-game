<div>
    @if ($this->player)
        <x-card>
            <flux:heading>Want to be notified as this game progresses?</flux:heading>
            <flux:subheading>We'll ping you when new rounds start and when the game ends — only on the channels you turn on here.</flux:subheading>

            <div class="mt-4 flex flex-col gap-3">
                @foreach ($this->configuredChannels as $key => $label)
                    <flux:field variant="inline">
                        <flux:switch wire:model.live="channels.{{ $key }}" />
                        <flux:label>{{ $label }}</flux:label>
                    </flux:field>
                @endforeach
            </div>

            <flux:text class="mt-4">
                Connect more channels like Discord or Telegram in your <flux:link variant="ghost" href="{{ route('settings.profile') }}">profile settings</flux:link>.
            </flux:text>
        </x-card>
    @endif
</div>
