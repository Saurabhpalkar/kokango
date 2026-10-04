<template>
<AdminLayout active="dashboard">
  <div class="hd">
    <div><h1>Dashboard</h1><div class="sub">{{ today }}</div></div>
    <div class="who" style="display:flex;align-items:center;gap:12px"><span style="width:42px;height:42px;border-radius:50%;background:var(--tint);color:var(--green);display:inline-flex;align-items:center;justify-content:center;font-weight:800">{{ initial(auth.user?.name || 'Admin') }}</span><span style="font-size:14px;font-weight:600">{{ auth.user?.name || 'Admin' }}</span></div>
  </div>

  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>

  <div v-if="loading && !data" class="state">Loading dashboard&hellip;</div>

  <template v-if="data">
    <div class="statgrid">
      <div v-for="s in stats" :key="s.label" class="card stat" :style="`border-radius:26px;padding:22px;box-sizing:border-box;border-top:4px solid ${s.accent}`">
        <div style="font-size:13px;font-weight:600;color:var(--muted)">{{ s.label }}</div>
        <div style="font-size:32px;font-weight:800;margin-top:8px;color:var(--ink)">{{ s.value }}</div>
      </div>
    </div>

    <div class="dgrid">
      <div class="card" style="flex:2 1 520px;border-radius:30px;padding:26px;box-sizing:border-box;min-width:0;max-width:100%">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px"><h2 style="margin:0;font-size:19px;font-weight:800">Recent Orders</h2><RouterLink to="/admin/orders" style="font-size:13px;font-weight:700;color:var(--green)">View all</RouterLink></div>
        <div style="overflow-x:auto">
        <table class="rt" style="width:100%;border-collapse:collapse;min-width:520px">
          <thead><tr><th>ORDER</th><th>CUSTOMER</th><th>TOTAL</th><th>STATUS</th></tr></thead>
          <tbody>
            <tr v-for="o in data.recent_orders" :key="o.order_no"><td data-label="Order" style="font-weight:700"><RouterLink to="/admin/orders">{{ o.order_no }}</RouterLink></td><td data-label="Customer">{{ o.customer_name }}</td><td data-label="Total">{{ inrp(o.total_paise) }}</td><td data-label="Status"><span class="badge" :style="orderPill(o.status)">{{ label(o.status) }}</span></td></tr>
            <tr v-if="!data.recent_orders.length"><td colspan="4" style="color:var(--muted)">No orders yet.</td></tr>
          </tbody>
        </table>
        </div>
      </div>

      <div class="card" style="flex:1 1 290px;border-radius:30px;padding:26px;box-sizing:border-box">
        <h2 style="margin:0 0 12px;font-size:19px;font-weight:800">Low Stock</h2>
        <div v-for="l in data.low_stock" :key="l.variant_id" style="display:flex;align-items:center;gap:12px;padding:12px 0;border-top:1px solid var(--line)">
          <div style="flex:0 0 48px;height:48px;border-radius:12px;background:var(--tint);display:flex;align-items:center;justify-content:center;overflow:hidden"><img v-if="l.image_url" :src="l.image_url" alt="" style="height:100%;width:auto;object-fit:contain"></div>
          <div style="flex:1;font-size:14px;font-weight:700">{{ l.product_name }}<div style="font-size:12px;color:var(--muted);font-weight:500">{{ l.size_label }}</div></div>
          <span style="color:#B3261E;font-weight:800;font-size:13px">{{ l.stock }} left</span>
        </div>
        <div v-if="!data.low_stock.length" style="padding:12px 0;border-top:1px solid var(--line);font-size:14px;color:var(--muted)">Everything is well stocked.</div>
        <RouterLink to="/admin/inventory" class="btn" style="display:flex;margin-top:16px">Restock</RouterLink>
      </div>
    </div>
  </template>
</AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { apiError } from '@/services/api'
import { adminDashboard } from '@/services/adminService'
import { inrp } from '@/utils/format'
import { orderPill, label, initial } from './adminUi'

const auth = useAuthStore()
const data = ref(null)
const loading = ref(false)
const error = ref('')
const today = new Date().toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

const num = (n) => Number(n || 0).toLocaleString('en-IN')
const stats = computed(() => {
  const s = data.value.stats
  return [
    { label: 'Total Orders', value: num(s.total_orders), accent: '#0B5D1E' },
    { label: 'Pending Orders', value: num(s.pending_orders), accent: '#C9C5B0' },
    { label: 'Processing Orders', value: num(s.processing_orders), accent: '#FFB400' },
    { label: 'Delivered Orders', value: num(s.delivered_orders), accent: '#9ACD32' },
    { label: 'Total Sales', value: inrp(s.total_sales_paise), accent: '#0B5D1E' },
    { label: 'Low Stock Products', value: num(s.low_stock_count), accent: '#D9534F' },
    { label: 'Pending Shipments', value: num(s.pending_shipments), accent: '#FFB400' },
    { label: 'Customers', value: num(s.customers), accent: '#9ACD32' },
  ]
})

async function load() {
  loading.value = true; error.value = ''
  try { data.value = await adminDashboard() } catch (e) { error.value = apiError(e, 'Could not load the dashboard.').message } finally { loading.value = false }
}
onMounted(load)
</script>

<style scoped>
.dgrid{display:flex;flex-wrap:wrap;gap:24px;margin-top:24px;align-items:flex-start}
@media (max-width:1099px){.dgrid>*{flex:1 1 100%!important;min-width:0}}
@media (max-width:999px){.who{display:none!important}}
@media (max-width:767px){.dgrid{gap:16px;margin-top:16px}}
</style>
