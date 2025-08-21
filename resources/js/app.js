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

window.Echo.connector.socket.on('open', () => {
  window.Livewire?.all()?.forEach(c => c.$refresh());
});
window.Echo.connector.socket.on('reconnect', () => {
  window.Livewire?.all()?.forEach(c => c.$refresh());
});
document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible') {
    window.Livewire?.all()?.forEach(c => c.$refresh());
  }
});