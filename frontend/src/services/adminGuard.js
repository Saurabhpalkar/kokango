import { onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

// 401 -> api.js already forgot the token and fires 'kokango:unauthorized'.
// 403 on an /admin call means the account is not an admin: we fire 'kokango:forbidden'.
let installed = false
function installForbiddenHook() {
  if (installed) return
  installed = true
  api.interceptors.response.use((r) => r, (error) => {
    if (error.response?.status === 403 && String(error.config?.url || '').startsWith('/admin')) {
      window.dispatchEvent(new CustomEvent('kokango:forbidden'))
    }
    return Promise.reject(error)
  })
}

/** Used once by AdminLayout: sends the admin back to /admin/login when the session is gone. */
export function useAdminGuard() {
  const router = useRouter()
  const route = useRoute()
  const auth = useAuthStore()
  installForbiddenHook()
  function kick() {
    auth.clearLocal()
    if (route.path !== '/admin/login') router.replace({ path: '/admin/login', query: { redirect: route.fullPath } })
  }
  onMounted(() => {
    window.addEventListener('kokango:unauthorized', kick)
    window.addEventListener('kokango:forbidden', kick)
  })
  onBeforeUnmount(() => {
    window.removeEventListener('kokango:unauthorized', kick)
    window.removeEventListener('kokango:forbidden', kick)
  })
}

/** Debounce helper for search boxes. */
export function debounce(fn, ms = 350) {
  let t
  const d = (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms) }
  d.cancel = () => clearTimeout(t)
  return d
}
