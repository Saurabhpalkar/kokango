import { defineStore } from 'pinia'

// Small "Added to cart" style message shown at the bottom of the screen.
export const useToastStore = defineStore('toast', {
  state: () => ({ message: '', visible: false, timer: null }),
  actions: {
    show(message) {
      this.message = message
      this.visible = true
      clearTimeout(this.timer)
      this.timer = setTimeout(() => { this.visible = false }, 2600)
    },
  },
})
