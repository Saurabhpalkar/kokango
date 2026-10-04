<template>
<div class="dcroot">
<div style="width:100%;position:relative;overflow:hidden;background:var(--bg)">
  <AnnouncementBar />
  <div style="position:absolute;top:-220px;right:-160px;width:520px;height:520px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;top:640px;left:-260px;width:480px;height:480px;border-radius:50%;background:var(--tint)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader active="products" search />

    <div class="ph" style="padding:32px 0 8px"><h1 style="margin:0;font-size:clamp(32px,8vw,48px);font-weight:800;letter-spacing:-1.5px">All Products</h1><p style="margin:10px 0 0;font-size:16px;color:var(--muted)">Konkan snacks and herbal powders, packed fresh.</p></div>

    <section class="plist" style="display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start;margin-top:32px">
      <aside class="card pside" style="flex:0 0 260px;border-radius:32px;padding:28px;box-sizing:border-box">
        <div style="font-size:15px;font-weight:800;margin-bottom:14px">Category</div>
        <div style="display:flex;flex-direction:column;gap:14px;font-size:14px;font-weight:500">
          <template v-for="c in catOptions" :key="c.slug">
            <label style="display:flex;gap:10px;align-items:center"><input type="checkbox" :checked="category === c.slug" @change="setCategory(c.slug)" style="accent-color:#0B5D1E;width:18px;height:18px">{{ c.label }}</label>
          </template>
        </div>
        <div style="font-size:15px;font-weight:800;margin:28px 0 14px">Price</div>
        <input type="range" :min="PRICE_MIN" :max="PRICE_MAX" step="10" v-model.number="maxPrice" aria-label="Maximum price" style="width:100%;accent-color:#0B5D1E">
        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);font-weight:600"><span>&#8377; 100</span><span>&#8377; 400</span></div>
      </aside>

      <div class="pmain" style="flex:1 1 640px;min-width:0">
        <div class="ptools" style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px">
          <div style="display:flex;gap:10px;flex-wrap:wrap"><template v-for="c in chipOptions" :key="c.slug"><button type="button" class="chip" :class="{ on: category === c.slug }" @click="setCategory(c.slug, true)">{{ c.label }}</button></template></div>
          <div class="pfilters" style="display:flex;flex-wrap:wrap;align-items:center;gap:16px">
            <label class="slabel" style="display:flex;align-items:center"><span style="position:absolute;left:-9999px">Search products</span><input ref="searchEl" v-model="query" type="search" placeholder="Search products" class="searchbox"></label>
            <label style="display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600;color:var(--muted)">Sort by<select v-model="sort"><option value="newest">Newest</option><option value="asc">Price: low to high</option><option value="desc">Price: high to low</option></select></label>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(min(250px,100%),1fr));gap:24px">
          <div v-if="loading && !visible.length" class="card" style="grid-column:1/-1;border-radius:32px;padding:48px 24px;text-align:center;box-sizing:border-box;color:var(--muted)">Loading products...</div>
          <template v-for="p in visible" :key="p.id">
            <div class="card" style="border-radius:32px;padding:16px;box-sizing:border-box">
              <a :href="`/product/${p.slug}`" class="pimg" style="display:flex;justify-content:center;height:230px;border-radius:24px;background:linear-gradient(160deg,#fff,var(--tint));overflow:hidden"><img :src="p.image_url" :alt="`${p.name} pack`" style="height:100%;width:auto;object-fit:contain"></a>
              <h3 style="margin:16px 6px 2px;font-size:18px;font-weight:700">{{ p.name }}</h3>
              <div style="margin:0 6px;font-size:13px;color:var(--muted)">{{ p.hindi_name }} &middot; {{ sizeText(p) }}</div>
              <div style="display:flex;justify-content:space-between;align-items:center;margin:14px 6px 4px"><span style="font-size:20px;font-weight:800;color:var(--green)">{{ inrp(p.min_price_paise) }}</span><button type="button" class="icon" @click.prevent="addToCart(p)" :aria-label="`Add ${p.name} to cart`" style="background:var(--mango);border-color:var(--mango)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8V6a3 3 0 016 0v2"/></svg></button></div>
            </div>
          </template>
        </div>
        <div v-if="error" class="card" style="border-radius:32px;padding:48px 24px;text-align:center;box-sizing:border-box"><div style="font-size:18px;font-weight:700">We could not load the products</div><p role="alert" style="margin:10px 0 0;font-size:14px;color:var(--muted)">{{ error }}</p><button type="button" class="btn" @click="fetchPage(1)" style="margin-top:18px">Try again</button></div>
        <div v-else-if="!loading && !visible.length" class="card" style="border-radius:32px;padding:48px 24px;text-align:center;box-sizing:border-box"><div style="font-size:18px;font-weight:700">No products match your filters</div><button type="button" class="btn" @click="clearFilters" style="margin-top:18px">Clear filters</button></div>
        <div v-if="hasMore" style="text-align:center;margin-top:28px"><button type="button" class="btn" :disabled="loading" @click="fetchPage(page + 1)">{{ loading ? 'Loading...' : 'Load more' }}</button></div>
      </div>
    </section>

    <SiteFooter />
  </div>
</div>
<WhatsAppFab />
</div>
</template>

<script setup>
import AnnouncementBar from '@/components/AnnouncementBar.vue'
import SiteHeader from '@/components/SiteHeader.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import WhatsAppFab from '@/components/WhatsAppFab.vue'
import { useCartStore } from '@/stores/cart'

const cart = useCartStore()
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useToastStore } from '@/stores/toast'
import { inrp } from '@/utils/format'
import { apiError } from '@/services/api'
import * as catalogue from '@/services/catalogueService'

const route = useRoute()
const toast = useToastStore()
const PRICE_MIN = 100
const PRICE_MAX = 400
const PER_PAGE = 24

const cats = ref([])
const catOptions = computed(() => [{ slug: '', label: 'All products' }, ...cats.value.map((c) => ({ slug: c.slug, label: c.name }))])
const chipOptions = computed(() => [{ slug: '', label: 'All' }, ...cats.value.map((c) => ({ slug: c.slug, label: c.name }))])

const fromRoute = () => (typeof route.query.category === 'string' ? route.query.category : '')
const category = ref(fromRoute())
const maxPrice = ref(PRICE_MAX)
const sort = ref('newest')
const query = ref('')
const searchEl = ref(null)

const setCategory = (slug, fromChip) => { category.value = (!fromChip && slug && category.value === slug) ? '' : slug }
watch(() => route.query.category, () => { category.value = fromRoute() })
const focusSearch = async () => { if (route.query.focus === 'search') { await nextTick(); searchEl.value && searchEl.value.focus() } }
watch(() => route.query.focus, focusSearch)

// ---- server-side filtering, sorting and search
const visible = ref([])
const page = ref(1)
const lastPage = ref(1)
const loading = ref(true)
const error = ref('')
const hasMore = computed(() => page.value < lastPage.value)
let seq = 0
async function fetchPage(n) {
  const my = ++seq
  loading.value = true; error.value = ''
  const params = { page: n, per_page: PER_PAGE, sort: { newest: 'newest', asc: 'price_asc', desc: 'price_desc' }[sort.value] }
  if (category.value) params.category = category.value
  if (query.value.trim()) params.search = query.value.trim()
  if (maxPrice.value < PRICE_MAX) params.max_price = maxPrice.value * 100
  try {
    const { items, meta } = await catalogue.products(params)
    if (my !== seq) return
    visible.value = n === 1 ? items : [...visible.value, ...items]
    page.value = meta.current_page || n
    lastPage.value = meta.last_page || 1
  } catch (e) {
    if (my !== seq) return
    error.value = apiError(e).message
    if (n === 1) visible.value = []
  } finally { if (my === seq) loading.value = false }
}
let timer = null
watch([category, maxPrice, sort, query], () => { clearTimeout(timer); timer = setTimeout(() => fetchPage(1), 250) })
onBeforeUnmount(() => clearTimeout(timer))
onMounted(async () => {
  focusSearch()
  catalogue.categories().then((c) => { cats.value = c }).catch(() => {})
  fetchPage(1)
})

const clearFilters = () => { category.value = ''; maxPrice.value = PRICE_MAX; sort.value = 'newest'; query.value = '' }
const sizeText = (p) => {
  const v = catalogue.defaultVariant(p)
  return v ? v.size_label : ''
}
const addToCart = async (p) => {
  const v = catalogue.defaultVariant(p)
  if (!v || !v.in_stock) { toast.show(`${p.name} is out of stock`); return }
  if (await cart.add(v.id, 1)) toast.show(`${p.name} added to cart`)
}
</script>

<style scoped>
.dcroot{--bg:#F8F7EF;--surface:#fff;--ink:#14301C;--muted:#566659;--line:#E6E2D0;--green:#0B5D1E;--leaf:#9ACD32;--mango:#FFB400;--amber:#9A5B00;--tint:#EAF3DD;--sun:#FFF1CC}
.dcroot{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans','Noto Sans Devanagari',system-ui,sans-serif}
a{color:var(--ink);text-decoration:none}a:hover{color:var(--green)}
button{font-family:inherit;cursor:pointer}
.card{background:var(--surface);border:1px solid var(--line);box-shadow:0 10px 30px rgba(20,48,28,0.06)}
.icon{width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;background:#fff;color:var(--ink);border:1px solid var(--line);border-radius:50%;padding:0;position:relative}
.icon:hover{border-color:var(--green);color:var(--green)}
.btn{background:var(--green);color:#fff;border:none;border-radius:999px;padding:12px 28px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:8px}
.btn:hover{background:#08481a;color:#fff}
.chip{background:#fff;color:var(--ink);border:1.5px solid var(--line);border-radius:999px;padding:8px 18px;font-size:13px;font-weight:600;min-height:40px}
.chip.on{border-color:var(--green);color:var(--green);background:var(--tint)}
.searchbox{height:44px;width:200px;box-sizing:border-box;background:#fff;border:1px solid var(--line);border-radius:999px;color:var(--ink);padding:0 18px;font-family:inherit;font-size:14px;font-weight:500}
.btn:disabled{opacity:.7;cursor:progress}
select{height:44px;background:#fff;border:1px solid var(--line);border-radius:999px;color:var(--ink);padding:0 16px;font-family:inherit;font-size:14px;font-weight:600}

/* ---- responsive ---- */
img{max-width:100%}
@media (max-width:600px){.wrap{padding-left:16px!important;padding-right:16px!important}}

.pmain,.pside{min-width:0}
@media (max-width:900px){.pside{flex:1 1 100%!important}}
@media (max-width:600px){
  .ph{padding-top:20px!important}
  .plist{gap:20px!important;margin-top:20px!important}
  .pside{padding:20px!important;border-radius:28px!important}
  .pfilters,.slabel{width:100%}
  .pfilters{gap:12px!important}
  .searchbox{width:100%!important}
  .pfilters>label:last-child{justify-content:space-between;width:100%}
  .chip{min-height:40px}
}
</style>
