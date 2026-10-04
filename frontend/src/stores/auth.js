import { defineStore } from 'pinia'
import api, { TOKEN_KEY } from '@/services/api'
import { useCartStore } from '@/stores/cart'

function setToken(t) { try { t ? localStorage.setItem(TOKEN_KEY, t) : localStorage.removeItem(TOKEN_KEY) } catch (e) { /* ignore */ } }

// Real authentication against the Laravel API (Sanctum token).
export const useAuthStore = defineStore('auth', {
  state: () => ({ user: null, token: (() => { try { return localStorage.getItem(TOKEN_KEY) } catch (e) { return null } })(), ready: false }),
  getters: {
    isLoggedIn: (s) => !!s.user,
    isAdmin: (s) => !!s.user?.is_admin,
  },
  actions: {
    /** Call once before routing: loads the user when a token is stored. */
    async init() {
      if (this.ready) return
      if (this.token) {
        try { this.user = (await api.get('/auth/me')).data.data } catch (e) { this.token = null; this.user = null; setToken(null) }
      }
      this.ready = true
    },
    async _accept(payload) {
      this.token = payload.token; this.user = payload.user; setToken(payload.token)
      // bring a guest cart into the account
      await useCartStore().mergeAfterLogin()
    },
    async login(login, password) { await this._accept((await api.post('/auth/login', { login, password })).data.data) },
    async register(form) { await this._accept((await api.post('/auth/register', form)).data.data) },
    async logout() {
      try { await api.post('/auth/logout') } catch (e) { /* token may already be invalid */ }
      this.user = null; this.token = null; setToken(null)
      useCartStore().reset()
    },
    clearLocal() { this.user = null; this.token = null; setToken(null) },
    async updateProfile(form) { this.user = (await api.put('/auth/profile', form)).data.data },
    async changePassword(form) { await api.put('/auth/password', form) },
    async forgotPassword(email) { await api.post('/auth/forgot-password', { email }) },
  },
})
