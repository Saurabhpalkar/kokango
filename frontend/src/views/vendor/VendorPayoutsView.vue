<template>
<VendorLayout active="payouts">
  <div class="hd">
    <div><h1>Earnings &amp; payouts</h1><div class="sub">Commission on each sale is {{ data?.summary.commission_percent ?? '—' }}%</div></div>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div v-if="loading && !data" class="state">Loading payouts&hellip;</div>
  <template v-if="data">
    <div class="statgrid three">
      <div v-for="s in cards" :key="s.label" class="card stat">
        <div class="slabel">{{ s.label }}</div>
        <div class="sval">{{ inrp(s.value) }}</div>
        <div class="snote">{{ s.note }}</div>
      </div>
    </div>
    <div class="card" style="padding:16px 26px;box-sizing:border-box;margin-top:24px">
      <h2 class="ph">Payout history</h2>
      <div style="overflow-x:auto">
        <table class="rt" style="width:100%;border-collapse:collapse;min-width:560px">
          <thead><tr><th>Reference</th><th>Period</th><th>Amount</th><th>Status</th><th>Paid on</th></tr></thead>
          <tbody>
            <tr v-for="p in data.payouts" :key="p.id">
              <td data-label="Reference"><b>{{ p.ref }}</b></td>
              <td data-label="Period">{{ p.period }}</td>
              <td data-label="Amount"><b>{{ inrp(p.amount_paise) }}</b></td>
              <td data-label="Status"><span class="badge" :style="payoutPill(p.status)">{{ label(p.status) }}</span></td>
              <td data-label="Paid on">{{ p.paid_at ? formatDate(p.paid_at) : '—' }}</td>
            </tr>
            <tr v-if="!data.payouts.length"><td colspan="5" style="color:var(--muted)">No payouts yet. They appear here once you have delivered orders.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <p class="note">Payouts go to the bank account saved in <RouterLink to="/vendor/store">Store settings</RouterLink>.</p>
  </template>
</VendorLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import VendorLayout from '@/layouts/VendorLayout.vue'
import { vendorPayouts } from '@/services/vendorService'
import { apiError } from '@/services/api'
import { inrp, formatDate } from '@/utils/format'
import { payoutPill, label } from './vendorUi'

const data = ref(null); const loading = ref(false); const error = ref('')
const cards = computed(() => data.value ? [
  { label: 'Available', value: data.value.summary.available_paise, note: 'Ready for the next payout' },
  { label: 'Processing', value: data.value.summary.processing_paise, note: 'On its way to your bank' },
  { label: 'Paid so far', value: data.value.summary.paid_paise, note: 'Received in your account' },
] : [])
async function load() {
  loading.value = true; error.value = ''
  try { data.value = await vendorPayouts() }
  catch (e) { error.value = apiError(e, 'Could not load payouts.').message } finally { loading.value = false }
}
onMounted(load)
</script>

<style scoped>
.stat{padding:20px;box-sizing:border-box;display:flex;flex-direction:column;gap:4px}
.slabel{font-size:13px;font-weight:600;color:var(--muted)}
.sval{font-size:28px;font-weight:800;letter-spacing:-.6px}
.snote{font-size:12px;color:var(--muted)}
.three{grid-template-columns:repeat(3,minmax(0,1fr))!important}
@media (max-width:700px){.three{grid-template-columns:1fr!important}}
.ph{margin:6px 0 14px;font-size:18px;font-weight:800}
.note{margin-top:18px;font-size:13px;color:var(--muted)}
.note a{color:var(--forest);font-weight:700}
</style>
