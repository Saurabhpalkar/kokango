import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router/index.js'
import { useCartStore } from './stores/cart.js'
import { useAuthStore } from './stores/auth.js'
import './style.css'

const app = createApp(App).use(createPinia()).use(router)
app.mount('#app')

// Load the server cart once at start, and drop the login if the API says it expired.
useCartStore().load()
window.addEventListener('kokango:unauthorized', () => useAuthStore().clearLocal())
