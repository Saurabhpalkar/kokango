import { defineStore } from 'pinia'
import api, { CART_TOKEN_KEY, apiError } from '@/services/api'
import { useToastStore } from '@/stores/toast'

const EMPTY = { token: null, count: 0, items: [], subtotal_paise: 0, shipping_paise: 0, tax_paise: 0, total_paise: 0 }

// The cart lives on the server (guest token header or logged-in user). This store mirrors it.
export const useCartStore = defineStore('cart', {
  state: () => ({ cart: { ...EMPTY }, loaded: false, busy: false }),
  getters: {
    items: (s) => s.cart.items,
    count: (s) => s.cart.count,
    subtotalPaise: (s) => s.cart.subtotal_paise,
    shippingPaise: (s) => s.cart.shipping_paise,
    taxPaise: (s) => s.cart.tax_paise,
    totalPaise: (s) => s.cart.total_paise,
  },
  actions: {
    _apply(cart) {
      this.cart = cart
      this.loaded = true
      if (cart.token) { try { localStorage.setItem(CART_TOKEN_KEY, cart.token) } catch (e) { /* ignore */ } }
    },
    async load() {
      try { this._apply((await api.get('/cart')).data.data) } catch (e) { /* offline: keep empty */ }
    },
    async _run(request) {
      this.busy = true
      try { this._apply((await request()).data.data); return true }
      catch (e) { useToastStore().show(apiError(e).message); return false }
      finally { this.busy = false }
    },
    add(variantId, qty = 1) { return this._run(() => api.post('/cart/items', { variant_id: variantId, qty })) },
    setQty(itemId, qty) { return this._run(() => api.patch(`/cart/items/${itemId}`, { qty })) },
    remove(itemId) { return this._run(() => api.delete(`/cart/items/${itemId}`)) },
    clear() { return this._run(() => api.delete('/cart')) },
    async mergeAfterLogin() {
      let guest = null
      try { guest = localStorage.getItem(CART_TOKEN_KEY) } catch (e) { /* ignore */ }
      try {
        if (guest) await api.post('/cart/merge', { guest_token: guest })
      } catch (e) { /* ignore */ }
      try { localStorage.removeItem(CART_TOKEN_KEY) } catch (e) { /* ignore */ }
      await this.load()
    },
    reset() {
      try { localStorage.removeItem(CART_TOKEN_KEY) } catch (e) { /* ignore */ }
      this.cart = { ...EMPTY }; this.loaded = false
      this.load()
    },
  },
})
