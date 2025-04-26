<div 
    x-data="datetime" 
    x-init="init({{ json_encode([
        'initial' => old($name, data_get($attributes->wire('model')->value, 'value', '')),
        'default' => $default ?? null,
        'min' => $min ?? null,
        'max' => $max ?? null
    ]) }})"
    {{ $attributes->wire('model') }}
    x-init="$watch('utc', value => $wire.set('{{ $attributes->wire('model')->value }}', value))"
>
    <flux:heading class="mb-2">{{ $label }}</flux:heading>

    @if(isset($description))
        <flux:text>{{ $description }}</flux:text>
    @endif

    <input
        x-model="local"
        type="datetime-local"
        @change="updateUtc"
        class="mb-4 bg-steel-20 relative text-sm p-2 text-steel-95 block border-2 border-steel-40 rounded-[10px] w-3/4
               focus:bg-steel-20 focus:outline-none"
        :min="min"
        :max="max"
    />

    @error($name)
        <div class="text-red-600 text-sm mb-3">{{ $message }}</div>
    @enderror
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('datetime', () => ({
        local: '',
        utc: '',
        min: null,
        max: null,

        init(config) {
            let starting;

            if (config.initial) {
                starting = new Date(config.initial);
            } else if (config.default) {
                starting = (config.default === 'now')
                    ? new Date()
                    : new Date(config.default);
            }

            if (starting) {
                this.local = toLocalDatetimeString(starting);
                this.utc = starting.toISOString();
            }

            this.min = config.min ? toLocalDatetimeString(new Date(config.min)) : null;
            this.max = config.max ? toLocalDatetimeString(new Date(config.max)) : null;
        },

        updateUtc(e) {
            const localDate = new Date(e.target.value);
            this.utc = localDate.toISOString();
        }
    }))
})

function toLocalDatetimeString(date) {
    if (!date) return '';
    const pad = n => n.toString().padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
</script>
