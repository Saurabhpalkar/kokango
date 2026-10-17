import { defineStore } from 'pinia'

// Which product the quick-view window shows (one window for the whole app, mounted in App.vue).
export const useQuickViewStore = defineStore('quickview', {
  state: () => ({ product: null }),
  actions: {
    open(product) { this.product = product },
    close() { this.product = null },
  },
})
