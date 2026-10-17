<template>
<AdminLayout active="reviews">
  <div class="hd">
    <div><h1>Reviews</h1><div class="sub">{{ counts.all ?? 0 }} reviews from customers who bought the product</div></div>
    <div><label class="sr-only" for="rq">Search reviews</label><input id="rq" v-model="q" class="s" placeholder="Search product or text"></div>
  </div>
  <div class="chips" role="group" aria-label="Filter reviews">
    <button v-for="c in chips" :key="c.v" type="button" class="chip" :class="{ on: status === c.v }" :aria-pressed="status === c.v" @click="setStatus(c.v)">{{ c.l }}<span v-if="counts[c.v] != null" class="cn">{{ counts[c.v] }}</span></button>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>

  <div class="list">
    <article v-for="r in rows" :key="r.id" class="card rv" :class="{ hid: r.status === 'hidden' }">
      <div class="rv-top">
        <span class="stars" role="img" :aria-label="r.rating + ' out of 5 stars'"><span v-for="n in 5" :key="n" :class="{ off: n > r.rating }" aria-hidden="true">&#9733;</span></span>
        <span class="badge" :style="r.status === 'published' ? 'background:#E8F3E4;color:#103D2C' : 'background:#EDEDE4;color:#3b4a3f'">{{ r.status === 'published' ? 'Shown' : 'Hidden' }}</span>
      </div>
      <h3 v-if="r.title">{{ r.title }}</h3>
      <p>{{ r.body }}</p>
      <div class="meta">
        <span><b>{{ r.customer?.name || 'Customer' }}</b> · {{ r.customer?.email }}</span>
        <span>on <a :href="`/product/${r.product?.slug}`" target="_blank" rel="noopener">{{ r.product?.name }}</a> · {{ formatDate(r.created_at) }}</span>
      </div>
      <div class="acts">
        <button class="ghost" :disabled="busy === r.id" @click="setReview(r, r.status === 'published' ? 'hidden' : 'published')">{{ r.status === 'published' ? 'Hide from shop' : 'Show in shop' }}</button>
        <template v-if="confirming === r.id"><button class="ghost danger" :disabled="busy === r.id" @click="remove(r)">Yes, delete</button><button class="ghost" @click="confirming = null">Keep</button></template>
        <button v-else class="ghost" @click="confirming = r.id">Delete</button>
      </div>
    </article>
    <div v-if="loading && !rows.length" class="card state">Loading reviews&hellip;</div>
    <div v-else-if="!rows.length && !error" class="card state">No reviews yet. Customers can review a product after ordering it.</div>
  </div>
  <AdminPager :meta="meta" @page="go" />
</AdminLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AdminPager from './AdminPager.vue'
import { apiError } from '@/services/api'
import { adminReviews } from '@/services/adminService'
import { debounce } from '@/services/adminGuard'
import { formatDate } from '@/utils/format'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const chips = [{ v: 'all', l: 'All' }, { v: 'published', l: 'Shown' }, { v: 'hidden', l: 'Hidden' }]
const rows = ref([]); const meta = ref(null); const counts = ref({}); const q = ref(''); const status = ref('all'); const page = ref(1)
const loading = ref(false); const error = ref(''); const busy = ref(null); const confirming = ref(null)

async function load() {
  loading.value = true; error.value = ''
  try { const r = await adminReviews.list({ status: status.value, search: q.value.trim(), page: page.value }); rows.value = r.data; meta.value = r.meta; counts.value = r.counts }
  catch (e) { error.value = apiError(e, 'Could not load reviews.').message } finally { loading.value = false }
}
const setStatus = (v) => { status.value = v; page.value = 1; load() }
const go = (p) => { page.value = p; load() }
watch(q, debounce(() => { page.value = 1; load() }, 300))
async function setReview(r, s) {
  busy.value = r.id
  try { await adminReviews.setStatus(r.id, s); toast.show(s === 'hidden' ? 'Review hidden from the shop' : 'Review shown in the shop'); await load() }
  catch (e) { error.value = apiError(e).message } finally { busy.value = null }
}
async function remove(r) {
  busy.value = r.id
  try { await adminReviews.remove(r.id); confirming.value = null; toast.show('Review deleted'); await load() }
  catch (e) { error.value = apiError(e).message } finally { busy.value = null }
}
onMounted(load)
</script>

<style scoped>
.list { display: grid; gap: 14px; }
.rv { border-radius: 18px; padding: 18px 22px; }
.rv.hid { opacity: .7; }
.rv-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.stars { color: #F2A900; font-size: 17px; letter-spacing: 2px; }
.stars .off { color: #D9D4BF; }
.rv h3 { margin: 10px 0 0; font-size: 16px; font-weight: 800; }
.rv p { margin: 6px 0 0; font-size: 14.5px; line-height: 1.6; white-space: pre-line; }
.meta { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 4px 16px; font-size: 13px; color: var(--muted); }
.meta a { color: var(--forest); font-weight: 700; }
.acts { margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
.cn { margin-left: 6px; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 10px; background: rgba(0, 0, 0, .08); font-size: 11px; display: inline-flex; align-items: center; justify-content: center; }
</style>
