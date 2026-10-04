<template>
<AdminLayout active="payments">
  <div class="hd"><div><h1>Payments</h1><div class="sub">{{ meta ? meta.total : 0 }} {{ filter === 'all' ? 'transactions' : filter + ' transactions' }}</div></div></div>

  <div class="statgrid" style="margin-bottom:24px">
    <div v-for="s in summary" :key="s.label" class="card stat" :style="`border-radius:26px;padding:22px;box-sizing:border-box;border-top:4px solid ${s.accent}`">
      <div style="font-size:13px;font-weight:600;color:var(--muted)">{{ s.label }}</div>
      <div style="font-size:30px;font-weight:800;margin-top:8px">{{ s.value }}</div>
    </div>
  </div>

  <div class="chips">
    <button v-for="f in filters" :key="f.key" class="chip" :class="{ on: filter === f.key }" @click="setFilter(f.key)">{{ f.label }}</button>
  </div>

  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div v-if="notice" class="banner">{{ notice }}</div>

  <div class="card" style="border-radius:30px;padding:16px 26px">
    <div style="overflow-x:auto">
    <table class="rt" style="width:100%;border-collapse:collapse;min-width:860px">
      <thead><tr><th>PAYMENT ID</th><th>ORDER</th><th>CUSTOMER</th><th>DATE</th><th>AMOUNT</th><th>METHOD</th><th>STATUS</th><th></th></tr></thead>
      <tbody>
        <tr v-for="p in rows" :key="p.id">
          <td data-label="Payment ID" style="font-weight:700;font-size:13px">{{ p.gateway_payment_id || ('#' + p.id) }}</td>
          <td data-label="Order" style="white-space:nowrap"><RouterLink to="/admin/orders" style="color:var(--green);font-weight:700">{{ p.order_no }}</RouterLink></td>
          <td data-label="Customer">{{ p.customer_name }}</td><td data-label="Date">{{ formatDate(p.created_at) }}</td>
          <td data-label="Amount" style="font-weight:700">{{ inrp(p.amount_paise) }}</td>
          <td data-label="Method">{{ p.method || '—' }}</td>
          <td data-label="Status"><span class="badge" :style="paymentPill(p.status)">{{ label(p.status) }}</span></td>
          <td data-label="">
            <div v-if="p.status === 'paid'">
              <div v-if="confirmId === p.id" style="display:flex;gap:8px;align-items:center"><span style="font-size:13px;font-weight:600;color:#B3261E">Refund?</span><button class="ghost danger" :disabled="busy" @click="refund(p)">Yes</button><button class="ghost" @click="confirmId = null">No</button></div>
              <button v-else class="ghost" @click="confirmId = p.id">Refund</button>
            </div>
            <div v-if="rowErr[p.id]" class="err" role="alert" style="margin-top:4px">{{ rowErr[p.id] }}</div>
          </td>
        </tr>
        <tr v-if="loading && !rows.length"><td colspan="8" class="state">Loading payments&hellip;</td></tr>
        <tr v-else-if="!rows.length && !error"><td colspan="8" style="color:var(--muted)">No payments with this status.</td></tr>
      </tbody>
    </table>
    </div>
    <AdminPager :meta="meta" @page="go" />
  </div>
</AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AdminPager from './AdminPager.vue'
import { apiError } from '@/services/api'
import { adminPayments } from '@/services/adminService'
import { inrp, formatDate } from '@/utils/format'
import { paymentPill, label } from './adminUi'

const rows = ref([])
const meta = ref(null)
const filter = ref('all')
const page = ref(1)
const loading = ref(false)
const error = ref('')
const notice = ref('')
const confirmId = ref(null)
const busy = ref(false)
const rowErr = ref({})
let seq = 0
const filters = [{ key: 'all', label: 'All' }, { key: 'paid', label: 'Paid' }, { key: 'failed', label: 'Failed' }, { key: 'refunded', label: 'Refunded' }]

const summary = computed(() => {
  const s = meta.value?.summary || {}
  return [
    { label: 'Collected', value: inrp(s.collected_paise), accent: '#0B5D1E' },
    { label: 'Failed', value: inrp(s.failed_paise), accent: '#D9534F' },
    { label: 'Refunded', value: inrp(s.refunded_paise), accent: '#FFB400' },
    { label: 'Transactions', value: String(s.transactions ?? 0), accent: '#9ACD32' },
  ]
})

async function load() {
  const my = ++seq
  loading.value = true; error.value = ''
  try {
    const res = await adminPayments.list({ status: filter.value, page: page.value, per_page: 10 })
    if (my !== seq) return
    rows.value = res.data; meta.value = res.meta
  } catch (e) { if (my === seq) error.value = apiError(e, 'Could not load payments.').message } finally { if (my === seq) loading.value = false }
}
function setFilter(f) { filter.value = f; page.value = 1; load() }
function go(p) { page.value = p; load() }

async function refund(p) {
  busy.value = true; rowErr.value = {}; notice.value = ''
  try { await adminPayments.refund(p.id); confirmId.value = null; notice.value = `Payment for ${p.order_no} refunded.`; await load() }
  catch (e) { rowErr.value = { [p.id]: apiError(e, 'Refund failed.').message }; confirmId.value = null } finally { busy.value = false }
}
onMounted(load)
</script>
