<template>
<AdminLayout active="products">
  <div class="hd">
    <div><h1>Products</h1><div class="sub">{{ meta ? meta.total : 0 }} products<template v-if="meta"> &middot; {{ variantCount }} variants on this page</template></div></div>
    <div style="display:flex;gap:12px;flex-wrap:wrap"><label style="position:absolute;left:-9999px" for="q">Search products</label><input id="q" v-model="q" class="s" placeholder="Search products"><RouterLink to="/admin/products/new" class="btn" style="box-sizing:border-box">+ Add product</RouterLink></div>
  </div>

  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div v-if="notice" class="banner">{{ notice }}</div>

  <div class="card" style="border-radius:30px;padding:16px 26px;box-sizing:border-box">
    <div style="overflow-x:auto">
    <table class="rt" style="width:100%;border-collapse:collapse;min-width:760px">
      <thead><tr><th>PRODUCT</th><th>CATEGORY</th><th>VARIANTS</th><th>PRICE</th><th>STOCK</th><th>STATUS</th><th></th></tr></thead>
      <tbody>
        <tr v-for="r in rows" :key="r.id">
          <td data-label="Product"><div style="display:flex;align-items:center;gap:12px"><div style="flex:0 0 52px;height:52px;border-radius:12px;background:var(--tint);display:flex;align-items:center;justify-content:center;overflow:hidden"><img v-if="r.image_url" :src="r.image_url" alt="" style="height:100%;width:auto;object-fit:contain"></div><div style="font-weight:700">{{ r.name }}<div style="font-size:12px;color:var(--muted);font-weight:500">{{ r.variants?.[0]?.sku || '—' }}</div></div></div></td>
          <td data-label="Category">{{ r.category?.name }}</td>
          <td data-label="Variants">{{ r.variants?.length || 0 }}</td>
          <td data-label="Price" style="font-weight:700">{{ inrp(r.min_price_paise) }}</td>
          <td data-label="Stock" :style="`color:${stockColor(r.stock_total)};font-weight:800`">{{ r.stock_total }}</td>
          <td data-label="Status"><span class="badge" :style="r.is_active ? 'background:#EAF3DD;color:#0B5D1E' : 'background:#EDEDE4;color:#3b4a3f'">{{ r.is_active ? 'Active' : 'Inactive' }}</span></td>
          <td data-label=""><div style="display:flex;gap:8px"><template v-if="confirmId === r.id"><span style="font-size:13px;font-weight:600;color:#B3261E;align-self:center">Delete?</span><button class="ghost" style="border-color:#E8B7B3;color:#B3261E" :disabled="busy" @click="remove(r)">Yes</button><button class="ghost" @click="confirmId = null">No</button></template><template v-else><RouterLink class="ghost" :to="'/admin/products/' + r.id">Edit</RouterLink><button class="ghost" @click="confirmId = r.id">Delete</button></template></div></td>
        </tr>
        <tr v-if="loading && !rows.length"><td colspan="7" class="state">Loading products&hellip;</td></tr>
        <tr v-else-if="!rows.length && !error"><td colspan="7" style="color:var(--muted)">No products found.</td></tr>
      </tbody>
    </table>
    </div>
    <AdminPager :meta="meta" @page="go" />
  </div>
</AdminLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AdminPager from './AdminPager.vue'
import { apiError } from '@/services/api'
import { adminProducts } from '@/services/adminService'
import { debounce } from '@/services/adminGuard'
import { inrp } from '@/utils/format'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const rows = ref([])
const meta = ref(null)
const q = ref('')
const page = ref(1)
const loading = ref(false)
const error = ref('')
const notice = ref('')
const confirmId = ref(null)
const busy = ref(false)
let seq = 0

const variantCount = computed(() => rows.value.reduce((a, r) => a + (r.variants?.length || 0), 0))
const stockColor = (n) => (n < 10 ? '#B3261E' : 'var(--ink)')

async function load() {
  const my = ++seq
  loading.value = true; error.value = ''
  try {
    const res = await adminProducts.list({ search: q.value.trim(), page: page.value, per_page: 10 })
    if (my !== seq) return
    rows.value = res.data; meta.value = res.meta
    if (res.meta && page.value > res.meta.last_page && res.meta.last_page >= 1) { page.value = res.meta.last_page; return load() }
  } catch (e) { if (my === seq) error.value = apiError(e, 'Could not load products.').message } finally { if (my === seq) loading.value = false }
}
const reload = debounce(() => { page.value = 1; load() })
watch(q, reload)
function go(p) { page.value = p; load() }

async function remove(r) {
  busy.value = true; error.value = ''
  try {
    const res = await adminProducts.remove(r.id)
    confirmId.value = null
    const msg = res.message || 'Product removed.'
    if (res.data?.id) { const i = rows.value.findIndex((x) => x.id === r.id); if (i >= 0) rows.value[i] = res.data }
    else await load()
    notice.value = msg; toast.show(msg)
  } catch (e) { error.value = apiError(e, 'Could not delete the product.').message; confirmId.value = null } finally { busy.value = false }
}
onMounted(load)
</script>
