<template>
<AdminLayout active="orders">
  <div class="hd">
    <div><h1>Orders</h1><div class="sub">{{ meta ? meta.total : 0 }} {{ meta && meta.total === 1 ? 'order' : 'orders' }}{{ filter === 'all' ? '' : ' · ' + label(filter) }}</div></div>
    <div><label style="position:absolute;left:-9999px" for="oq">Search orders</label><input id="oq" v-model="q" class="s" placeholder="Order no, name or email"></div>
  </div>
  <div class="chips"><button class="chip" :class="{ on: filter === 'all' }" @click="setFilter('all')">All</button><button v-for="st in orderStatuses" :key="st" class="chip" :class="{ on: filter === st }" @click="setFilter(st)">{{ label(st) }}</button></div>

  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>

  <div class="ordwrap">
    <div ref="listEl" class="card ordlist" style="flex:1 1 560px;border-radius:30px;padding:16px 20px;box-sizing:border-box;min-width:0">
      <div style="overflow-x:auto">
      <table class="rt" style="width:100%;border-collapse:collapse;min-width:620px">
        <thead><tr><th>ORDER</th><th>CUSTOMER</th><th>DATE</th><th>TOTAL</th><th>PAYMENT</th><th>STATUS</th></tr></thead>
        <tbody>
          <tr v-for="o in orders" :key="o.order_no" :class="{ sel: selNo === o.order_no }" style="cursor:pointer" @click="pick(o.order_no)"><td data-label="Order" style="font-weight:700;white-space:nowrap">{{ o.order_no }}</td><td data-label="Customer">{{ o.customer?.name }}</td><td data-label="Date"><span style="white-space:nowrap">{{ shortDate(o.placed_at) }}</span></td><td data-label="Total" style="font-weight:700;white-space:nowrap">{{ inrp(o.total_paise) }}</td><td data-label="Payment"><span class="badge" :style="paymentPill(o.payment_status)">{{ label(o.payment_status) }}</span></td><td data-label="Status"><span class="badge" :style="orderPill(o.status)">{{ label(o.status) }}</span></td></tr>
          <tr v-if="loading && !orders.length"><td colspan="6" class="state">Loading orders&hellip;</td></tr>
          <tr v-else-if="!orders.length && !error"><td colspan="6" style="color:var(--muted)">No {{ filter === 'all' ? '' : filter + ' ' }}orders found.</td></tr>
        </tbody>
      </table>
      </div>
      <AdminPager :meta="meta" @page="go" />
    </div>

    <aside v-if="selNo" ref="detailEl" class="card orddetail" style="flex:0 1 360px;border-radius:30px;padding:28px;box-sizing:border-box;min-width:0">
      <div v-if="detailLoading && !sel" class="state">Loading order&hellip;</div>
      <div v-else-if="detailError && !sel" class="err">{{ detailError }}</div>
      <template v-if="sel">
        <button type="button" class="ghost backbtn" @click="backToList">&larr; Back to list</button>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px"><h2 style="margin:0;font-size:20px;font-weight:800">{{ sel.order_no }}</h2><span class="badge" :style="orderPill(sel.status)">{{ label(sel.status) }}</span></div>
        <div style="font-size:12px;color:var(--muted);margin:4px 0 20px">{{ formatDateTime(sel.placed_at) }}</div>

        <div class="cap">CUSTOMER</div>
        <div style="font-size:14px;line-height:1.7;color:var(--muted)"><span style="color:var(--ink);font-weight:700">{{ sel.customer?.name }}</span><br>{{ sel.customer?.email }}<br>{{ sel.customer?.phone }}<br>{{ address }}</div>

        <div class="cap" style="margin-top:20px">ITEMS</div>
        <div style="font-size:14px;line-height:1.5">
          <div v-for="it in sel.items" :key="it.id" style="display:flex;justify-content:space-between;gap:12px;padding:4px 0"><span>{{ it.name }} <span style="color:var(--muted)">{{ it.size_label }} &times; {{ it.qty }}</span></span><span style="font-weight:700;white-space:nowrap">{{ inrp(it.total_paise) }}</span></div>
        </div>

        <div style="margin:16px 0 0;padding-top:12px;border-top:1px dashed var(--line);font-size:13px;color:var(--muted)">
          <div class="tr"><span>Subtotal</span><span>{{ inrp(sel.subtotal_paise) }}</span></div>
          <div class="tr"><span>Shipping</span><span>{{ inrp(sel.shipping_paise) }}</span></div>
          <div class="tr"><span>GST</span><span>{{ inrp(sel.tax_paise) }}</span></div>
          <div v-if="sel.discount_paise" class="tr"><span>Discount</span><span>&minus;{{ inrp(sel.discount_paise) }}</span></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin:10px 0 20px;font-weight:800"><span>Total</span><span style="color:var(--green)">{{ inrp(sel.total_paise) }}</span></div>

        <div class="cap">PAYMENT</div>
        <div style="font-size:14px;line-height:1.7;margin-bottom:20px"><span class="badge" :style="paymentPill(sel.payment_status)">{{ label(sel.payment_status) }}</span><template v-if="sel.payment"> <span style="color:var(--muted)">&nbsp;{{ sel.payment.gateway }}<template v-if="sel.payment.method"> &middot; {{ sel.payment.method }}</template></span></template></div>

        <div class="cap">SHIPMENT</div>
        <div v-if="sel.shipment" style="font-size:14px;line-height:1.7;margin-bottom:20px">{{ sel.shipment.courier || sel.shipment.provider }} &middot; AWB <span style="font-weight:700">{{ sel.shipment.awb || '—' }}</span><br><span style="color:var(--muted)">Status: {{ label(sel.shipment.status) }}</span></div>
        <div v-else style="font-size:14px;line-height:1.7;margin-bottom:20px;color:var(--muted)">No shipment created yet</div>

        <label for="st" class="cap" style="display:block">UPDATE ORDER STATUS</label>
        <select id="st" v-model="chosen"><option v-for="st in orderStatuses" :key="st" :value="st">{{ label(st) }}</option></select>
        <div style="display:flex;gap:12px;margin-top:16px;flex-wrap:wrap"><button class="btn" :disabled="acting" @click="updateStatus">Update status</button><button class="ghost" style="min-height:44px;padding:9px 20px;font-size:14px;border-color:var(--ink)" :disabled="acting" @click="createShipment">Create shipment</button></div>
        <div v-if="note" :style="noteIsErr ? 'background:#FBE3E1;color:#B3261E' : 'background:var(--tint);color:var(--green)'" style="margin-top:14px;border-radius:14px;padding:10px 14px;font-size:13px;font-weight:700" data-testid="order-note">{{ note }}</div>
      </template>
    </aside>
  </div>
</AdminLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AdminPager from './AdminPager.vue'
import { apiError } from '@/services/api'
import { adminOrders } from '@/services/adminService'
import { debounce } from '@/services/adminGuard'
import { inrp, formatDate, formatDateTime } from '@/utils/format'
import { orderStatuses, orderPill, paymentPill, label } from './adminUi'

const shortDate = (iso) => (iso ? new Date(iso).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' }) : '')
const orders = ref([])
const meta = ref(null)
const filter = ref('all')
const q = ref('')
const page = ref(1)
const loading = ref(false)
const error = ref('')
const selNo = ref(null)
const sel = ref(null)
const detailLoading = ref(false)
const detailError = ref('')
const chosen = ref('pending')
const note = ref('')
const noteIsErr = ref(false)
const acting = ref(false)
let seq = 0
let dseq = 0

const address = computed(() => { const a = sel.value?.address; return a ? [a.line1, a.line2, `${a.city}, ${a.state} ${a.pincode}`].filter(Boolean).join(', ') : '' })

async function load(keepSelection = false) {
  const my = ++seq
  loading.value = true; error.value = ''
  try {
    const res = await adminOrders.list({ status: filter.value, search: q.value.trim(), page: page.value, per_page: 10 })
    if (my !== seq) return
    orders.value = res.data; meta.value = res.meta
    if (!keepSelection) {
      if (!res.data.length) { selNo.value = null; sel.value = null }
      else if (!res.data.some((o) => o.order_no === selNo.value)) select(res.data[0].order_no)
    }
  } catch (e) { if (my === seq) error.value = apiError(e, 'Could not load orders.').message } finally { if (my === seq) loading.value = false }
}
const reload = debounce(() => { page.value = 1; load() })
watch(q, reload)
function setFilter(f) { filter.value = f; page.value = 1; load() }
function go(p) { page.value = p; load() }

const detailEl = ref(null)
const listEl = ref(null)
const narrow = () => typeof window !== 'undefined' && window.matchMedia('(max-width:1099px)').matches
function pick(no) { select(no); if (narrow()) nextTick(() => detailEl.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })) }
function backToList() { listEl.value?.scrollIntoView({ behavior: 'smooth', block: 'start' }) }
async function select(no) {
  selNo.value = no; note.value = ''; detailError.value = ''
  const my = ++dseq
  detailLoading.value = true
  if (sel.value && sel.value.order_no !== no) sel.value = null
  try { const o = await adminOrders.get(no); if (my === dseq) { sel.value = o; chosen.value = o.status } }
  catch (e) { if (my === dseq) detailError.value = apiError(e, 'Could not load the order.').message } finally { if (my === dseq) detailLoading.value = false }
}

function patchRow(o) { const i = orders.value.findIndex((x) => x.order_no === o.order_no); if (i >= 0) orders.value[i] = { ...orders.value[i], ...o } }
function say(msg, isErr = false) { note.value = msg; noteIsErr.value = isErr }

async function updateStatus() {
  acting.value = true
  try { const o = await adminOrders.updateStatus(sel.value.order_no, chosen.value); sel.value = o; patchRow(o); say(`Status updated to ${label(o.status)}.`) }
  catch (e) { say(apiError(e, 'Could not update the status.').message, true) } finally { acting.value = false }
}
async function createShipment() {
  acting.value = true
  try {
    const s = await adminOrders.createShipment(sel.value.order_no)
    sel.value = await adminOrders.get(sel.value.order_no); patchRow(sel.value); chosen.value = sel.value.status
    say(`Shipment created with ${s.courier || s.provider}, AWB ${s.awb}.`)
  } catch (e) { say(apiError(e, 'Could not create the shipment.').message, true) } finally { acting.value = false }
}
onMounted(() => load())
</script>

<style scoped>
.chips{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.ordwrap{display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start}
@media (max-width:1099px){.ordwrap{display:block}.orddetail{margin-top:16px}.ordlist{width:100%}}
@media (max-width:767px){.chips{flex-wrap:nowrap;overflow-x:auto;margin:0 -16px 16px;padding:0 16px 4px;-webkit-overflow-scrolling:touch}.chips .chip{flex:0 0 auto;white-space:nowrap}.orddetail{padding:20px 16px!important}}
.backbtn{display:none;margin-bottom:12px}
@media (max-width:1099px){.backbtn{display:inline-flex}}
.cap{font-size:12px;font-weight:700;color:var(--muted);letter-spacing:1px;margin-bottom:6px}
.tr{display:flex;justify-content:space-between;padding:2px 0}
tr.sel td{background:var(--sun)}
td{padding-left:6px;padding-right:6px}
th{padding-left:6px;padding-right:6px}
</style>
