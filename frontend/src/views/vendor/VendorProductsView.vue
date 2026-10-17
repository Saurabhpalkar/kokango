<template>
<VendorLayout active="products">
  <div class="hd">
    <div><h1>My products</h1><div class="sub">{{ meta ? meta.total : 0 }} products</div></div>
    <div style="display:flex;gap:12px;flex-wrap:wrap"><label class="sr-only" for="q">Search products</label><input id="q" v-model="q" class="s" placeholder="Search products"><RouterLink to="/vendor/products/new" class="btn">+ Add product</RouterLink></div>
  </div>
  <div class="chips" role="group" aria-label="Filter by status">
    <button v-for="c in chips" :key="c.v" type="button" class="chip" :class="{ on: status === c.v }" :aria-pressed="status === c.v" @click="setStatus(c.v)">{{ c.l }}</button>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div v-if="notice" class="banner">{{ notice }}</div>

  <div class="card" style="padding:16px 26px;box-sizing:border-box">
    <div style="overflow-x:auto">
    <table class="rt" style="width:100%;border-collapse:collapse;min-width:720px">
      <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <tr v-for="r in rows" :key="r.id">
          <td data-label="Product"><div style="display:flex;align-items:center;gap:12px"><div class="thumb"><img v-if="r.image_url" :src="r.image_url" alt=""></div><div style="font-weight:700">{{ r.name }}<div class="small">{{ r.variants.length }} {{ r.variants.length === 1 ? 'variant' : 'variants' }}</div></div></div></td>
          <td data-label="Category">{{ r.category?.name }}</td>
          <td data-label="Price" style="font-weight:700">{{ inrp(r.min_price_paise) }}</td>
          <td data-label="Stock" :style="`color:${r.stock_total < 10 ? 'var(--danger)' : 'var(--ink)'};font-weight:800`">{{ r.stock_total }}</td>
          <td data-label="Status"><span class="badge" :style="productPill(r.status)">{{ productStatusLabel[r.status] || r.status }}</span><div v-if="r.status === 'rejected' && r.review_note" class="small" style="color:var(--danger);margin-top:4px;max-width:220px">{{ r.review_note }}</div></td>
          <td data-label=""><div style="display:flex;gap:8px"><template v-if="confirmId === r.id"><span style="font-size:13px;font-weight:600;color:var(--danger);align-self:center">Delete?</span><button class="ghost danger" :disabled="busy" @click="remove(r)">Yes</button><button class="ghost" @click="confirmId = null">No</button></template><template v-else><RouterLink class="ghost" :to="'/vendor/products/' + r.id">Edit</RouterLink><button class="ghost" @click="confirmId = r.id">Delete</button></template></div></td>
        </tr>
        <tr v-if="loading && !rows.length"><td colspan="6" class="state">Loading products&hellip;</td></tr>
        <tr v-else-if="!rows.length && !error"><td colspan="6" style="color:var(--muted)">No products here yet. Use "Add product" to create your first listing.</td></tr>
      </tbody>
    </table>
    </div>
    <AdminPager :meta="meta" @page="go" />
  </div>
</VendorLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import VendorLayout from '@/layouts/VendorLayout.vue'
import AdminPager from '@/views/admin/AdminPager.vue'
import { apiError } from '@/services/api'
import { vendorProducts } from '@/services/vendorService'
import { debounce } from '@/services/adminGuard'
import { inrp } from '@/utils/format'
import { useToastStore } from '@/stores/toast'
import { productPill, productStatusLabel } from './vendorUi'

const toast = useToastStore()
const rows = ref([]); const meta = ref(null); const q = ref(''); const status = ref('all'); const page = ref(1)
const loading = ref(false); const error = ref(''); const notice = ref(''); const confirmId = ref(null); const busy = ref(false)
const chips = [{ v: 'all', l: 'All' }, { v: 'active', l: 'Live' }, { v: 'pending_review', l: 'In review' }, { v: 'rejected', l: 'Rejected' }, { v: 'draft', l: 'Drafts' }]
let seq = 0
async function load() {
  const my = ++seq; loading.value = true; error.value = ''
  try { const r = await vendorProducts.list({ search: q.value, status: status.value, page: page.value }); if (my === seq) { rows.value = r.data; meta.value = r.meta } }
  catch (e) { if (my === seq) error.value = apiError(e, 'Could not load products.').message } finally { if (my === seq) loading.value = false }
}
const go = (p) => { page.value = p; load() }
const setStatus = (v) => { status.value = v; page.value = 1; load() }
const search = debounce(() => { page.value = 1; load() }, 300)
watch(q, search)
async function remove(r) {
  busy.value = true
  try { await vendorProducts.remove(r.id); confirmId.value = null; notice.value = `${r.name} was deleted.`; toast.show('Product deleted'); await load() }
  catch (e) { error.value = apiError(e, 'Could not delete the product.').message } finally { busy.value = false }
}
onMounted(load)
</script>

<style scoped>
.thumb{flex:0 0 52px;height:52px;border-radius:12px;background:var(--tint);display:flex;align-items:center;justify-content:center;overflow:hidden}
.thumb img{height:100%;width:auto;object-fit:contain}
.small{font-size:12px;color:var(--muted);font-weight:500}
</style>
