import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router/index.js'
import { useCartStore } from './stores/cart.js'
import { useAuthStore } from './stores/auth.js'
import { vReveal, vCount } from './composables/useReveal.js'
import { vSeasonScene } from './composables/useSeasonalFx.js'
import './style.css'

const app = createApp(App).use(createPinia()).use(router)
app.directive('reveal', vReveal)   // scroll-in animations (see composables/useReveal.js)
app.directive('count', vCount)     // figures count up when they come into view
app.directive('season-scene', vSeasonScene)   // occasional seasonal effect in landscape areas (content/season.js)
app.mount('#app')

// Load the server cart once at start, and drop the login if the API says it expired.
useCartStore().load()
window.addEventListener('kokango:unauthorized', () => useAuthStore().clearLocal())
