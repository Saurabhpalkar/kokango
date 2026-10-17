<template>
<AdminLayout active="vendors">
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div v-if="loading && !d" class="state">Loading vendor&hellip;</div>
  <template v-if="d">
    <div class="hd">
      <div>
        <RouterLink to="/admin/vendors" class="mk-more">&larr; All vendors</RouterLink>
        <h1 style="margin-top:6px">{{ v.store_name }} <span class="badge st" :style="vendorPill(v.status)">{{ vendorStatusLabel[v.status] }}</span></h1>
        <div class="sub">{{ v.owner_name }} &middot; {{ v.city }} &middot; joined {{ formatDate(v.created_at) }}</div>
      </div>
      <div class="mk-actions">
        <button v-if="v.status === 'pending' || v.status === 'rejected'" class="btn" :disabled="busy || !ready" :title="ready ? '' : 'Complete the checklist first'" @click="act('approve')">Approve vendor</button>
        <button v-if="v.status === 'pending'" class="ghost danger" :disabled="busy" @click="ask('reject')">Reject</button>
        <button v-if="v.status === 'approved'" class="ghost danger" :disabled="busy" @click="ask('suspend')">Suspend</button>
        <button v-if="v.status === 'suspended'" class="btn" :disabled="busy" @click="act('reactivate')">Reactivate</button>
      </div>
    </div>

    <div v-if="reasonFor" class="card mk-pad reasonbox">
      <h2 class="mk-h2">{{ reasonFor === 'reject' ? 'Reject this application' : 'Suspend this vendor' }}</h2>
      <p class="mk-muted" style="margin:6px 0 0">{{ reasonFor === 'reject' ? 'The vendor sees this reason, so say what they need to fix.' : 'The vendor sees this reason. Their live products are hidden from the store until you reactivate them.' }}</p>
      <div class="mk-reason">
        <label class="field" style="margin:0">Reason<textarea v-model="reason" class="mk-ta" :class="{ bad: reasonErr }" rows="3"></textarea><span v-if="reasonErr" class="err">{{ reasonErr }}</span></label>
        <div class="mk-actions"><button class="btn danger-btn" :disabled="busy" @click="act(reasonFor, reason)">{{ reasonFor === 'reject' ? 'Reject application' : 'Suspend vendor' }}</button><button class="ghost" @click="reasonFor = ''">Cancel</button></div>
      </div>
    </div>
    <div v-if="v.status_reason && v.status !== 'approved'" class="mk-note" style="margin-bottom:20px"><b>{{ v.status === 'suspended' ? 'Suspended' : 'Rejected' }}:</b> {{ v.status_reason }}</div>

    <div class="statgrid">
      <div v-for="s in cards" :key="s.l" class="card mk-stat"><div class="mk-sl">{{ s.l }}</div><div class="mk-sv">{{ s.v }}</div><div class="mk-sn">{{ s.n }}</div></div>
    </div>

    <div class="mk-row">
      <div class="g2" style="display:grid;gap:24px;align-content:start">
        <div class="card mk-pad">
          <div class="mk-ch"><h2>Sales, last 14 days</h2><span class="mk-muted">{{ inrp(total) }} total</span></div>
          <div class="mk-bars" role="img" :aria-label="`Vendor sales for the last 14 days, ${inrp(total)} in total`">
            <div v-for="x in d.sales_by_day" :key="x.label" class="mk-bar"><span class="mk-bf" :style="`height:${Math.max(3, x.paise / max * 100)}%`" :title="`${x.label}: ${inrp(x.paise)}`"></span><span class="mk-bl">{{ x.label.split(' ')[0] }}</span></div>
          </div>
        </div>
        <div class="card mk-pad">
          <div class="mk-ch"><h2>Products ({{ d.products.length }})</h2><RouterLink v-if="reviewCount" to="/admin/approvals" class="mk-more">{{ reviewCount }} awaiting review</RouterLink></div>
          <div style="overflow-x:auto">
          <table class="rt" style="width:100%;border-collapse:collapse;min-width:480px">
            <thead><tr><th>Product</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead>
            <tbody>
              <tr v-for="p in d.products" :key="p.id">
                <td data-label="Product"><div class="mk-who"><div class="mk-th"><img v-if="p.image_url" :src="p.image_url" alt=""></div><b>{{ p.name }}</b></div></td>
                <td data-label="Price">{{ inrp(p.min_price_paise) }}</td><td data-label="Stock">{{ p.stock_total }}</td>
                <td data-label="Status"><span class="badge" :style="productPill(p.status)">{{ productStatusLabel[p.status] || p.status }}</span></td>
              </tr>
              <tr v-if="!d.products.length"><td colspan="4" class="mk-muted">No products yet.</td></tr>
            </tbody>
          </table>
          </div>
        </div>
        <div class="card mk-pad">
          <div class="mk-ch"><h2>Recent orders</h2></div>
          <div style="overflow-x:auto">
          <table class="rt" style="width:100%;border-collapse:collapse;min-width:480px">
            <thead><tr><th>Order</th><th>Customer</th><th>Value</th><th>Commission</th><th>Status</th></tr></thead>
            <tbody>
              <tr v-for="o in d.orders" :key="o.id">
                <td data-label="Order"><b>{{ o.order_no }}</b></td><td data-label="Customer">{{ o.customer_name }}</td><td data-label="Value">{{ inrp(o.subtotal_paise) }}</td><td data-label="Commission">{{ inrp(o.commission_paise) }}</td>
                <td data-label="Status"><span class="badge" :style="vOrderPill(o.status)">{{ label(o.status) }}</span></td>
              </tr>
              <tr v-if="!d.orders.length"><td colspan="5" class="mk-muted">No orders yet.</td></tr>
            </tbody>
          </table>
          </div>
        </div>
      </div>

      <div class="g1" style="display:grid;gap:24px;align-content:start">
        <div class="card mk-pad">
          <div class="mk-ch"><h2>Approval checklist</h2></div>
          <div v-for="c in d.checklist" :key="c.key" class="mk-check"><span class="mk-tick" :class="{ no: !c.ok }" aria-hidden="true">{{ c.ok ? '✓' : '!' }}</span><span>{{ c.label }}<span class="sr-only">{{ c.ok ? ' — complete' : ' — missing' }}</span></span></div>
          <p v-if="!ready" class="mk-note" style="margin:12px 0 0">Some details are missing. The vendor can add them under Store settings, or you can edit the account later.</p>
        </div>
        <div class="card mk-pad">
          <div class="mk-ch"><h2>Commission</h2></div>
          <form class="comm" @submit.prevent="saveCommission">
            <label class="field" style="margin:0">Custom rate (%) <small>(blank = marketplace default)</small><input v-model="commission" inputmode="decimal" placeholder="Default" :class="{ bad: commErr }"><span v-if="commErr" class="err">{{ commErr }}</span></label>
            <button class="btn btn-sm" :disabled="busy">Save</button>
          </form>
          <p class="mk-muted" style="margin:10px 0 0">Currently charging <b>{{ d.stats.commission_percent }}%</b> on each sale. Changes apply to new orders only.</p>
        </div>
        <div class="card mk-pad">
          <div class="mk-ch"><h2>Details</h2></div>
          <dl class="mk-kv">
            <dt>Email</dt><dd>{{ v.email }}</dd><dt>Phone</dt><dd>{{ v.phone || '—' }}</dd>
            <dt>Pickup</dt><dd>{{ v.pickup_address ? v.pickup_address + (v.pincode ? ' – ' + v.pincode : '') : '—' }}</dd>
            <dt>FSSAI</dt><dd>{{ v.fssai_no || '—' }}</dd><dt>GST</dt><dd>{{ v.gst_no || '—' }}</dd><dt>PAN</dt><dd>{{ v.pan_no || '—' }}</dd>
            <dt>Bank</dt><dd>{{ v.bank?.account_no ? `${v.bank.bank_name} · ••••${v.bank.account_no.slice(-4)} · ${v.bank.ifsc}` : '—' }}</dd>
          </dl>
        </div>
        <div class="card mk-pad">
          <div class="mk-ch"><h2>Payouts</h2><RouterLink to="/admin/vendor-payouts" class="mk-more">Manage</RouterLink></div>
          <div v-for="p in d.payouts.slice(0, 4)" :key="p.id" class="mk-li"><div class="mk-grow">{{ p.ref }}<small>{{ p.period }}</small></div><div style="text-align:right"><b>{{ inrp(p.amount_paise) }}</b><br><span class="badge" :style="payoutPill(p.status)">{{ label(p.status) }}</span></div></div>
          <div v-if="!d.payouts.length" class="mk-muted">No payouts yet.</div>
        </div>
      </div>
    </div>
  </template>
</AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { adminVendors } from '@/services/adminMarketplaceService'
import { useToastStore } from '@/stores/toast'
import { apiError } from '@/services/api'
import { inrp, formatDate } from '@/utils/format'
import { vendorPill, vendorStatusLabel, productPill, productStatusLabel, payoutPill, label } from './marketUi'
import { vOrderPill } from '@/views/vendor/vendorUi'

const route = useRoute(); const toast = useToastStore()
const d = ref(null); const loading = ref(false); const busy = ref(false); const error = ref('')
const reasonFor = ref(''); const reason = ref(''); const reasonErr = ref(''); const commission = ref(''); const commErr = ref('')
const v = computed(() => d.value.vendor)
const ready = computed(() => d.value.checklist.every((c) => c.ok))
const total = computed(() => d.value.sales_by_day.reduce((a, x) => a + x.paise, 0))
const max = computed(() => Math.max(1, ...d.value.sales_by_day.map((x) => x.paise)))
const reviewCount = computed(() => d.value.products.filter((p) => p.status === 'pending_review').length)
const cards = computed(() => { const s = d.value.stats; return [
  { l: 'Sales', v: inrp(s.sales_paise), n: `${s.orders} orders` },
  { l: 'Commission earned', v: inrp(s.commission_paise), n: `${s.commission_percent}% rate` },
  { l: 'Vendor earnings', v: inrp(s.earnings_paise), n: 'After commission' },
  { l: 'Balance to pay', v: inrp(s.payable_paise), n: 'Delivered, unpaid' },
] })
async function load() {
  loading.value = true; error.value = ''
  try { d.value = await adminVendors.get(route.params.id); commission.value = d.value.vendor.commission_percent ?? '' }
  catch (e) { error.value = apiError(e, 'Could not load this vendor.').message } finally { loading.value = false }
}
const ask = (a) => { reasonFor.value = a; reason.value = ''; reasonErr.value = '' }
async function act(action, why = '') {
  busy.value = true; reasonErr.value = ''; error.value = ''
  try { await adminVendors.act(route.params.id, action, why); reasonFor.value = ''; toast.show({ approve: 'Vendor approved', reject: 'Application rejected', suspend: 'Vendor suspended', reactivate: 'Vendor reactivated' }[action]); await load() }
  catch (e) { const r = apiError(e, 'Could not update the vendor.'); if (r.errors.reason) reasonErr.value = r.message; else error.value = r.message } finally { busy.value = false }
}
async function saveCommission() {
  commErr.value = ''
  if (commission.value !== '' && !(Number(commission.value) >= 0 && Number(commission.value) <= 50)) { commErr.value = 'Enter 0–50.'; return }
  busy.value = true
  try { await adminVendors.update(route.params.id, { ...d.value.vendor, commission_percent: commission.value }); toast.show('Commission updated'); await load() }
  catch (e) { error.value = apiError(e, 'Could not save the commission.').message } finally { busy.value = false }
}
onMounted(load)
</script>

<style scoped>
.st{vertical-align:middle;margin-left:8px;font-size:12px}
.reasonbox{margin-bottom:20px;border:1px solid #E8B7B3}
.danger-btn{background:#B3261E;border-color:#B3261E}
.comm{display:flex;gap:10px;align-items:flex-end}.comm .field{flex:1}
</style>
