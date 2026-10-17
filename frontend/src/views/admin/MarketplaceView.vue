<template>
<AdminLayout active="marketplace">
  <div class="hd">
    <div><h1>Marketplace</h1><div class="sub">Vendors, listings and money across the whole marketplace</div></div>
    <RouterLink to="/admin/vendors/new" class="btn">+ Add vendor</RouterLink>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div v-if="loading && !d" class="state">Loading marketplace&hellip;</div>
  <template v-if="d">
    <div class="statgrid">
      <div v-for="s in cards" :key="s.l" class="card mk-stat">
        <span class="mk-sico" :style="`background:${s.tone};color:${s.fg}`" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" v-html="s.i"></svg></span>
        <div class="mk-sl">{{ s.l }}</div><div class="mk-sv">{{ s.v }}</div><div class="mk-sn">{{ s.n }}</div>
      </div>
    </div>

    <div class="mk-row">
      <div class="card mk-pad g2">
        <div class="mk-ch"><h2>Marketplace sales, last 14 days</h2><span class="mk-muted">{{ inrp(total) }} total</span></div>
        <div class="mk-bars" role="img" :aria-label="`Sales for the last 14 days, ${inrp(total)} in total`">
          <div v-for="x in d.sales_by_day" :key="x.label" class="mk-bar"><span class="mk-bf" :style="`height:${Math.max(3, x.paise / max * 100)}%`" :title="`${x.label}: ${inrp(x.paise)}`"></span><span class="mk-bl">{{ x.label.split(' ')[0] }}</span></div>
        </div>
      </div>
      <div class="card mk-pad g1">
        <div class="mk-ch"><h2>Needs your attention</h2></div>
        <RouterLink to="/admin/vendors?status=pending" class="mk-li att"><div class="mk-grow">New vendor applications<small>Review and approve or reject</small></div><span class="badge" :style="vendorPill('pending')">{{ d.stats.pending_vendors }}</span></RouterLink>
        <RouterLink to="/admin/approvals" class="mk-li att"><div class="mk-grow">Products waiting for review<small>Vendors cannot sell these until approved</small></div><span class="badge" :style="vendorPill('pending')">{{ d.stats.pending_products }}</span></RouterLink>
        <RouterLink to="/admin/vendor-payouts" class="mk-li att"><div class="mk-grow">Vendor balances to pay out<small>Delivered orders not yet paid</small></div><span class="badge" style="background:var(--tint);color:var(--forest)">{{ inrp(d.stats.payable_paise) }}</span></RouterLink>
      </div>
    </div>

    <div class="mk-row">
      <div class="card mk-pad g2">
        <div class="mk-ch"><h2>Top vendors</h2><RouterLink to="/admin/vendors" class="mk-more">All vendors</RouterLink></div>
        <div style="overflow-x:auto">
        <table class="rt" style="width:100%;border-collapse:collapse;min-width:520px">
          <thead><tr><th>Vendor</th><th>Orders</th><th>Sales</th><th>Commission</th></tr></thead>
          <tbody>
            <tr v-for="v in d.top_vendors" :key="v.id">
              <td data-label="Vendor"><RouterLink :to="'/admin/vendors/' + v.id" class="mk-who"><span class="mk-av">{{ initial(v.store_name) }}</span><span><b>{{ v.store_name }}</b><small>{{ v.city }}</small></span></RouterLink></td>
              <td data-label="Orders">{{ v.orders }}</td><td data-label="Sales"><b>{{ inrp(v.sales_paise) }}</b></td><td data-label="Commission">{{ inrp(v.commission_paise) }} <span class="mk-muted">({{ v.commission_percent }}%)</span></td>
            </tr>
            <tr v-if="!d.top_vendors.length"><td colspan="4" class="mk-muted">No sales yet.</td></tr>
          </tbody>
        </table>
        </div>
      </div>
      <div class="card mk-pad g1">
        <div class="mk-ch"><h2>Awaiting product review</h2><RouterLink to="/admin/approvals" class="mk-more">Open queue</RouterLink></div>
        <div v-for="p in d.pending_products.slice(0, 5)" :key="p.id" class="mk-li"><div class="mk-th"><img v-if="p.image_url" :src="p.image_url" alt=""></div><div class="mk-grow">{{ p.name }}<small>{{ p.vendor_name }}</small></div></div>
        <div v-if="!d.pending_products.length" class="mk-muted">Nothing waiting.</div>
      </div>
    </div>
  </template>
</AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { adminMarketplace } from '@/services/adminMarketplaceService'
import { apiError } from '@/services/api'
import { inrp } from '@/utils/format'
import { vendorPill, initial } from './marketUi'

const d = ref(null); const loading = ref(false); const error = ref('')
const I = {
  store: '<path d="M3 10l1.500-5h15L21 10M5 10v9h14v-9M9 19v-5h6v5"/>', rupee: '<path d="M7 5h10M7 9h10M7 5c5 0 7 2 7 4s-2 4-7 4l7 6"/>',
  pct: '<path d="M19 5L5 19"/><circle cx="7" cy="7" r="2.200"/><circle cx="17" cy="17" r="2.200"/>', box: '<path d="M4 8l8-4 8 4v8l-8 4-8-4z"/><path d="M4 8l8 4 8-4M12 12v8"/>',
}
const cards = computed(() => { const s = d.value.stats; const g = { tone: 'var(--tint)', fg: 'var(--forest)' }; const y = { tone: 'var(--sun)', fg: 'var(--amber)' }; return [
  { l: 'Vendors', v: s.vendors, n: `${s.approved_vendors} approved · ${s.pending_vendors} pending`, i: I.store, ...g },
  { l: 'Marketplace sales', v: inrp(s.gmv_paise), n: `${s.orders} vendor orders`, i: I.rupee, ...g },
  { l: 'Commission earned', v: inrp(s.commission_paise), n: 'Kokango revenue', i: I.pct, ...y },
  { l: 'Listings in review', v: s.pending_products, n: 'Waiting for approval', i: I.box, ...y },
] })
const total = computed(() => d.value.sales_by_day.reduce((a, x) => a + x.paise, 0))
const max = computed(() => Math.max(1, ...d.value.sales_by_day.map((x) => x.paise)))
async function load() { loading.value = true; error.value = ''; try { d.value = await adminMarketplace.overview() } catch (e) { error.value = apiError(e, 'Could not load the marketplace overview.').message } finally { loading.value = false } }
onMounted(load)
</script>

<style scoped>
.att{color:inherit;text-decoration:none}.att:hover .mk-grow{color:var(--forest)}
</style>
