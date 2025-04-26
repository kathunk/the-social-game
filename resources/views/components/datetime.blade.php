<div 
    x-data="datetime({{ json_encode([
        'initial' => old($name, data_get($attributes->wire('model')->value, 'value', '')),
        'min' => $min ?? null,
        'max' => $max ?? null
    ]) }})"
    x-init="$watch('utc', value => $wire.set('{{ $attributes->wire('model')->value }}', value))"
>
    <flux:heading>{{ $label }}</flux:heading>

    @if(isset($description))
        <flux:text>{{ $description }}</flux:text>
    @endif

    <input
        x-model="local"
        type="datetime-local"
        @change="updateUtc"
        class="mb-4 bg-steel-20 relative text-steel-95 block border border-2 border-steel-40 rounded-[10px] w-3/4
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
        Alpine.data('datetime', ({ initial, min, max }) => ({
            local: initial ? toLocalDatetimeString(new Date(initial)) : '',
            utc: initial ? new Date(initial).toISOString() : '',
            min: min ? toLocalDatetimeString(new Date(min)) : null,
            max: max ? toLocalDatetimeString(new Date(max)) : null,

            updateUtc(e) {
                const localDate = new Date(e.target.value);
                this.utc = localDate.toISOString();
                // local updates automatically because of x-model
            }
        }))
    })

    function toLocalDatetimeString(date) {
        if (!date) return '';
        const pad = n => n.toString().padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }
</script>
