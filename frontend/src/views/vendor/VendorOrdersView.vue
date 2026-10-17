<template>
<VendorLayout active="orders">
  <div class="hd">
    <div><h1>Orders</h1><div class="sub">{{ meta ? meta.total : 0 }} orders</div></div>
    <div><label class="sr-only" for="q">Search orders</label><input id="q" v-model="q" class="s" placeholder="Order no or customer"></div>
  </div>
  <div class="chips" role="group" aria-label="Filter by status">
    <button v-for="c in chips" :key="c" type="button" class="chip" :class="{ on: status === c }" :aria-pressed="status === c" @click="setStatus(c)">{{ c === 'all' ? 'All' : label(c) }}</button>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>

  <div class="ordwrap">
    <div class="card ordlist" :class="{ hide: sel && narrow }" style="padding:16px 22px;box-sizing:border-box">
      <div style="overflow-x:auto">
      <table class="rt" style="width:100%;border-collapse:collapse;min-width:520px">
        <thead><tr><th>Order</th><th>Customer</th><th>Date</th><th>Earning</th><th>Status</th></tr></thead>
        <tbody>
          <tr v-for="o in rows" :key="o.order_no" :class="{ sel: sel && sel.order_no === o.order_no }" style="cursor:pointer" tabindex="0" @click="sel = o" @keydown.enter="sel = o">
            <td data-label="Order" style="font-weight:700">{{ o.order_no }}</td><td data-label="Customer">{{ o.customer_name }}</td><td data-label="Date">{{ formatDate(o.placed_at) }}</td><td data-label="Earning" style="font-weight:700">{{ inrp(o.earning_paise) }}</td>
            <td data-label="Status"><span class="badge" :style="vOrderPill(o.status)">{{ label(o.status) }}</span></td>
          </tr>
          <tr v-if="loading && !rows.length"><td colspan="5" class="state">Loading orders&hellip;</td></tr>
          <tr v-else-if="!rows.length && !error"><td colspan="5" style="color:var(--muted)">No orders found.</td></tr>
        </tbody>
      </table>
      </div>
      <AdminPager :meta="meta" @page="go" />
    </div>

    <aside v-if="sel" class="card orddetail" aria-label="Order details">
      <button type="button" class="ghost backbtn" @click="sel = null">&larr; All orders</button>
      <div class="dh"><div><h2>{{ sel.order_no }}</h2><div class="muted">{{ formatDateTime(sel.placed_at) }}</div></div><span class="badge" :style="vOrderPill(sel.status)">{{ label(sel.status) }}</span></div>
      <div class="cap">Customer</div>
      <div class="blk"><b>{{ sel.customer_name }}</b><br>{{ sel.address.line1 }}, {{ sel.address.city }}, {{ sel.address.state }} {{ sel.address.pincode }}</div>
      <div class="cap">Items</div>
      <div v-for="it in sel.items" :key="it.id" class="it"><div class="thumb"><img v-if="it.image_url" :src="it.image_url" alt=""></div><div class="grow">{{ it.name }}<small>{{ it.size_label }} &times; {{ it.qty }}</small></div><b>{{ inrp(it.total_paise) }}</b></div>
      <div class="sum"><div><span>Order value</span><span>{{ inrp(sel.subtotal_paise) }}</span></div><div><span>Kokango commission</span><span>- {{ inrp(sel.commission_paise) }}</span></div><div class="tot"><span>Your earning</span><span>{{ inrp(sel.earning_paise) }}</span></div></div>
      <div v-if="nextStatus[sel.status]" class="act"><button type="button" class="btn btn-block" :disabled="busy" @click="advance">{{ busy ? 'Updating…' : nextLabel[nextStatus[sel.status]] }}</button>
        <button v-if="sel.status === 'pending'" type="button" class="ghost danger btn-block" :disabled="busy" @click="cancel">Reject order</button></div>
      <p v-else class="muted">{{ sel.status === 'shipped' ? 'The courier will mark this order delivered.' : 'No further action needed.' }}</p>
    </aside>
  </div>
</VendorLayout>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import VendorLayout from '@/layouts/VendorLayout.vue'
import AdminPager from '@/views/admin/AdminPager.vue'
import { vendorOrders } from '@/services/vendorService'
import { apiError } from '@/services/api'
import { debounce } from '@/services/adminGuard'
import { inrp, formatDate, formatDateTime } from '@/utils/format'
import { useToastStore } from '@/stores/toast'
import { vOrderPill, label, nextStatus, nextLabel } from './vendorUi'

const toast = useToastStore()
const rows = ref([]); const meta = ref(null); const q = ref(''); const status = ref('all'); const page = ref(1); const sel = ref(null)
const loading = ref(false); const error = ref(''); const busy = ref(false)
const chips = ['all', 'pending', 'confirmed', 'packed', 'shipped', 'delivered', 'cancelled']
const narrow = ref(false)
const onResize = () => { narrow.value = window.innerWidth < 1100 }
async function load() {
  loading.value = true; error.value = ''
  try { const r = await vendorOrders.list({ status: status.value, search: q.value, page: page.value }); rows.value = r.data; meta.value = r.meta; if (sel.value) sel.value = r.data.find((x) => x.order_no === sel.value.order_no) || sel.value }
  catch (e) { error.value = apiError(e, 'Could not load orders.').message } finally { loading.value = false }
}
const go = (p) => { page.value = p; load() }
const setStatus = (c) => { status.value = c; page.value = 1; sel.value = null; load() }
watch(q, debounce(() => { page.value = 1; load() }, 300))
async function setTo(s) {
  busy.value = true
  try { await vendorOrders.updateStatus(sel.value.order_no, s); toast.show(`Order ${label(s).toLowerCase()}`); await load() }
  catch (e) { error.value = apiError(e, 'Could not update the order.').message } finally { busy.value = false }
}
const advance = () => setTo(nextStatus[sel.value.status])
const cancel = () => setTo('cancelled')
onMounted(() => { onResize(); window.addEventListener('resize', onResize); load() })
onBeforeUnmount(() => window.removeEventListener('resize', onResize))
</script>

<style scoped>
.ordwrap{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:24px;align-items:start}
.ordwrap:not(:has(.orddetail)){grid-template-columns:minmax(0,1fr)}
.orddetail{padding:22px;box-sizing:border-box;position:sticky;top:24px}
.dh{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px}
.dh h2{margin:0;font-size:20px;font-weight:800}
.muted{color:var(--muted);font-size:13px}
.cap{font-size:11.5px;font-weight:700;color:var(--muted);letter-spacing:.8px;text-transform:uppercase;margin:16px 0 6px}
.blk{font-size:14px;line-height:1.6}
.it{display:flex;align-items:center;gap:12px;padding:10px 0;border-top:1px solid var(--line);font-size:14px;font-weight:600}
.it small{display:block;font-size:12px;color:var(--muted);font-weight:500}
.thumb{flex:0 0 44px;height:44px;border-radius:10px;background:var(--tint);display:flex;align-items:center;justify-content:center;overflow:hidden}
.thumb img{height:100%;width:auto;object-fit:contain}
.grow{flex:1;min-width:0}
.sum{margin-top:12px;border-top:1px dashed var(--line);padding-top:10px;display:flex;flex-direction:column;gap:6px;font-size:14px;color:var(--muted)}
.sum>div{display:flex;justify-content:space-between}
.sum .tot{font-size:17px;font-weight:800;color:var(--forest);padding-top:6px}
.act{display:flex;flex-direction:column;gap:10px;margin-top:18px}
.backbtn{display:none;margin-bottom:12px}
tr.sel td{background:var(--sun)}
@media (max-width:1099px){.ordwrap{display:block}.orddetail{margin-top:0;position:static}.ordlist.hide{display:none}.backbtn{display:inline-flex}}
</style>
