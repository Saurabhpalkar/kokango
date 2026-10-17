<template>
<div class="dcroot">
  <AnnouncementBar />
  <SiteHeader active="products" search />

  <main>
    <!-- compact page title -->
    <section class="phead wrap">
      <nav class="crumbs" aria-label="Breadcrumb">
        <a href="/">Home</a><span aria-hidden="true">/</span>
        <template v-if="activeCategory"><a href="/products">Shop</a><span aria-hidden="true">/</span><span aria-current="page">{{ activeCategory.name }}</span></template>
        <span v-else aria-current="page">Shop</span>
      </nav>
      <h1>{{ heading }}</h1>
    </section>

    <div class="wrap shop">
      <!-- filters: sidebar on desktop, slide-in drawer on phones -->
      <aside id="shop-filters" class="side card" :class="{ open: drawer }" aria-label="Filters">
        <div class="side-head">
          <h2>Filters</h2>
          <button type="button" class="icon x" aria-label="Close filters" @click="drawer = false"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
        </div>
        <div class="grp msort">
          <label class="lg" for="msort">Sort by</label>
          <select id="msort" v-model="sort" class="sel"><option value="newest">Newest</option><option value="asc">Price: low to high</option><option value="desc">Price: high to low</option></select>
        </div>
        <fieldset class="grp">
          <legend>Category</legend>
          <label v-for="c in catOptions" :key="c.slug" class="chk"><input type="checkbox" :checked="category === c.slug" @change="setCategory(c.slug)"><span>{{ c.label }}</span></label>
        </fieldset>
        <div class="grp">
          <label class="lg" for="maxprice">Maximum price: <strong>{{ maxPrice >= PRICE_MAX ? 'Any' : inrp(maxPrice * 100) }}</strong></label>
          <input id="maxprice" type="range" :min="PRICE_MIN" :max="PRICE_MAX" step="10" v-model.number="maxPrice">
          <div class="range-l"><span>&#8377; {{ PRICE_MIN }}</span><span>&#8377; {{ PRICE_MAX }}</span></div>
        </div>
        <div class="side-foot">
          <button type="button" class="btn btn-line btn-sm" :disabled="!filtersActive" @click="clearFilters">Clear filters</button>
          <button type="button" class="btn btn-sm apply" @click="drawer = false">Show {{ total }} {{ total === 1 ? 'product' : 'products' }}</button>
        </div>
      </aside>
      <button v-if="drawer" type="button" class="scrim" aria-label="Close filters" @click="drawer = false"></button>

      <section class="main" aria-label="Products">
        <div class="bar">
          <div class="tabs" role="group" aria-label="Quick category filter">
            <button v-for="c in chipOptions" :key="c.slug" type="button" class="chip" :class="{ on: category === c.slug }" :aria-pressed="category === c.slug ? 'true' : 'false'" @click="setCategory(c.slug, true)">{{ c.label }}</button>
          </div>

          <div class="tools">
            <label class="sbox"><span class="sr-only">Search products</span>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg>
              <input ref="searchEl" v-model="query" type="search" placeholder="Search products" autocomplete="off">
            </label>
            <button type="button" class="fbtn" aria-label="Filter and sort" aria-controls="shop-filters" :aria-expanded="drawer ? 'true' : 'false'" @click="drawer = true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg><span>Filter &amp; sort</span><span v-if="filtersActive || sort !== 'newest'" class="dotn" aria-label="filters applied"></span>
            </button>
            <label class="sortl">Sort by
              <select v-model="sort" class="sel"><option value="newest">Newest</option><option value="asc">Price: low to high</option><option value="desc">Price: high to low</option></select>
            </label>
          </div>
        </div>

        <p class="count" role="status" aria-live="polite">
          <template v-if="loading && !visible.length">Loading products...</template>
          <template v-else-if="!error">Showing {{ visible.length }} of {{ total }} {{ total === 1 ? 'product' : 'products' }}<template v-if="query.trim()"> for &ldquo;{{ query.trim() }}&rdquo;</template></template>
        </p>

        <div v-if="loading && !visible.length" class="pgrid" aria-hidden="true">
          <div v-for="n in 8" :key="n" class="card sk-card"><div class="skeleton sk-img"></div><div class="skeleton sk-line w70"></div><div class="skeleton sk-line w40"></div></div>
        </div>
        <div v-else-if="error" class="card state"><div class="state-t">We could not load the products</div><p role="alert">{{ error }}</p><button type="button" class="btn" @click="fetchPage(1)">Try again</button></div>
        <div v-else-if="!visible.length" class="card state"><div class="state-t">No products match your filters</div><p>Try a different search or clear the filters.</p><button type="button" class="btn" @click="clearFilters">Clear filters</button></div>
        <div v-else class="pgrid"><ProductCard v-for="(p, k) in visible" :key="p.id" v-reveal="{ delay: (k % 4) * 70 }" :product="p" /></div>

        <div v-if="hasMore && visible.length" class="more"><button type="button" class="btn btn-line" :disabled="loading" :aria-busy="loading ? 'true' : 'false'" @click="fetchPage(page + 1)">{{ loading ? 'Loading...' : 'Load more products' }}</button></div>
      </section>
    </div>
  </main>

  <SiteFooter />
  <WhatsAppFab />
</div>
</template>

<script setup>
import AnnouncementBar from '@/components/AnnouncementBar.vue'
import SiteHeader from '@/components/SiteHeader.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import WhatsAppFab from '@/components/WhatsAppFab.vue'
import ProductCard from '@/components/ProductCard.vue'
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { inrp } from '@/utils/format'
import { apiError } from '@/services/api'
import * as catalogue from '@/services/catalogueService'

const route = useRoute()
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
const query = ref(typeof route.query.search === 'string' ? route.query.search : '')
const searchEl = ref(null)
const drawer = ref(false)

const activeCategory = computed(() => cats.value.find((c) => c.slug === category.value) || null)
const heading = computed(() => (activeCategory.value ? activeCategory.value.name : 'All Products'))
const filtersActive = computed(() => !!category.value || maxPrice.value < PRICE_MAX || !!query.value.trim())

const setCategory = (slug, fromChip) => { category.value = (!fromChip && slug && category.value === slug) ? '' : slug }
watch(() => route.query.category, () => { category.value = fromRoute() })
watch(() => route.query.search, (v) => { query.value = typeof v === 'string' ? v : '' })
const focusSearch = async () => { if (route.query.focus === 'search') { await nextTick(); searchEl.value && searchEl.value.focus() } }
watch(() => route.query.focus, focusSearch)

// lock the page behind the filter drawer, release it on close or leave
watch(drawer, (open) => { document.body.style.overflow = open ? 'hidden' : '' })
const onKey = (e) => { if (e.key === 'Escape') drawer.value = false }

// ---- server-side filtering, sorting and search (same API parameters as before)
const visible = ref([])
const total = ref(0)
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
    total.value = meta.total ?? visible.value.length
    page.value = meta.current_page || n
    lastPage.value = meta.last_page || 1
  } catch (e) {
    if (my !== seq) return
    error.value = apiError(e).message
    if (n === 1) { visible.value = []; total.value = 0 }
  } finally { if (my === seq) loading.value = false }
}
let timer = null
watch([category, maxPrice, sort, query], () => { clearTimeout(timer); timer = setTimeout(() => fetchPage(1), 250) })
onBeforeUnmount(() => { clearTimeout(timer); document.body.style.overflow = ''; document.removeEventListener('keydown', onKey) })
onMounted(async () => {
  document.addEventListener('keydown', onKey)
  focusSearch()
  catalogue.categories().then((c) => { cats.value = c.filter((x) => x.products_count == null || x.products_count > 0) }).catch(() => {})
  fetchPage(1)
})

const clearFilters = () => { category.value = ''; maxPrice.value = PRICE_MAX; sort.value = 'newest'; query.value = '' }
</script>

<style scoped>
/* compact title */
.phead { padding-top: 22px; }
.crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 8px; font-size: 13px; color: var(--muted); }
.crumbs a { color: var(--muted); display: inline-flex; align-items: center; min-height: 28px; }
.crumbs a:hover { color: var(--forest); }
.crumbs [aria-current] { color: var(--ink); font-weight: 600; }
.phead h1 { margin: 4px 0 0; font-size: clamp(26px, 3.6vw, 38px); font-weight: 800; letter-spacing: -.8px; line-height: 1.15; }

/* layout */
.shop { display: grid; grid-template-columns: 250px minmax(0, 1fr); gap: 32px; align-items: start; padding-top: 20px; padding-bottom: 56px; }
.side { position: sticky; top: 130px; padding: 22px; display: flex; flex-direction: column; gap: 22px; }
.side-head { display: flex; align-items: center; justify-content: space-between; }
.side-head h2 { margin: 0; font-size: 18px; font-weight: 800; }
.x, .scrim, .apply, .msort { display: none; }
.grp { border: 0; margin: 0; padding: 0; min-width: 0; }
.grp legend, .lg { padding: 0; margin: 0 0 12px; display: block; font-size: 14px; font-weight: 800; }
.lg strong { color: var(--forest); }
.chk { display: flex; align-items: center; gap: 10px; min-height: 40px; font-size: 14px; font-weight: 500; cursor: pointer; }
.chk input { width: 18px; height: 18px; accent-color: var(--forest); flex: none; }
input[type="range"] { width: 100%; accent-color: var(--forest); }
.range-l { display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; color: var(--muted); }
.side-foot { display: flex; gap: 10px; }
.side-foot .btn { flex: 1; }

.main { min-width: 0; }
.bar { display: flex; flex-direction: column; gap: 12px; }
.tabs { display: flex; gap: 8px; overflow-x: auto; padding: 2px; margin: 0 -2px; scrollbar-width: none; }
.tabs::-webkit-scrollbar { display: none; }
.tabs .chip { flex: none; }
.tools { display: flex; align-items: center; gap: 12px; }
.sbox { flex: 1 1 auto; max-width: 380px; display: flex; align-items: center; gap: 10px; min-height: 44px; padding: 0 16px; background: #fff; border: 1px solid #CFCBB6; border-radius: 999px; color: var(--muted); transition: border-color .2s, box-shadow .2s; }
.sbox:focus-within { border-color: var(--forest); box-shadow: 0 0 0 3px rgba(55, 146, 55, .28); }
.sbox input { flex: 1; min-width: 0; border: 0; outline: none; background: transparent; font-size: 14px; font-weight: 500; color: var(--ink); height: 42px; }
.fbtn { display: none; position: relative; flex: none; align-items: center; gap: 8px; min-height: 44px; padding: 0 16px; border-radius: 999px; border: 1px solid var(--forest); background: var(--forest); color: #fff; font-size: 14px; font-weight: 700; }
.dotn { width: 9px; height: 9px; border-radius: 50%; background: var(--mango); position: absolute; top: 4px; right: 6px; border: 2px solid var(--forest); }
.sortl { margin-left: auto; display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; color: var(--muted); }
.count { margin: 16px 0 14px; font-size: 13px; color: var(--muted); min-height: 20px; }

.sk-card { background: var(--surface); border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; gap: 10px; padding-bottom: 16px; }
.sk-img { aspect-ratio: 1; border-radius: 0; }
.sk-line { height: 14px; margin: 0 14px; }
.w70 { width: 70%; } .w40 { width: 40%; }
.state { padding: 48px 24px; text-align: center; }
.state-t { font-size: 18px; font-weight: 800; }
.state p { margin: 10px 0 18px; font-size: 14px; color: var(--muted); }
.more { text-align: center; margin-top: 32px; }
.main .pgrid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
@media (min-width: 1200px) { .main .pgrid { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; } }

@media (max-width: 900px) {
  .shop { grid-template-columns: 1fr; padding-top: 10px; gap: 0; }
  /* search + filter stay in reach while scrolling */
  .bar { position: sticky; top: 52px; z-index: 20; margin: 0 calc(var(--gutter) * -1); padding: 10px var(--gutter); background: rgba(250, 248, 240, .96); backdrop-filter: blur(6px); border-bottom: 1px solid var(--line); gap: 10px; }
  .fbtn { display: inline-flex; }
  .sortl { display: none; }
  .msort { display: block; }
  .sbox { max-width: none; }
  /* filters open as a bottom sheet */
  .side {
    position: fixed; z-index: 90; left: 0; right: 0; bottom: 0; top: auto; max-height: 82vh; border-radius: 20px 20px 0 0; overflow-y: auto;
    transform: translateY(102%); transition: transform .3s var(--ease); visibility: hidden; box-shadow: none; border: 0;
  }
  .side.open { transform: none; visibility: visible; box-shadow: 0 -10px 40px rgba(10, 42, 30, .25); }
  .x, .apply { display: inline-flex; }
  .scrim { display: block; position: fixed; inset: 0; z-index: 80; border: 0; background: rgba(10, 42, 30, .5); }
  .side-foot { margin-top: auto; position: sticky; bottom: 0; padding: 12px 0 4px; background: var(--surface); }
  .main .pgrid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
}
@media (max-width: 640px) {
  .phead { padding-top: 14px; }
  .phead h1 { font-size: 24px; }
  .crumbs { font-size: 12px; }
  .tabs .chip { min-height: 36px; padding: 0 14px; }
  .fbtn span:not(.dotn) { display: none; }
  .fbtn { width: 44px; padding: 0; justify-content: center; }
  .count { margin: 12px 0 10px; }
  .main .pgrid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
}
</style>
