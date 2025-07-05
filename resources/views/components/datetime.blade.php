@props(['label' => null, 'description' => null, 'name' => null])

<div class="text-steel-95 text-sm" x-data="datetime" {{ $attributes->wire('model') }}>
    <div class="mb-2">
        @if(isset($label))
            <flux:heading>{{ $label }}</flux:heading>
        @endif
        @if(isset($description))
            <flux:text>{{ $description }}</flux:text>
        @endif
    </div>
    <input
        id="{{ $label }}"
        x-ref="picker"
        type="datetime-local"
        @change="changeDate"
        class="bg-steel-20 p-2 relative text-steel-95 block border-1 border-gray-200 dark:border-gray-500 rounded-[10px]
               focus:bg-steel-20 focus:outline-none"
        {{
            $attributes->filter(
                fn($val, $key) => in_array($key, ['required'])
            )
        }}
        x-bind:min="minFormatted"
        x-bind:max="maxFormatted"
    >
    </input>

    @error($name)
        <div class="text-red-600 text-sm my-3">{{ $message }}</div>
    @enderror

    @if (session()->has('message'))
        <div class="pt-1 text-gray-600 text-sm">
            {{ session('message') }}
        </div>
    @endif

    <input type="hidden" x-ref="anchor" />
</div>

    <script>

        document.addEventListener('alpine:init', () => {
            Alpine.data('datetime', () => ({
                init() {
                    const wireModel = this.$el.getAttribute('wire:model');

                    // Store wireModel for later use
                    this.wireModel = wireModel;

                    if (wireModel) {
                        // Set up the watcher for ongoing changes
                        this.$watch('$wire.' + wireModel, (value) => {
                            if (value) {
                                this.setDate(value);
                            }
                        });

                        // Initialize with existing value if it exists
                        this.$nextTick(() => {
                            if (this.$wire && typeof this.$wire.get === 'function') {
                                const initialValue = this.$wire.get(wireModel);
                                if (initialValue) {
                                    this.setDate(initialValue);
                                }
                            }
                        });
                    }
                },
                wireModel: null,
                date: null,
                min: {!! isset($min) ? "'$min'" : 'null' !!},
                max: {!! isset($max) ? "'$max'" : 'null' !!},
                get minFormatted() {

                    return this.min ? toISOLocal(new Date(this.min)) : null
                },
                get maxFormatted() {

                    return this.max ? toISOLocal(new Date(this.max)) : null
                },
                get dateTimeLocalString() {
                    return this.date ? toISOLocal(this.date) : null
                },
                get utcIsoString() {
                    return this.date ? this.date.toISOString() : null
                },
                setDate(dateString) {
                    if (!dateString) return;
                    this.date = new Date(dateString);
                    this.$refs.picker.value = this.dateTimeLocalString;
                },
                changeDate(e) {
                    e.preventDefault();

                    this.setDate(e.target.value);

                    // Use the stored wireModel from init
                    if (this.wireModel) {
                        this.$wire.set(this.wireModel, this.utcIsoString);
                    }
                }
            }))
        })

        function toISOLocal(d) {
            var z  = n =>  ('0' + n).slice(-2);
            var zz = n => ('00' + n).slice(-3);

            return d.getFullYear() + '-'
                + z(d.getMonth()+1) + '-' +
                z(d.getDate()) + 'T' +
                z(d.getHours()) + ':'  +
                z(d.getMinutes()) + ':' +
                z(d.getSeconds())
        }
    </script>
