<template>
<AdminLayout active="vendors">
  <div class="hd">
    <div><h1>Vendors</h1><div class="sub">{{ counts.all || 0 }} sellers on the marketplace</div></div>
    <div class="mk-actions"><label class="sr-only" for="vq">Search vendors</label><input id="vq" v-model="q" class="s" placeholder="Search store, owner, city"><RouterLink to="/admin/vendors/new" class="btn">+ Add vendor</RouterLink></div>
  </div>
  <div class="chips" role="group" aria-label="Filter by status">
    <button v-for="c in chips" :key="c.v" type="button" class="chip" :class="{ on: status === c.v }" :aria-pressed="status === c.v" @click="setStatus(c.v)">{{ c.l }} <span class="n">{{ counts[c.v] ?? 0 }}</span></button>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div class="card" style="border-radius:18px;padding:16px 26px">
    <div style="overflow-x:auto">
    <table class="rt" style="width:100%;border-collapse:collapse;min-width:880px">
      <thead><tr><th>Vendor</th><th>City</th><th>Products</th><th>Sales</th><th>Commission</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <tr v-for="v in rows" :key="v.id">
          <td data-label="Vendor"><RouterLink :to="'/admin/vendors/' + v.id" class="mk-who"><span class="mk-av">{{ initial(v.store_name) }}</span><span><b>{{ v.store_name }}</b><small>{{ v.owner_name }} &middot; {{ v.email }}</small></span></RouterLink></td>
          <td data-label="City">{{ v.city || '—' }}</td>
          <td data-label="Products"><span>{{ v.live_products }} live<span v-if="v.review_products" class="mk-muted"> · {{ v.review_products }} in review</span></span></td>
          <td data-label="Sales"><b>{{ inrp(v.sales_paise) }}</b></td>
          <td data-label="Commission"><span>{{ v.commission_percent }}%<span v-if="v.commission_percent_override != null" class="mk-muted"> (custom)</span></span></td>
          <td data-label="Status"><span class="badge" :style="vendorPill(v.status)">{{ vendorStatusLabel[v.status] }}</span></td>
          <td data-label=""><RouterLink class="ghost" :to="'/admin/vendors/' + v.id">{{ v.status === 'pending' ? 'Review' : 'View' }}</RouterLink></td>
        </tr>
        <tr v-if="loading && !rows.length"><td colspan="7" class="state">Loading vendors&hellip;</td></tr>
        <tr v-else-if="!rows.length && !error"><td colspan="7" style="color:var(--muted)">No vendors match.</td></tr>
      </tbody>
    </table>
    </div>
    <AdminPager :meta="meta" @page="go" />
  </div>
</AdminLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AdminPager from './AdminPager.vue'
import { adminVendors } from '@/services/adminMarketplaceService'
import { apiError } from '@/services/api'
import { debounce } from '@/services/adminGuard'
import { inrp } from '@/utils/format'
import { vendorPill, vendorStatusLabel, initial } from './marketUi'

const route = useRoute()
const chips = [{ v: 'all', l: 'All' }, { v: 'pending', l: 'Pending' }, { v: 'approved', l: 'Approved' }, { v: 'suspended', l: 'Suspended' }, { v: 'rejected', l: 'Rejected' }]
const rows = ref([]); const meta = ref(null); const counts = ref({}); const q = ref(''); const page = ref(1); const loading = ref(false); const error = ref('')
const status = ref(chips.some((c) => c.v === route.query.status) ? route.query.status : 'all')
let seq = 0
async function load() {
  const my = ++seq; loading.value = true; error.value = ''
  try { const r = await adminVendors.list({ status: status.value, search: q.value.trim(), page: page.value }); if (my === seq) { rows.value = r.data; meta.value = r.meta; counts.value = r.counts } }
  catch (e) { if (my === seq) error.value = apiError(e, 'Could not load vendors.').message } finally { if (my === seq) loading.value = false }
}
const setStatus = (v) => { status.value = v; page.value = 1; load() }
watch(q, debounce(() => { page.value = 1; load() }))
const go = (p) => { page.value = p; load() }
onMounted(load)
</script>

<style scoped>
.n{display:inline-block;min-width:20px;margin-left:4px;padding:0 6px;border-radius:999px;background:rgba(16,61,44,.1);font-size:12px;font-weight:800}
.chip.on .n{background:rgba(255,255,255,.25)}
a.mk-who{color:inherit;text-decoration:none}a.mk-who:hover b{color:var(--forest)}
</style>
