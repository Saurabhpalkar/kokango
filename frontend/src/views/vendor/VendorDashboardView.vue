<template>
<VendorLayout active="dashboard">
  <div class="hd">
    <div><h1>Dashboard</h1><div class="sub">{{ vendor.vendor?.store_name }} &middot; {{ today }}</div></div>
    <RouterLink to="/vendor/products/new" class="btn">+ Add product</RouterLink>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div v-if="loading && !data" class="state">Loading dashboard&hellip;</div>

  <template v-if="data">
    <div class="statgrid">
      <div v-for="s in stats" :key="s.label" class="card stat">
        <span class="sico" :style="`background:${s.tone};color:${s.fg}`" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" v-html="s.icon"></svg></span>
        <div class="slabel">{{ s.label }}</div>
        <div class="sval">{{ s.value }}</div>
        <div v-if="s.note" class="snote">{{ s.note }}</div>
      </div>
    </div>

    <div class="dgrid">
      <div class="card pad grow">
        <div class="ch"><h2>Sales, last 7 days</h2><span class="muted">{{ inrp(weekTotal) }} total</span></div>
        <div class="bars" role="img" :aria-label="`Sales for the last 7 days, ${inrp(weekTotal)} in total`">
          <div v-for="d in data.sales_by_day" :key="d.label" class="bar">
            <span class="bv">{{ d.paise ? inrp(d.paise).replace('.00','') : '' }}</span>
            <span class="bf" :style="`height:${Math.max(4, d.paise / maxDay * 100)}%`"></span>
            <span class="bl">{{ d.label }}</span>
          </div>
        </div>
      </div>
      <div class="card pad side">
        <div class="ch"><h2>Low stock</h2><RouterLink to="/vendor/inventory" class="more">Manage</RouterLink></div>
        <div v-for="l in data.low_stock" :key="l.variant_id" class="ls">
          <div class="lsi"><img v-if="l.image_url" :src="l.image_url" alt=""></div>
          <div class="lst">{{ l.product_name }}<small>{{ l.size_label }}</small></div>
          <span class="lsn">{{ l.stock }} left</span>
        </div>
        <div v-if="!data.low_stock.length" class="muted pad0">Everything is well stocked.</div>
      </div>
    </div>

    <div class="card pad" style="margin-top:24px">
      <div class="ch"><h2>Recent orders</h2><RouterLink to="/vendor/orders" class="more">View all</RouterLink></div>
      <div style="overflow-x:auto">
        <table class="rt" style="width:100%;border-collapse:collapse;min-width:560px">
          <thead><tr><th>Order</th><th>Customer</th><th>Date</th><th>Your earning</th><th>Status</th></tr></thead>
          <tbody>
            <tr v-for="o in data.recent_orders" :key="o.order_no"><td data-label="Order" style="font-weight:700"><RouterLink to="/vendor/orders">{{ o.order_no }}</RouterLink></td><td data-label="Customer">{{ o.customer_name }}</td><td data-label="Date">{{ formatDate(o.placed_at) }}</td><td data-label="Earning" style="font-weight:700">{{ inrp(o.earning_paise) }}</td><td data-label="Status"><span class="badge" :style="vOrderPill(o.status)">{{ label(o.status) }}</span></td></tr>
            <tr v-if="!data.recent_orders.length"><td colspan="5" class="muted">No orders yet. They will appear here once customers buy your products.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </template>
</VendorLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import VendorLayout from '@/layouts/VendorLayout.vue'
import { useVendorStore } from '@/stores/vendor'
import { apiError } from '@/services/api'
import { vendorDashboard } from '@/services/vendorService'
import { inrp, formatDate } from '@/utils/format'
import { vOrderPill, label } from './vendorUi'

const vendor = useVendorStore()
const data = ref(null); const loading = ref(false); const error = ref('')
const today = new Date().toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
const num = (n) => Number(n || 0).toLocaleString('en-IN')
const I = {
  rupee: '<path d="M7 5h10M7 9h10M7 5c5 0 7 2 7 4s-2 4-7 4l7 6"/>',
  wallet: '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h3"/>',
  orders: '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
  box: '<path d="M4 8l8-4 8 4v8l-8 4-8-4z"/><path d="M4 8l8 4 8-4M12 12v8"/>',
}
const stats = computed(() => {
  const s = data.value.stats
  const green = { tone: 'var(--tint)', fg: 'var(--forest)' }; const sun = { tone: 'var(--sun)', fg: 'var(--amber)' }
  return [
    { label: 'Total sales', value: inrp(s.total_sales_paise), icon: I.rupee, ...green },
    { label: 'Your earnings', value: inrp(s.earnings_paise), note: 'After commission', icon: I.wallet, ...green },
    { label: 'Orders', value: num(s.orders), note: `${num(s.pending_orders)} need action`, icon: I.orders, ...sun },
    { label: 'Products', value: num(s.products), note: `${num(s.live_products)} live · ${num(s.low_stock_count)} low stock`, icon: I.box, ...green },
  ]
})
const weekTotal = computed(() => data.value.sales_by_day.reduce((a, d) => a + d.paise, 0))
const maxDay = computed(() => Math.max(1, ...data.value.sales_by_day.map((d) => d.paise)))

async function load() {
  loading.value = true; error.value = ''
  try { data.value = await vendorDashboard() } catch (e) { error.value = apiError(e, 'Could not load the dashboard.').message } finally { loading.value = false }
}
onMounted(load)
</script>

<style scoped>
.stat{padding:20px;box-sizing:border-box;display:flex;flex-direction:column;gap:4px}
.sico{width:42px;height:42px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px}
.slabel{font-size:13px;font-weight:600;color:var(--muted)}
.sval{font-size:28px;font-weight:800;letter-spacing:-.6px}
.snote{font-size:12px;color:var(--muted)}
.pad{padding:22px 24px;box-sizing:border-box}
.dgrid{display:flex;flex-wrap:wrap;gap:24px;margin-top:24px;align-items:stretch}
.grow{flex:2 1 460px;min-width:0}.side{flex:1 1 300px;min-width:0}
.ch{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.ch h2{margin:0;font-size:18px;font-weight:800}
.more{font-size:13px;font-weight:700;color:var(--forest)}
.muted{color:var(--muted);font-size:13px}.pad0{padding:12px 0}
.bars{display:flex;align-items:flex-end;gap:12px;height:200px;padding-top:20px}
.bar{flex:1;height:100%;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:6px;min-width:0}
.bf{width:100%;max-width:44px;border-radius:8px 8px 3px 3px;background:linear-gradient(180deg,var(--leaf),var(--forest));display:block}
.bl{font-size:12px;font-weight:600;color:var(--muted)}
.bv{font-size:11px;font-weight:700;color:var(--ink);white-space:nowrap}
.ls{display:flex;align-items:center;gap:12px;padding:12px 0;border-top:1px solid var(--line)}
.lsi{flex:0 0 44px;height:44px;border-radius:10px;background:var(--tint);display:flex;align-items:center;justify-content:center;overflow:hidden}
.lsi img{height:100%;width:auto;object-fit:contain}
.lst{flex:1;font-size:14px;font-weight:700;min-width:0}.lst small{display:block;font-size:12px;color:var(--muted);font-weight:500}
.lsn{color:var(--danger);font-weight:800;font-size:13px}
@media (max-width:767px){.dgrid{gap:16px;margin-top:16px}.bars{gap:6px}.bv{display:none}}
</style>
