<template>
<AdminLayout active="approvals">
  <div class="hd">
    <div><h1>Product approvals</h1><div class="sub">Vendor listings go live only after you approve them</div></div>
    <div><label class="sr-only" for="aq">Search listings</label><input id="aq" v-model="q" class="s" placeholder="Search product or vendor"></div>
  </div>
  <div class="chips" role="group" aria-label="Filter listings">
    <button v-for="c in chips" :key="c.v" type="button" class="chip" :class="{ on: status === c.v }" :aria-pressed="status === c.v" @click="setStatus(c.v)">{{ c.l }}<span v-if="c.n !== undefined" class="n">{{ c.n }}</span></button>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>

  <div v-if="rejecting" class="card mk-pad" style="margin-bottom:20px;border:1px solid #E8B7B3">
    <h2 class="mk-h2">Reject “{{ rejecting.name }}”</h2>
    <p class="mk-muted" style="margin:6px 0 0">{{ rejecting.vendor_name }} will see this note and can edit and resubmit.</p>
    <div class="mk-reason">
      <label class="field" style="margin:0">Reason<textarea v-model="reason" class="mk-ta" :class="{ bad: reasonErr }" rows="3" placeholder="e.g. Add the ingredient list and a clearer photo."></textarea><span v-if="reasonErr" class="err">{{ reasonErr }}</span></label>
      <div class="mk-actions"><button class="btn danger-btn" :disabled="busy" @click="act(rejecting, 'reject', reason)">Reject listing</button><button class="ghost" @click="rejecting = null">Cancel</button></div>
    </div>
  </div>

  <div class="card" style="border-radius:18px;padding:16px 26px">
    <div style="overflow-x:auto">
    <table class="rt" style="width:100%;border-collapse:collapse;min-width:820px">
      <thead><tr><th>Product</th><th>Vendor</th><th>Category</th><th>Price</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <tr v-for="p in rows" :key="p.id">
          <td data-label="Product"><div class="mk-who"><div class="mk-th"><img v-if="p.image_url" :src="p.image_url" alt=""></div><span><b>{{ p.name }}</b><small>{{ p.variants.length }} {{ p.variants.length === 1 ? 'size' : 'sizes' }}<template v-if="p.submitted_at"> · submitted {{ formatDate(p.submitted_at) }}</template></small></span></div></td>
          <td data-label="Vendor">{{ p.vendor_name }}<div v-if="p.vendor_status && p.vendor_status !== 'approved'" class="mk-muted">Store {{ p.vendor_status }}</div></td>
          <td data-label="Category">{{ p.category?.name }}</td>
          <td data-label="Price"><b>{{ inrp(p.min_price_paise) }}</b></td>
          <td data-label="Status"><span class="badge" :style="productPill(p.status)">{{ productStatusLabel[p.status] || p.status }}</span><div v-if="p.status === 'rejected' && p.review_note" class="mk-muted" style="margin-top:4px;max-width:220px">{{ p.review_note }}</div></td>
          <td data-label="">
            <div class="mk-actions">
              <template v-if="p.status === 'pending_review'"><button class="btn btn-sm" :disabled="busy" @click="act(p, 'approve')">Approve</button><button class="ghost danger" :disabled="busy" @click="startReject(p)">Reject</button></template>
              <button v-else-if="p.status === 'active'" class="ghost" :disabled="busy" @click="act(p, 'unpublish')">Unpublish</button>
              <button v-else-if="p.status === 'rejected' || p.status === 'paused'" class="ghost" :disabled="busy" @click="act(p, 'approve')">{{ p.status === 'paused' ? 'Republish' : 'Approve anyway' }}</button>
            </div>
          </td>
        </tr>
        <tr v-if="loading && !rows.length"><td colspan="6" class="state">Loading listings&hellip;</td></tr>
        <tr v-else-if="!rows.length && !error"><td colspan="6" style="color:var(--muted)">{{ status === 'pending_review' ? 'The queue is empty. New vendor submissions will appear here.' : 'No listings here.' }}</td></tr>
      </tbody>
    </table>
    </div>
    <AdminPager :meta="meta" @page="go" />
  </div>
</AdminLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AdminPager from './AdminPager.vue'
import { adminApprovals } from '@/services/adminMarketplaceService'
import { useToastStore } from '@/stores/toast'
import { apiError } from '@/services/api'
import { debounce } from '@/services/adminGuard'
import { inrp, formatDate } from '@/utils/format'
import { productPill, productStatusLabel } from './marketUi'

const toast = useToastStore()
const rows = ref([]); const meta = ref(null); const counts = ref({}); const q = ref(''); const page = ref(1); const status = ref('pending_review')
const loading = ref(false); const busy = ref(false); const error = ref(''); const rejecting = ref(null); const reason = ref(''); const reasonErr = ref('')
const chips = computed(() => [{ v: 'pending_review', l: 'Needs review', n: counts.value.pending_review ?? 0 }, { v: 'active', l: 'Live', n: counts.value.active ?? 0 }, { v: 'rejected', l: 'Rejected', n: counts.value.rejected ?? 0 }, { v: 'all', l: 'All' }])
let seq = 0
async function load() {
  const my = ++seq; loading.value = true; error.value = ''
  try { const r = await adminApprovals.list({ status: status.value, search: q.value.trim(), page: page.value }); if (my === seq) { rows.value = r.data; meta.value = r.meta; counts.value = r.counts } }
  catch (e) { if (my === seq) error.value = apiError(e, 'Could not load listings.').message } finally { if (my === seq) loading.value = false }
}
const setStatus = (v) => { status.value = v; page.value = 1; load() }
watch(q, debounce(() => { page.value = 1; load() }))
const go = (p) => { page.value = p; load() }
const startReject = (p) => { rejecting.value = p; reason.value = ''; reasonErr.value = '' }
async function act(p, action, why = '') {
  busy.value = true; error.value = ''; reasonErr.value = ''
  try { await adminApprovals.act(p.id, action, why); rejecting.value = null; toast.show({ approve: 'Listing approved and live', reject: 'Listing rejected', unpublish: 'Listing unpublished' }[action]); await load() }
  catch (e) { const r = apiError(e, 'Could not update the listing.'); if (r.errors.reason) reasonErr.value = r.message; else error.value = r.message } finally { busy.value = false }
}
onMounted(load)
</script>

<style scoped>
.n{display:inline-block;min-width:20px;margin-left:6px;padding:0 6px;border-radius:999px;background:rgba(16,61,44,.1);font-size:12px;font-weight:800}
.chip.on .n{background:rgba(255,255,255,.25)}
.danger-btn{background:#B3261E;border-color:#B3261E}
</style>
