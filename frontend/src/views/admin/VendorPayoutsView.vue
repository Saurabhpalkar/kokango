<template>
<AdminLayout active="vendor-payouts">
  <div class="hd">
    <div><h1>Commission &amp; payouts</h1><div class="sub">What vendors are owed, and what Kokango earns</div></div>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div v-if="loading && !d" class="state">Loading payouts&hellip;</div>
  <template v-if="d">
    <div class="statgrid">
      <div v-for="s in cards" :key="s.l" class="card mk-stat"><div class="mk-sl">{{ s.l }}</div><div class="mk-sv">{{ inrp(s.v) }}</div><div class="mk-sn">{{ s.n }}</div></div>
    </div>

    <div class="mk-row">
      <div class="card mk-pad g2">
        <div class="mk-ch"><h2>Vendor balances</h2></div>
        <div v-if="payErr" class="errbox" role="alert"><span>{{ payErr }}</span></div>
        <div style="overflow-x:auto">
        <table class="rt" style="width:100%;border-collapse:collapse;min-width:640px">
          <thead><tr><th>Vendor</th><th>Commission</th><th>Balance to pay</th><th></th></tr></thead>
          <tbody>
            <tr v-for="v in d.vendors" :key="v.id">
              <td data-label="Vendor"><RouterLink :to="'/admin/vendors/' + v.id" class="mk-who"><span class="mk-av">{{ initial(v.store_name) }}</span><span><b>{{ v.store_name }}</b><small>{{ v.status === 'suspended' ? 'Suspended' : v.city }}</small></span></RouterLink></td>
              <td data-label="Commission">{{ v.commission_percent }}%</td>
              <td data-label="Balance"><b>{{ inrp(v.payable_paise) }}</b></td>
              <td data-label=""><button class="btn btn-sm" :disabled="busy || !v.payable_paise || !v.min_ok" :title="!v.min_ok && v.payable_paise ? 'Below the minimum payout' : ''" @click="create(v)">Create payout</button></td>
            </tr>
            <tr v-if="!d.vendors.length"><td colspan="4" class="mk-muted">No active vendors yet.</td></tr>
          </tbody>
        </table>
        </div>
      </div>
      <div class="card mk-pad g1">
        <div class="mk-ch"><h2>Marketplace settings</h2></div>
        <form style="display:grid;gap:14px" @submit.prevent="saveSettings">
          <label class="field" style="margin:0">Default commission (%)<input v-model="sf.default_commission" inputmode="decimal" :class="{ bad: sErr.default_commission }"><span v-if="sErr.default_commission" class="err">{{ sErr.default_commission }}</span></label>
          <label class="field" style="margin:0">Minimum payout (&#8377;)<input v-model="sf.min_payout" inputmode="numeric"></label>
          <label class="mk-toggle"><input v-model="sf.auto_approve_products" type="checkbox"><span>Auto-approve listings from approved vendors<small>Off by default so every new listing is reviewed first.</small></span></label>
          <button class="btn btn-sm" :disabled="busy" style="justify-self:start">Save settings</button>
        </form>
        <p class="mk-muted" style="margin:12px 0 0">A vendor can override the default rate on their own page.</p>
      </div>
    </div>

    <div class="card mk-pad" style="margin-top:24px">
      <div class="mk-ch"><h2>Payout ledger</h2></div>
      <div v-if="payOkId" class="reason">
        <h3 class="mk-h2" style="font-size:16px">Mark {{ payOk.ref }} as paid</h3>
        <label class="field" style="margin:10px 0 0">Bank reference (UTR)<input v-model="utr" :class="{ bad: utrErr }" placeholder="e.g. UTR123456789"><span v-if="utrErr" class="err">{{ utrErr }}</span></label>
        <div class="mk-actions" style="margin-top:12px"><button class="btn btn-sm" :disabled="busy" @click="setStatus(payOk, 'paid', utr)">Confirm payment</button><button class="ghost" @click="payOkId = null">Cancel</button></div>
      </div>
      <div style="overflow-x:auto">
      <table class="rt" style="width:100%;border-collapse:collapse;min-width:720px">
        <thead><tr><th>Reference</th><th>Vendor</th><th>Period</th><th>Amount</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <tr v-for="p in d.payouts" :key="p.id">
            <td data-label="Reference"><b>{{ p.ref }}</b></td><td data-label="Vendor">{{ p.vendor_name }}</td><td data-label="Period">{{ p.period }}</td><td data-label="Amount"><b>{{ inrp(p.amount_paise) }}</b></td>
            <td data-label="Status"><span class="badge" :style="payoutPill(p.status)">{{ label(p.status) }}</span><div v-if="p.utr" class="mk-muted">{{ p.utr }}</div></td>
            <td data-label=""><button v-if="p.status === 'scheduled'" class="ghost" :disabled="busy" @click="setStatus(p, 'processing')">Start processing</button><button v-else-if="p.status === 'processing'" class="ghost" :disabled="busy" @click="payOkId = p.id; utr = ''; utrErr = ''">Mark paid</button></td>
          </tr>
          <tr v-if="!d.payouts.length"><td colspan="6" class="mk-muted">No payouts yet.</td></tr>
        </tbody>
      </table>
      </div>
    </div>
  </template>
</AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { adminVendorPayouts, adminMarketplace } from '@/services/adminMarketplaceService'
import { useToastStore } from '@/stores/toast'
import { apiError } from '@/services/api'
import { inrp } from '@/utils/format'
import { payoutPill, label, initial } from './marketUi'

const toast = useToastStore()
const d = ref(null); const loading = ref(false); const busy = ref(false); const error = ref(''); const payErr = ref('')
const sf = reactive({ default_commission: '', min_payout: '', auto_approve_products: false }); const sErr = reactive({})
const payOkId = ref(null); const utr = ref(''); const utrErr = ref('')
const payOk = computed(() => d.value.payouts.find((p) => p.id === payOkId.value) || {})
const cards = computed(() => { const s = d.value.summary; return [
  { l: 'Owed to vendors', v: s.payable_paise, n: 'Delivered, not yet paid out' },
  { l: 'Payouts in progress', v: s.in_progress_paise, n: 'Scheduled or processing' },
  { l: 'Paid to vendors', v: s.paid_paise, n: 'All time' },
  { l: 'Kokango commission', v: s.commission_paise, n: 'Earned on vendor sales' },
] })
async function load() {
  loading.value = true; error.value = ''
  try { d.value = await adminVendorPayouts.list(); sf.default_commission = d.value.settings.default_commission; sf.min_payout = Math.round(d.value.settings.min_payout_paise / 100); sf.auto_approve_products = d.value.settings.auto_approve_products }
  catch (e) { error.value = apiError(e, 'Could not load payouts.').message } finally { loading.value = false }
}
async function create(v) { busy.value = true; payErr.value = ''; try { await adminVendorPayouts.create(v.id); toast.show('Payout created'); await load() } catch (e) { payErr.value = apiError(e, 'Could not create the payout.').message } finally { busy.value = false } }
async function setStatus(p, st, u = '') {
  busy.value = true; utrErr.value = ''; error.value = ''
  try { await adminVendorPayouts.setStatus(p.id, st, u); payOkId.value = null; toast.show(st === 'paid' ? 'Marked as paid' : 'Payout is processing'); await load() }
  catch (e) { const r = apiError(e, 'Could not update the payout.'); if (r.errors.utr) utrErr.value = r.message; else error.value = r.message } finally { busy.value = false }
}
async function saveSettings() {
  Object.keys(sErr).forEach((k) => delete sErr[k]); busy.value = true
  try { await adminMarketplace.saveSettings({ default_commission: sf.default_commission, min_payout_paise: Number(sf.min_payout) * 100, auto_approve_products: sf.auto_approve_products }); toast.show('Settings saved'); await load() }
  catch (e) { const r = apiError(e, 'Could not save settings.'); Object.assign(sErr, r.errors); if (!Object.keys(r.errors).length) error.value = r.message } finally { busy.value = false }
}
onMounted(load)
</script>

<style scoped>
.reason{margin-bottom:16px;padding:14px;background:var(--bg);border:1px solid var(--line);border-radius:var(--r-md)}
a.mk-who{color:inherit;text-decoration:none}
</style>
