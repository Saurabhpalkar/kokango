<template>
<div class="dcroot adm">
<div class="ashell">

  <header class="atop on-dark">
    <RouterLink to="/admin" class="alogo"><img src="/images/logo.png" alt="Kokango logo"><span>Kokango <span class="abadge">ADMIN</span></span></RouterLink>
    <button ref="menuBtn" type="button" class="aburger" :aria-expanded="open ? 'true' : 'false'" aria-controls="admin-nav" aria-label="Menu" @click="open = !open">
      <span class="abars" :class="{ x: open }"><i></i><i></i><i></i></span>
    </button>
  </header>

  <aside id="admin-nav" ref="navEl" class="aside on-dark" :class="{ open }">
    <RouterLink to="/admin" class="alogo alogo-side"><img src="/images/logo.png" alt="Kokango logo"><span>Kokango <span class="abadge">ADMIN</span></span></RouterLink>
    <nav aria-label="Admin">
      <template v-for="(n, i) in nav" :key="n.key || n.group">
      <div v-if="n.group" class="nav-h" role="presentation">{{ n.group }}</div>
      <RouterLink v-else class="nav" :class="{ on: active === n.key }" :to="n.to" :aria-current="active === n.key ? 'page' : undefined">
        <svg class="nico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" v-html="ICONS[n.key]"></svg>
        <span>{{ n.label }}</span>
      </RouterLink>
    </template>
    </nav>
    <div class="afoot">
      <div class="nav who-chip" data-testid="admin-name"><span class="aav">{{ (auth.user?.name || 'A')[0].toUpperCase() }}</span><span class="aname">{{ auth.user?.name || 'Admin' }}</span></div>
      <RouterLink class="nav" to="/"><svg class="nico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5"/></svg><span>View store</span></RouterLink>
      <button class="nav alogout" type="button" :disabled="leaving" @click="logout"><svg class="nico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 4H5a1 1 0 00-1 1v14a1 1 0 001 1h5M15 8l4 4-4 4M19 12H9"/></svg><span>Logout</span></button>
    </div>
  </aside>
  <div v-if="open" class="ascrim" aria-hidden="true" @click="open = false"></div>

  <main class="amain">
    <slot />
  </main>
</div>
</div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { RouterLink, useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAdminGuard } from '@/services/adminGuard'
import '@/panel.css'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const leaving = ref(false)
const open = ref(false)
const menuBtn = ref(null)
const navEl = ref(null)
watch(() => route.fullPath, () => { open.value = false })
function onKey(e) { if (e.key === 'Escape' && open.value) { open.value = false; menuBtn.value?.focus() } }
function onDoc(e) {
  if (!open.value) return
  const t = e.target
  if (navEl.value?.contains(t) || menuBtn.value?.contains(t)) return
  open.value = false
}
onMounted(() => { document.addEventListener('keydown', onKey); document.addEventListener('click', onDoc) })
onBeforeUnmount(() => { document.removeEventListener('keydown', onKey); document.removeEventListener('click', onDoc) })
useAdminGuard()
async function logout() {
  leaving.value = true
  try { await auth.logout() } finally { leaving.value = false }
  router.push('/admin/login')
}

const ICONS = {"dashboard": "<path d=\"M4 13h6V4H4zM14 20h6V11h-6zM4 20h6v-3H4zM14 8h6V4h-6z\"/>", "products": "<path d=\"M4 8l8-4 8 4v8l-8 4-8-4z\"/><path d=\"M4 8l8 4 8-4M12 12v8\"/>", "categories": "<path d=\"M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z\"/>", "inventory": "<path d=\"M3 7l9-4 9 4-9 4zM3 12l9 4 9-4M3 17l9 4 9-4\"/>", "orders": "<path d=\"M6 3h12v18l-3-2-3 2-3-2-3 2z\"/><path d=\"M9 8h6M9 12h6\"/>", "payments": "<rect x=\"3\" y=\"6\" width=\"18\" height=\"12\" rx=\"2\"/><path d=\"M3 10h18M7 15h3\"/>", "shipments": "<path d=\"M3 7h11v9H3zM14 10h4l3 3v3h-7z\"/><circle cx=\"7.5\" cy=\"17.5\" r=\"1.7\"/><circle cx=\"17.5\" cy=\"17.5\" r=\"1.7\"/>", "customers": "<circle cx=\"9\" cy=\"8\" r=\"3.2\"/><path d=\"M3 20c0-3.5 2.7-6 6-6s6 2.5 6 6M16 5.5a3 3 0 010 5.5M18 14.5c1.8.7 3 2.4 3 5.5\"/>", "users": "<circle cx=\"12\" cy=\"8\" r=\"3.5\"/><path d=\"M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7\"/>", "settings": "<circle cx=\"12\" cy=\"12\" r=\"3\"/><path d=\"M19 12a7 7 0 00-.1-1.2l2-1.5-2-3.5-2.4 1a7 7 0 00-2-1.2L14 3h-4l-.5 2.6a7 7 0 00-2 1.2l-2.4-1-2 3.5 2 1.5A7 7 0 005 12c0 .4 0 .8.1 1.2l-2 1.5 2 3.500 2.400-1a7 7 0 002 1.200L10 21h4l.5-2.600a7 7 0 002-1.200l2.400 1 2-3.500-2-1.500c.1-.4.100-.8.100-1.200z\"/>", "marketplace": "<path d=\"M4 9l1.5-5h13L20 9M4 9v10a1 1 0 001 1h14a1 1 0 001-1V9M4 9c0 1.7 1.3 3 3 3s3-1.3 3-3c0 1.7 1.3 3 3 3s3-1.3 3-3c0 1.700 1.300 3 3 3\"/>", "vendors": "<path d=\"M3 10l1.500-5h15L21 10M5 10v9h14v-9M9 19v-5h6v5\"/>", "approvals": "<circle cx=\"12\" cy=\"12\" r=\"9\"/><path d=\"M8 12.500l2.700 2.700L16 9.500\"/>", "vendor-payouts": "<path d=\"M4 7h16v11H4zM4 11h16M15 15h2\"/><path d=\"M8 7V5h8v2\"/>"}

defineProps({ active: { type: String, default: '' } })

const nav = [
  { key: 'dashboard', label: 'Dashboard', to: '/admin' },
  { group: 'Marketplace' },
  { key: 'marketplace', label: 'Marketplace overview', to: '/admin/marketplace' },
  { key: 'vendors', label: 'Vendors', to: '/admin/vendors' },
  { key: 'approvals', label: 'Product approvals', to: '/admin/approvals' },
  { key: 'vendor-payouts', label: 'Commission & payouts', to: '/admin/vendor-payouts' },
  { group: 'Store' },
  { key: 'products', label: 'Products', to: '/admin/products' },
  { key: 'categories', label: 'Categories', to: '/admin/categories' },
  { key: 'inventory', label: 'Inventory', to: '/admin/inventory' },
  { key: 'orders', label: 'Orders', to: '/admin/orders' },
  { key: 'payments', label: 'Payments', to: '/admin/payments' },
  { key: 'shipments', label: 'Shipments', to: '/admin/shipments' },
  { group: 'People' },
  { key: 'customers', label: 'Customers', to: '/admin/customers' },
  { key: 'users', label: 'Staff & roles', to: '/admin/users' },
  { group: 'System' },
  { key: 'settings', label: 'Settings', to: '/admin/settings' }
]
</script>
