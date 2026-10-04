<template>
<AdminLayout active="customers">
  <div class="hd">
    <div><h1>Customers</h1><div class="sub">{{ meta ? meta.total : 0 }} customers</div></div>
    <div><label style="position:absolute;left:-9999px" for="cq">Search customers</label><input id="cq" v-model="q" class="s" placeholder="Search name, email or phone"></div>
  </div>

  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>

  <div class="card" style="border-radius:30px;padding:16px 26px">
    <div style="overflow-x:auto">
    <table class="rt" style="width:100%;border-collapse:collapse;min-width:860px">
      <thead><tr><th>CUSTOMER</th><th>PHONE</th><th>CITY</th><th>JOINED</th><th>ORDERS</th><th>TOTAL SPENT</th><th>STATUS</th></tr></thead>
      <tbody>
        <tr v-for="c in rows" :key="c.id">
          <td data-label="Customer"><div style="display:flex;align-items:center;gap:12px"><span style="flex:0 0 38px;height:38px;border-radius:50%;background:var(--tint);color:var(--green);display:inline-flex;align-items:center;justify-content:center;font-weight:800">{{ initial(c.name) }}</span><div style="font-weight:700">{{ c.name }}<div style="font-size:12px;color:var(--muted);font-weight:500">{{ c.email }}</div></div></div></td>
          <td data-label="Phone">{{ c.phone || '—' }}</td><td data-label="City">{{ c.city || '—' }}</td><td data-label="Joined">{{ formatDate(c.created_at) }}</td>
          <td data-label="Orders" style="font-weight:700">{{ c.orders_count }}</td>
          <td data-label="Total Spent" style="font-weight:700">{{ inrp(c.total_spent_paise) }}</td>
          <td data-label="Status">
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><span class="badge" :style="c.is_active ? 'background:#EAF3DD;color:#0B5D1E' : 'background:#EDEDE4;color:#3b4a3f'">{{ c.is_active ? 'Active' : 'Inactive' }}</span><button class="ghost" :disabled="c.busy" :aria-label="(c.is_active ? 'Deactivate ' : 'Activate ') + c.name" @click="toggle(c)">{{ c.is_active ? 'Deactivate' : 'Activate' }}</button></div>
            <div v-if="c.err" class="err" role="alert" style="margin-top:4px">{{ c.err }}</div>
          </td>
        </tr>
        <tr v-if="loading && !rows.length"><td colspan="7" class="state">Loading customers&hellip;</td></tr>
        <tr v-else-if="!rows.length && !error"><td colspan="7" style="color:var(--muted)">No customers match your search.</td></tr>
      </tbody>
    </table>
    </div>
    <AdminPager :meta="meta" @page="go" />
  </div>
</AdminLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AdminPager from './AdminPager.vue'
import { apiError } from '@/services/api'
import { adminCustomers } from '@/services/adminService'
import { debounce } from '@/services/adminGuard'
import { inrp, formatDate } from '@/utils/format'
import { initial } from './adminUi'

const rows = ref([])
const meta = ref(null)
const q = ref('')
const page = ref(1)
const loading = ref(false)
const error = ref('')
let seq = 0

async function load() {
  const my = ++seq
  loading.value = true; error.value = ''
  try {
    const res = await adminCustomers.list({ search: q.value.trim(), page: page.value, per_page: 10 })
    if (my !== seq) return
    rows.value = res.data.map((c) => ({ ...c, busy: false, err: '' })); meta.value = res.meta
  } catch (e) { if (my === seq) error.value = apiError(e, 'Could not load customers.').message } finally { if (my === seq) loading.value = false }
}
watch(q, debounce(() => { page.value = 1; load() }))
function go(p) { page.value = p; load() }

async function toggle(c) {
  c.busy = true; c.err = ''
  try { Object.assign(c, await adminCustomers.update(c.id, { is_active: !c.is_active })) }
  catch (e) { c.err = apiError(e, 'Could not update the customer.').message } finally { c.busy = false }
}
onMounted(load)
</script>
