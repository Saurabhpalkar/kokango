import { defineStore } from 'pinia'
import { vendorAuth, VENDOR_TOKEN_KEY } from '@/services/vendorService'

// Vendor session (separate from the customer/admin auth store, which is untouched).
export const useVendorStore = defineStore('vendor', {
  state: () => ({ vendor: null, ready: false }),
  getters: { isVendor: (s) => !!s.vendor },
  actions: {
    async init() {
      if (this.ready) return
      let has = false
      try { has = !!localStorage.getItem(VENDOR_TOKEN_KEY) } catch (e) { /* ignore */ }
      if (has) {
        try { this.vendor = await vendorAuth.me() } catch (e) {
          this.vendor = null
          // A network blip, 429 or 5xx is not a sign-out: try again on the next page.
          if (![401, 403].includes(e?.response?.status)) return
        }
      }
      this.ready = true
    },
    async login(login, password) { const r = await vendorAuth.login(login, password); this.vendor = r.vendor },
    async register(form) { const r = await vendorAuth.register(form); this.vendor = r.vendor },
    async logout() { await vendorAuth.logout(); this.vendor = null },
    setVendor(v) { this.vendor = v },
  },
})
