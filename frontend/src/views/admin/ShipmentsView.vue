<template>
<AdminLayout active="shipments">
  <div class="hd"><div><h1>Shipments</h1><div class="sub">{{ meta ? meta.total : 0 }} {{ filter === 'all' ? 'shipments' : label(filter).toLowerCase() + ' shipments' }}</div></div></div>

  <div class="chips">
    <button class="chip" :class="{ on: filter === 'all' }" @click="setFilter('all')">All</button>
    <button v-for="s in shipmentStatuses" :key="s" class="chip" :class="{ on: filter === s }" @click="setFilter(s)">{{ label(s) }}</button>
  </div>

  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div v-if="notice" class="banner">{{ notice }}</div>

  <div class="card" style="border-radius:30px;padding:16px 26px">
    <div style="overflow-x:auto">
    <table class="rt" style="width:100%;border-collapse:collapse;min-width:900px">
      <thead><tr><th>ORDER</th><th>CUSTOMER</th><th>COURIER</th><th>AWB</th><th>STATUS</th><th>UPDATE STATUS</th><th></th></tr></thead>
      <tbody>
        <tr v-for="s in rows" :key="s.id">
          <td data-label="Order" style="font-weight:700;white-space:nowrap">{{ s.order_no }}</td><td data-label="Customer">{{ s.customer_name }}</td><td data-label="Courier">{{ s.courier || '—' }}</td>
          <td data-label="AWB" style="font-weight:700;white-space:nowrap">{{ s.awb || "—" }}</td>
          <td data-label="Status"><span class="badge" :style="shipmentPill(s.status)">{{ label(s.status) }}</span></td>
          <td data-label="Update Status">
            <div style="display:flex;gap:8px;align-items:center">
              <select v-model="s.next" style="height:38px;width:170px;padding:0 10px;font-size:13px" :aria-label="'Status for ' + s.order_no"><option v-for="x in shipmentStatuses" :key="x" :value="x">{{ label(x) }}</option></select>
              <button class="ghost" :disabled="s.busy || s.next === s.status" @click="update(s)">Update</button>
            </div>
            <div v-if="s.err" class="err" role="alert" style="margin-top:4px">{{ s.err }}</div>
          </td>
          <td data-label=""><div style="display:flex;gap:8px"><button class="ghost" :disabled="s.busy" @click="sync(s)">Sync</button><a v-if="s.tracking_url" class="ghost" :href="s.tracking_url" target="_blank" rel="noopener">Track</a></div></td>
        </tr>
        <tr v-if="loading && !rows.length"><td colspan="7" class="state">Loading shipments&hellip;</td></tr>
        <tr v-else-if="!rows.length && !error"><td colspan="7" style="color:var(--muted)">No shipments with this status.</td></tr>
      </tbody>
    </table>
    </div>
    <AdminPager :meta="meta" @page="go" />
  </div>
</AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AdminPager from './AdminPager.vue'
import { apiError } from '@/services/api'
import { adminShipments } from '@/services/adminService'
import { shipmentStatuses, shipmentPill, label } from './adminUi'

const rows = ref([])
const meta = ref(null)
const filter = ref('all')
const page = ref(1)
const loading = ref(false)
const error = ref('')
const notice = ref('')
let seq = 0
const wrap = (s) => ({ ...s, next: s.status, busy: false, err: '' })

async function load() {
  const my = ++seq
  loading.value = true; error.value = ''
  try {
    const res = await adminShipments.list({ status: filter.value, page: page.value, per_page: 10 })
    if (my !== seq) return
    rows.value = res.data.map(wrap); meta.value = res.meta
  } catch (e) { if (my === seq) error.value = apiError(e, 'Could not load shipments.').message } finally { if (my === seq) loading.value = false }
}
function setFilter(f) { filter.value = f; page.value = 1; notice.value = ''; load() }
function go(p) { page.value = p; load() }

async function act(s, fn, done) {
  s.busy = true; s.err = ''; notice.value = ''
  try { const r = await fn(); Object.assign(s, r, { next: r.status }); notice.value = done(r) }
  catch (e) { s.err = apiError(e, 'Action failed.').message } finally { s.busy = false }
}
const update = (s) => act(s, () => adminShipments.updateStatus(s.id, s.next), (r) => `${r.order_no} marked ${label(r.status)}.`)
const sync = (s) => act(s, () => adminShipments.sync(s.id), (r) => `${r.order_no} synced: ${label(r.status)}.`)
onMounted(load)
</script>
