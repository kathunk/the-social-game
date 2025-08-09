@props(['element'])

<div
  class="rounded-lg p-3 md:p-4 bg-gradient-to-b from-green-200 via-yellow-100 to-red-100"
  wire:ignore
  x-data="{
    path: 'round_properties.{{ App\Challenges\Classes\TierListGuess::key() }}.{{ $element['property_name'] }}',
    getOrder() {
      return Array.from(this.$refs.list.querySelectorAll('[data-value]'))
        .map((el) => ({ key: el.dataset.key, value: el.dataset.value }))
    },
    sync() { $wire.set(this.path, this.getOrder()) },
  }"
  x-init="sync()"
>
  <ul x-ref="list" x-sort="sync()" class="ranked-list flex flex-col gap-2 w-full">
    @foreach($element['answer_keys'] as $key)
      <li
        x-sort:item
        data-key="{{ $key['tier'] }}"
        data-value="{{ $key['value'] }}"
        class="w-full p-3 text-sm flex flex-col gap-1"
      >
        <p class="text-sm">{{ $key['value'] }}</p>
        <p class="text-xs text-slate-500">submitted by</p>
      </li>
    @endforeach
  </ul>
</div>