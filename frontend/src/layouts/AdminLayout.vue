<template>
<div class="dcroot">
<div class="ashell">

  <header class="atop">
    <RouterLink to="/admin" class="alogo"><img src="/images/logo.png" alt="Kokango logo"><span>Kokango <span class="abadge">ADMIN</span></span></RouterLink>
    <button ref="menuBtn" type="button" class="aburger" :aria-expanded="open ? 'true' : 'false'" aria-controls="admin-nav" aria-label="Menu" @click="open = !open">
      <span class="abars" :class="{ x: open }"><i></i><i></i><i></i></span>
    </button>
  </header>

  <aside id="admin-nav" ref="navEl" class="aside" :class="{ open }">
    <RouterLink to="/admin" class="alogo alogo-side"><img src="/images/logo.png" alt="Kokango logo"><span>Kokango <span class="abadge">ADMIN</span></span></RouterLink>
    <nav aria-label="Admin">
      <RouterLink v-for="n in nav" :key="n.key" class="nav" :class="{ on: active === n.key }" :to="n.to" :aria-current="active === n.key ? 'page' : undefined">{{ n.label }}</RouterLink>
    </nav>
    <div class="afoot">
      <div class="nav" style="cursor:default" data-testid="admin-name"><span class="aav">{{ (auth.user?.name || 'A')[0].toUpperCase() }}</span><span class="aname">{{ auth.user?.name || 'Admin' }}</span></div>
      <RouterLink class="nav" to="/">View store</RouterLink>
      <button class="nav alogout" type="button" :disabled="leaving" @click="logout">Logout</button>
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

defineProps({ active: { type: String, default: '' } })

const nav = [
  { key: 'dashboard', label: 'Dashboard', to: '/admin' },
  { key: 'products', label: 'Products', to: '/admin/products' },
  { key: 'categories', label: 'Categories', to: '/admin/categories' },
  { key: 'inventory', label: 'Inventory', to: '/admin/inventory' },
  { key: 'orders', label: 'Orders', to: '/admin/orders' },
  { key: 'payments', label: 'Payments', to: '/admin/payments' },
  { key: 'shipments', label: 'Shipments', to: '/admin/shipments' },
  { key: 'customers', label: 'Customers', to: '/admin/customers' },
  { key: 'users', label: 'Users', to: '/admin/users' },
  { key: 'settings', label: 'Settings', to: '/admin/settings' }
]
</script>

<style>
.dcroot{--bg:#F4F5EC;--surface:#fff;--ink:#14301C;--muted:#566659;--line:#E6E2D0;--green:#0B5D1E;--mango:#FFB400;--tint:#EAF3DD;--sun:#FFF1CC}
.dcroot{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans','Noto Sans Devanagari',system-ui,sans-serif}
.dcroot a{color:var(--ink);text-decoration:none}.dcroot a:hover{color:var(--green)}
.dcroot button{font-family:inherit;cursor:pointer}
.dcroot .card{background:var(--surface);border:1px solid var(--line);box-shadow:0 8px 24px rgba(20,48,28,0.05)}
.dcroot .nav{display:flex;align-items:center;gap:12px;padding:12px 18px;border-radius:999px;font-size:14px;font-weight:600;color:var(--muted);min-height:44px;box-sizing:border-box}
.dcroot .nav.on{background:var(--tint);color:var(--green)}
.dcroot .badge{display:inline-block;padding:5px 14px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap}
.dcroot th{text-align:left;font-size:12px;font-weight:700;color:var(--muted);padding:12px 8px;letter-spacing:0.5px}
.dcroot td{padding:12px 8px;font-size:14px;border-top:1px solid var(--line);vertical-align:middle}
.dcroot .btn{box-sizing:border-box;background:var(--green);color:#fff;border:none;border-radius:999px;padding:10px 22px;font-size:14px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.dcroot .btn:hover{background:#08481a;color:#fff}
.dcroot .ghost{box-sizing:border-box;background:#fff;color:var(--ink);border:1.5px solid var(--line);border-radius:999px;padding:6px 16px;font-size:13px;font-weight:600;min-height:38px;display:inline-flex;align-items:center;justify-content:center}
.dcroot .ghost:disabled,.dcroot .btn:disabled{opacity:.5;cursor:default}
.dcroot .ghost:hover{border-color:var(--green);color:var(--green)}
.dcroot .ghost.danger{border-color:#E8B7B3;color:#B3261E}
.dcroot .ghost.danger:hover{background:#B3261E;color:#fff;border-color:#B3261E}
.dcroot .chip{background:#fff;color:var(--ink);border:1.5px solid var(--line);border-radius:999px;padding:8px 18px;font-size:13px;font-weight:600;min-height:40px}
.dcroot .chip.on{border-color:var(--green);color:var(--green);background:var(--tint)}
.dcroot select,.dcroot input.in,.dcroot textarea.in{height:46px;box-sizing:border-box;background:var(--bg);border:1px solid var(--line);border-radius:14px;color:var(--ink);padding:0 14px;font-family:inherit;font-size:14px;font-weight:600;width:100%}
.dcroot textarea.in{height:auto;padding:12px 14px;font-weight:500;resize:vertical}
.dcroot input.s{height:46px;box-sizing:border-box;background:#fff;border:1px solid var(--line);border-radius:999px;padding:0 20px;color:var(--ink);font-family:inherit;font-size:14px;min-width:240px}
.dcroot input.num{width:84px;height:38px;text-align:center;padding:0 8px;border-radius:999px;background:#fff}
.dcroot .lbl{font-size:12px;font-weight:700;color:var(--muted);letter-spacing:1px;display:block;margin-bottom:6px}
.dcroot .ok{color:var(--green);font-weight:700;font-size:13px}
.dcroot .banner{background:var(--tint);color:var(--green);border-radius:16px;padding:12px 18px;font-size:14px;font-weight:700;margin-bottom:20px}
.dcroot .hd{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:16px;margin-bottom:28px}
.dcroot .hd h1{margin:0;font-size:32px;font-weight:800;letter-spacing:-0.5px}
.dcroot .hd .sub{font-size:13px;color:var(--muted);margin-top:4px}
.dcroot .err{color:#B3261E;font-size:13px;font-weight:600}
.dcroot .state{color:var(--muted);font-size:14px;padding:22px 8px;text-align:center}
.dcroot .errbox{background:#FBE3E1;color:#B3261E;border-radius:16px;padding:12px 18px;font-size:14px;font-weight:600;margin-bottom:20px;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap}
.dcroot .errbox button{background:#fff;border:1.5px solid #E8B7B3;color:#B3261E;border-radius:999px;padding:4px 14px;font-size:12px;font-weight:700}
.dcroot select:disabled{opacity:.6}
.dcroot input::placeholder{color:#8a948c}

/* ---- shell ---- */

.dcroot .ashell{width:100%;min-height:100vh;display:flex;flex-wrap:wrap;background:var(--bg)}
.dcroot .atop{display:none}
.dcroot .aside{flex:0 0 260px;background:#fff;border-right:1px solid var(--line);padding:24px 16px;box-sizing:border-box}
.dcroot .alogo{display:flex;align-items:center;gap:12px;font-weight:800;font-size:21px}
.dcroot .alogo img{height:40px;width:auto}
.dcroot .alogo-side{padding:0 10px 28px}
.dcroot .abadge{font-size:10px;background:var(--mango);color:var(--ink);padding:3px 8px;border-radius:999px;font-weight:700;vertical-align:middle}
.dcroot .afoot{margin:18px 0 6px;border-top:1px solid var(--line);padding-top:14px}
.dcroot .aav{flex:0 0 28px;height:28px;border-radius:50%;background:var(--tint);color:var(--green);display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:13px}
.dcroot .aname{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--ink)}
.dcroot .alogout{border:none;background:none;width:100%;text-align:left}
.dcroot .amain{flex:1 1 700px;padding:32px 40px;box-sizing:border-box;min-width:0}
.dcroot .ascrim{display:none}
.dcroot .aburger{display:none}

/* ---- tablet / phone: top bar + drawer ---- */
@media (max-width:999px){
  .dcroot .ashell{display:block}
  .dcroot .atop{display:flex;align-items:center;justify-content:space-between;gap:12px;position:sticky;top:0;z-index:50;background:#fff;border-bottom:1px solid var(--line);padding:8px 16px;min-height:60px;box-sizing:border-box}
  .dcroot .atop .alogo{font-size:18px;gap:10px;min-height:44px}
  .dcroot .atop .alogo img{height:34px}
  .dcroot .aburger{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;border:1.5px solid var(--line);background:#fff;padding:0}
  .dcroot .abars{position:relative;display:block;width:20px;height:14px}
  .dcroot .abars i{position:absolute;left:0;right:0;height:2px;border-radius:2px;background:var(--ink);transition:transform .2s,opacity .2s}
  .dcroot .abars i:nth-child(1){top:0}.dcroot .abars i:nth-child(2){top:6px}.dcroot .abars i:nth-child(3){top:12px}
  .dcroot .abars.x i:nth-child(1){top:6px;transform:rotate(45deg)}.dcroot .abars.x i:nth-child(2){opacity:0}.dcroot .abars.x i:nth-child(3){top:6px;transform:rotate(-45deg)}
  .dcroot .aside{position:fixed;left:0;right:0;top:60px;bottom:auto;max-height:calc(100vh - 60px);max-height:calc(100dvh - 60px);overflow-y:auto;z-index:49;border-right:none;border-bottom:1px solid var(--line);box-shadow:0 18px 30px rgba(20,48,28,.14);padding:10px 12px 14px;transform:translateY(-8px);opacity:0;visibility:hidden;transition:transform .18s,opacity .18s,visibility 0s .18s}
  .dcroot .aside.open{transform:none;opacity:1;visibility:visible;transition:transform .18s,opacity .18s}
  .dcroot .alogo-side{display:none}
  .dcroot .ascrim{display:block;position:fixed;inset:60px 0 0 0;z-index:48;background:rgba(20,48,28,.35)}
  .dcroot .amain{padding:20px 16px 32px}
  .dcroot .hd{margin-bottom:20px;gap:12px}
  .dcroot .hd>*{min-width:0}
  .dcroot .hd h1{font-size:clamp(24px,5.5vw,32px)}
  .dcroot .hd input.s,.dcroot .hd .btn{max-width:100%}
}
@media (max-width:767px){
  .dcroot .amain .card{padding-left:16px!important;padding-right:16px!important;border-radius:22px!important}
  .dcroot .hd>div:last-child:not(:first-child){width:100%}
  .dcroot .hd input.s{min-width:0;width:100%}
  .dcroot .ghost{min-height:44px}
  .dcroot .chip{min-height:44px}
  .dcroot td,.dcroot th{padding-left:8px;padding-right:8px}
}

/* ---- shared responsive helpers ---- */
.dcroot .statgrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.dcroot .chips{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
@media (max-width:1099px){.dcroot .statgrid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:767px){
  .dcroot .statgrid{gap:12px}
  .dcroot .stat{padding:16px!important}
  .dcroot .stat>div:last-child{font-size:clamp(20px,6.5vw,28px)!important;overflow-wrap:anywhere}
  .dcroot .chips{flex-wrap:nowrap;overflow-x:auto;margin-left:-16px;margin-right:-16px;padding:0 16px 4px;-webkit-overflow-scrolling:touch}
  .dcroot .chips .chip{flex:0 0 auto;white-space:nowrap}
  .dcroot .s,.dcroot input.s{min-width:0!important}
  .dcroot .btn,.dcroot .ghost{max-width:100%}
  .dcroot .pager{justify-content:center}
}
@media (max-width:379px){.dcroot .statgrid{grid-template-columns:1fr}}

/* ---- tables become stacked card rows on phones / small tablets ---- */
@media (max-width:899px){
  .dcroot table.rt{min-width:0!important;width:100%}
  .dcroot .rt thead{display:none}
  .dcroot .rt,.dcroot .rt tbody{display:block}
  .dcroot .rt tr{display:block;border:1px solid var(--line);border-radius:16px;padding:8px 14px;margin-bottom:12px;background:#fff;overflow:hidden}
  .dcroot .rt tr.sel{background:var(--sun)}
  .dcroot .rt tr.sel td{background:transparent!important}
  .dcroot .rt td{display:flex;justify-content:space-between;align-items:center;gap:12px;border-top:1px solid var(--line);padding:8px 0!important;text-align:right;min-width:0;overflow-wrap:anywhere}
  .dcroot .rt td:first-child{border-top:none;text-align:left;justify-content:flex-start}
  .dcroot .rt td:first-child::before,.dcroot .rt td[data-label=""]::before{display:none}
  .dcroot .rt td[data-label=""]{justify-content:flex-start;flex-wrap:wrap}
  .dcroot .rt td::before{content:attr(data-label);flex:0 0 auto;font-size:11px;font-weight:700;color:var(--muted);letter-spacing:.6px;text-align:left}
  .dcroot .rt td[colspan]{display:block;text-align:center;border-top:none}
  .dcroot .rt tr:has(td[colspan]){border:none;padding:0}
  .dcroot .rt td>div,.dcroot .rt td>form{justify-content:flex-end;text-align:left}
  .dcroot .rt td:first-child>div,.dcroot .rt td:first-child>form{justify-content:flex-start}
  .dcroot .rt td select{max-width:100%}
  .dcroot .rt td[data-label="Set Stock"],.dcroot .rt td[data-label="Update Status"]{flex-wrap:wrap}
  .dcroot .rt td input.s:not(.num){width:100%!important;max-width:200px}
}
</style>
