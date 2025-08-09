import './echo'
import sort from '@alpinejs/sort'

// If Alpine is already on window, register immediately.
// Otherwise, wait for Livewire/Flux to boot Alpine.
if (window.Alpine) {
  window.Alpine.plugin(sort)
} else {
  document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(sort)
  })
}