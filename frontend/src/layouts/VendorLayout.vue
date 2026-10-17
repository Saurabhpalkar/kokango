<template>
<div class="dcroot adm vnd">
<div class="ashell">

  <header class="atop on-dark">
    <RouterLink to="/vendor" class="alogo"><img src="/images/logo.png" alt="Kokango logo"><span>Kokango <span class="abadge">VENDOR</span></span></RouterLink>
    <button ref="menuBtn" type="button" class="aburger" :aria-expanded="open ? 'true' : 'false'" aria-controls="vendor-nav" aria-label="Menu" @click="open = !open">
      <span class="abars" :class="{ x: open }"><i></i><i></i><i></i></span>
    </button>
  </header>

  <aside id="vendor-nav" ref="navEl" class="aside on-dark" :class="{ open }">
    <RouterLink to="/vendor" class="alogo alogo-side"><img src="/images/logo.png" alt="Kokango logo"><span>Kokango <span class="abadge">VENDOR</span></span></RouterLink>
    <nav aria-label="Vendor">
      <RouterLink v-for="n in nav" :key="n.key" class="nav" :class="{ on: active === n.key }" :to="n.to" :aria-current="active === n.key ? 'page' : undefined">
        <svg class="nico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" v-html="ICONS[n.key]"></svg>
        <span>{{ n.label }}</span>
      </RouterLink>
    </nav>
    <div class="afoot">
      <div class="nav who-chip" data-testid="vendor-name"><span class="aav">{{ (vendor.vendor?.store_name || 'V')[0].toUpperCase() }}</span><span class="aname">{{ vendor.vendor?.store_name || 'My store' }}</span></div>
      <RouterLink class="nav" to="/"><svg class="nico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5"/></svg><span>View store</span></RouterLink>
      <button class="nav alogout" type="button" :disabled="leaving" @click="logout"><svg class="nico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 4H5a1 1 0 00-1 1v14a1 1 0 001 1h5M15 8l4 4-4 4M19 12H9"/></svg><span>Logout</span></button>
    </div>
  </aside>
  <div v-if="open" class="ascrim" aria-hidden="true" @click="open = false"></div>

  <main class="amain">
    <div v-if="vendor.vendor && vendor.vendor.status !== 'approved'" class="vbanner" role="status"><b>{{ vendor.vendor.status === 'pending' ? 'Your store is awaiting approval.' : 'Your store is suspended.' }}</b> {{ vendor.vendor.status === 'pending' ? 'Complete Store settings (pickup address, FSSAI licence and bank account) so the Kokango team can approve you. You can prepare products meanwhile; they go live after approval.' : (vendor.vendor.status_reason || 'Contact support to restore selling.') }} <RouterLink v-if="vendor.vendor.status === 'pending'" to="/vendor/store">Open store settings</RouterLink></div>
    <slot />
  </main>
</div>
</div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { RouterLink, useRouter, useRoute } from 'vue-router'
import { useVendorStore } from '@/stores/vendor'
import '@/panel.css'

const vendor = useVendorStore()
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
async function logout() {
  leaving.value = true
  try { await vendor.logout() } finally { leaving.value = false }
  router.push('/vendor/login')
}

defineProps({ active: { type: String, default: '' } })

const ICONS = {
  dashboard: '<path d="M4 13h6V4H4zM14 20h6V11h-6zM4 20h6v-3H4zM14 8h6V4h-6z"/>',
  products: '<path d="M4 8l8-4 8 4v8l-8 4-8-4z"/><path d="M4 8l8 4 8-4M12 12v8"/>',
  inventory: '<path d="M3 7l9-4 9 4-9 4zM3 12l9 4 9-4M3 17l9 4 9-4"/>',
  orders: '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
  payouts: '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h3"/>',
  store: '<path d="M4 9l1.5-5h13L20 9M4 9v11h16V9M4 9c0 1.7 1.3 3 3 3s3-1.3 3-3c0 1.7 1.3 3 3 3s3-1.3 3-3c0 1.7 1.3 3 3 3"/>',
}

const nav = [
  { key: 'dashboard', label: 'Dashboard', to: '/vendor' },
  { key: 'products', label: 'My Products', to: '/vendor/products' },
  { key: 'inventory', label: 'Inventory', to: '/vendor/inventory' },
  { key: 'orders', label: 'Orders', to: '/vendor/orders' },
  { key: 'payouts', label: 'Earnings & Payouts', to: '/vendor/payouts' },
  { key: 'store', label: 'Store Settings', to: '/vendor/store' },
]
</script>

<style>
.adm.vnd .vbanner{background:var(--sun);color:#5A3F00;border-radius:var(--r-md);padding:12px 18px;font-size:14px;line-height:1.6;margin-bottom:20px;border:1px solid #F2DC8A}
</style>
