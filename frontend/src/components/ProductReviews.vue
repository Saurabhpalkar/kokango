<script setup>
// Reviews on the product page: star summary, the published reviews, and a form for customers who bought the product.
import { computed, reactive, ref, watch } from 'vue'
import api, { apiError } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { formatDate } from '@/utils/format'

const props = defineProps({ slug: { type: String, required: true } })
const auth = useAuthStore()

const list = ref([]); const meta = ref(null); const summary = ref({ count: 0, average: 0, stars: {} }); const viewer = ref({})
const loading = ref(false); const more = ref(false)
const open = ref(false); const busy = ref(false); const err = ref(''); const errs = reactive({}); const done = ref('')
const f = reactive({ rating: 0, title: '', body: '' })
const hover = ref(0)

async function load(page = 1) {
  if (page === 1) loading.value = true; else more.value = true
  try {
    const r = (await api.get(`/products/${encodeURIComponent(props.slug)}/reviews`, { params: { page, per_page: 6 } })).data
    list.value = page === 1 ? r.data : [...list.value, ...r.data]
    meta.value = r.meta; summary.value = r.summary; viewer.value = r.viewer
    if (r.viewer.review && !open.value) Object.assign(f, { rating: r.viewer.review.rating, title: r.viewer.review.title, body: r.viewer.review.body })
  } catch (e) { /* reviews are optional: the section just stays empty */ }
  finally { loading.value = false; more.value = false }
}
watch(() => [props.slug, auth.isLoggedIn], () => load(1), { immediate: true })

const pct = (n) => (summary.value.count ? Math.round((n / summary.value.count) * 100) : 0)
const hasMore = computed(() => meta.value && meta.value.current_page < meta.value.last_page)
const words = ['', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent']

async function submit() {
  err.value = ''; done.value = ''; Object.keys(errs).forEach((k) => delete errs[k])
  if (!f.rating) { errs.rating = 'Choose a star rating.'; return }
  if (f.body.trim().length < 10) { errs.body = 'Please write at least a few words.'; return }
  busy.value = true
  try {
    await api.post(`/products/${encodeURIComponent(props.slug)}/reviews`, { rating: f.rating, title: f.title.trim() || null, body: f.body.trim() })
    done.value = viewer.value.review ? 'Your review was updated. Thank you!' : 'Thank you! Your review is now on this page.'
    open.value = false; await load(1)
  } catch (e) { const ae = apiError(e, 'Could not save your review.'); Object.assign(errs, ae.errors); if (!Object.keys(ae.errors).length) err.value = ae.message }
  finally { busy.value = false }
}
</script>

<template>
  <section class="revs" aria-labelledby="rev-h">
    <div class="rv-head">
      <h2 id="rev-h">Customer reviews</h2>
      <div v-if="summary.count" class="avg">
        <span class="big">{{ summary.average.toFixed(1) }}</span>
        <span class="stars" role="img" :aria-label="`${summary.average} out of 5 stars`"><span v-for="n in 5" :key="n" class="st" :style="{ '--f': Math.max(0, Math.min(1, summary.average - n + 1)) * 100 + '%' }" aria-hidden="true">&#9733;</span></span>
        <span class="cnt">{{ summary.count }} {{ summary.count === 1 ? 'review' : 'reviews' }}</span>
      </div>
    </div>

    <div class="rv-grid">
      <aside class="rv-side">
        <ul v-if="summary.count" class="bars" aria-label="Ratings breakdown">
          <li v-for="s in [5, 4, 3, 2, 1]" :key="s"><span>{{ s }} &#9733;</span><span class="bar"><span :style="{ width: pct(summary.stars[s] || 0) + '%' }"></span></span><span class="n">{{ summary.stars[s] || 0 }}</span></li>
        </ul>
        <p v-else-if="!loading" class="none">No reviews yet. Bought this product? Be the first to share what you think.</p>

        <div class="cta">
          <template v-if="viewer.can_review">
            <button v-if="!open" type="button" class="btn btn-block" @click="open = true">{{ viewer.review ? 'Edit your review' : 'Write a review' }}</button>
          </template>
          <p v-else-if="viewer.signed_in" class="hint">You can review this product after you have ordered it.</p>
          <p v-else class="hint"><a href="/login">Log in</a> to review a product you have bought.</p>
          <p v-if="done" class="ok" role="status">{{ done }}</p>
          <p v-if="viewer.review?.status === 'hidden'" class="hint">Your review is not shown in the shop.</p>
        </div>
      </aside>

      <div class="rv-main">
        <transition name="drop">
          <form v-if="open" class="card rv-form" novalidate @submit.prevent="submit">
            <h3>{{ viewer.review ? 'Edit your review' : 'Write a review' }}</h3>
            <div class="pick" role="radiogroup" aria-label="Your rating" @mouseleave="hover = 0">
              <button v-for="n in 5" :key="n" type="button" role="radio" :aria-checked="f.rating === n ? 'true' : 'false'" :aria-label="`${n} star${n > 1 ? 's' : ''}`" class="pk" :class="{ on: n <= (hover || f.rating) }" @mouseenter="hover = n" @click="f.rating = n">&#9733;</button>
              <span class="pk-w">{{ words[hover || f.rating] }}</span>
            </div>
            <span v-if="errs.rating" class="err">{{ errs.rating }}</span>
            <label class="field">Title (optional)<input v-model="f.title" maxlength="120" placeholder="Sum it up in a few words"></label>
            <label class="field">Your review<textarea v-model="f.body" rows="4" maxlength="2000" placeholder="How did you use it? How was the taste, smell and freshness?"></textarea></label>
            <span v-if="errs.body" class="err">{{ errs.body }}</span>
            <p v-if="err" class="err" role="alert">{{ err }}</p>
            <div class="row"><button type="submit" class="btn" :disabled="busy">{{ busy ? 'Saving…' : 'Post review' }}</button><button type="button" class="btn btn-line" @click="open = false">Cancel</button></div>
          </form>
        </transition>

        <div v-if="loading" class="rv-sk" aria-hidden="true"><div v-for="n in 2" :key="n" class="skeleton"></div></div>
        <ul v-else class="rv-list">
          <li v-for="r in list" :key="r.id" class="rv">
            <div class="rv-t">
              <span class="stars sm" role="img" :aria-label="`${r.rating} out of 5 stars`"><span v-for="n in 5" :key="n" class="st" :style="{ '--f': n <= r.rating ? '100%' : '0%' }" aria-hidden="true">&#9733;</span></span>
              <b v-if="r.title">{{ r.title }}</b>
            </div>
            <p>{{ r.body }}</p>
            <div class="by"><span class="av" aria-hidden="true">{{ r.name[0] }}</span>{{ r.name }}<span v-if="r.verified" class="ver">&#10003; Verified buyer</span><span class="dt">{{ formatDate(r.created_at) }}</span></div>
          </li>
        </ul>
        <button v-if="hasMore" type="button" class="btn btn-line more" :disabled="more" @click="load(meta.current_page + 1)">{{ more ? 'Loading…' : 'Show more reviews' }}</button>
      </div>
    </div>
  </section>
</template>

<style scoped>
.revs { padding-top: 64px; }
.rv-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px 24px; margin-bottom: 22px; }
.rv-head h2 { margin: 0; font-size: clamp(24px, 3.4vw, 32px); font-weight: 800; letter-spacing: -.6px; }
.avg { display: flex; align-items: center; gap: 10px; }
.big { font-size: 30px; font-weight: 800; line-height: 1; }
.cnt { font-size: 14px; color: var(--muted); }
.stars { display: inline-flex; gap: 2px; font-size: 20px; line-height: 1; }
.stars.sm { font-size: 15px; }
/* each star fills from the left (partial stars for averages) */
.st { color: #D9D4BF; background: linear-gradient(90deg, #F2A900 var(--f, 0%), #D9D4BF var(--f, 0%)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }

.rv-grid { display: grid; grid-template-columns: 300px minmax(0, 1fr); gap: 36px; align-items: start; }
.bars { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
.bars li { display: grid; grid-template-columns: 36px 1fr 28px; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; }
.bar { height: 8px; border-radius: 4px; background: #ECE8D8; overflow: hidden; }
.bar span { display: block; height: 100%; border-radius: 4px; background: #F2A900; transition: width .8s var(--ease); }
.n { color: var(--muted); text-align: right; }
.none, .hint { margin: 0; font-size: 14px; line-height: 1.6; color: var(--muted); }
.hint a { color: var(--forest); font-weight: 700; text-decoration: underline; }
.cta { margin-top: 18px; display: grid; gap: 10px; }
.ok { margin: 0; font-size: 13.5px; font-weight: 700; color: #1E7A34; }

.rv-form { padding: 22px; border-radius: 18px; display: grid; gap: 12px; margin-bottom: 18px; }
.rv-form h3 { margin: 0; font-size: 18px; font-weight: 800; }
.pick { display: flex; align-items: center; gap: 2px; }
.pk { width: 40px; height: 40px; border: 0; background: none; font-size: 30px; line-height: 1; color: #D9D4BF; transition: transform .15s, color .15s; padding: 0; }
.pk.on { color: #F2A900; }
.pk:hover { transform: scale(1.15); }
.pk-w { margin-left: 8px; font-size: 14px; font-weight: 700; color: var(--muted); }
.rv-form textarea { min-height: 110px; padding: 12px 14px; border: 1px solid #CFCBB6; border-radius: var(--r-sm); font: inherit; font-size: 14px; resize: vertical; }
.rv-form textarea:focus { outline: none; border-color: var(--forest); box-shadow: 0 0 0 3px rgba(55, 146, 55, .28); }
.row { display: flex; gap: 10px; flex-wrap: wrap; }

.rv-list { list-style: none; margin: 0; padding: 0; }
.rv { padding: 18px 0; border-bottom: 1px solid var(--line); animation: rise .5s var(--ease) both; }
.rv:first-child { padding-top: 0; }
.rv-t { display: flex; align-items: center; flex-wrap: wrap; gap: 6px 12px; }
.rv-t b { font-size: 15.5px; }
.rv p { margin: 8px 0 0; font-size: 15px; line-height: 1.65; white-space: pre-line; }
.by { margin-top: 10px; display: flex; align-items: center; flex-wrap: wrap; gap: 6px 10px; font-size: 13.5px; font-weight: 700; }
.av { width: 30px; height: 30px; border-radius: 50%; background: var(--forest); color: var(--mango); display: inline-flex; align-items: center; justify-content: center; font-size: 13px; }
.ver { font-size: 12px; font-weight: 700; color: #1E7A34; background: var(--tint); padding: 3px 8px; border-radius: 999px; }
.dt { font-weight: 500; color: var(--muted); }
.more { margin-top: 18px; }
.rv-sk { display: grid; gap: 14px; }
.rv-sk .skeleton { height: 90px; }
.drop-enter-active, .drop-leave-active { transition: opacity .25s, transform .25s var(--ease); }
.drop-enter-from, .drop-leave-to { opacity: 0; transform: translateY(-8px); }
@media (max-width: 860px) { .rv-grid { grid-template-columns: 1fr; gap: 22px; } .revs { padding-top: 44px; } }
</style>
