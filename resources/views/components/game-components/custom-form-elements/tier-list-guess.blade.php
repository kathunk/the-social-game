@props(['element'])

<div
  class="rounded-lg p-3 md:p-4 bg-gradient-to-b from-green-200 via-yellow-100 to-red-100"
  wire:ignore
  x-data="{
    path: 'round_properties.{{ App\Challenges\TierList\TierListGuess::key() }}.{{ $element['property_name'] }}',
    order: [],
    letterAt(n) {
      const code = 65 + n + (n >= 4 ? 1 : 0) // skip 'E'
      return String.fromCharCode(code)
    },
    getOrder() {
      return Array.from(this.$refs.list.querySelectorAll('[data-value]'))
        .map((el, i) => ({
          actual_tier: el.dataset.key,
          guessed_tier: this.letterAt(i),
          value: el.dataset.value,
        }))
    },
    letterFor(key) {
      // If order is empty, calculate it on-the-fly based on current DOM order
      if (this.order.length === 0) {
        const items = Array.from(this.$refs.list.querySelectorAll('[data-value]'))
        const idx = items.findIndex(el => el.dataset.key === key)
        const n = idx >= 0 ? idx : 0
        return this.letterAt(n)
      }
      
      const idx = this.order.findIndex(i => i.actual_tier == key)
      const n = idx >= 0 ? idx : 0
      return this.letterAt(n)
    },
    sync() {
      this.order = this.getOrder()
      $wire.set(this.path, this.order)
    },
  }"
  x-init="sync()"
>
  <ul x-ref="list" x-sort="sync()" class="ranked-list flex flex-col gap-2 w-full">
    @foreach($element['answer_keys'] as $key)
      <li
        x-sort:item
        data-key="{{ $key['tier'] }}"
        data-value="{{ $key['value'] }}"
        class="w-full p-3 text-sm flex flex-row justify-between items-center cursor-grab active:cursor-grabbing"
      >
        <div class="flex flex-row gap-2 items-center">
            <flux:badge size="sm" x-text="letterFor($el.closest('li').dataset.key)"></flux:badge>
            <p class="text-sm">{{ $key['value'] }}</p>
        </div>
        <flux:badge size="sm">
            @if($element['round_type'] === 'opponent')
              {{ Str::replace('_', ' ', Str::title($key['category'])) }}
            @else
              {{ App\Models\Player::find($key['player_id'])->name }}
            @endif
        </flux:badge>
      </li>
    @endforeach
  </ul>
</div>