<script setup>
// Quick-view window opened from a product card's eye button. One instance lives in App.vue.
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useQuickViewStore } from '@/stores/quickview'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import { useWishlistStore } from '@/stores/wishlist'
import { inrp } from '@/utils/format'
import { hoverPhoto } from '@/content/productPhotos'
import * as catalogue from '@/services/catalogueService'

const quick = useQuickViewStore()
const cart = useCartStore()
const toast = useToastStore()
const wishlist = useWishlistStore()
const router = useRouter()

const p = computed(() => quick.product)
const variants = computed(() => (p.value?.variants || []).filter((v) => v.is_active !== false).sort((a, b) => a.price_paise - b.price_paise))
const variant = ref(null)
const qty = ref(1)
const img = ref('')
const busy = ref(false)
const dialog = ref(null)
const closeBtn = ref(null)
let lastFocus = null

const okStock = (v) => !!v && v.in_stock !== false && (v.stock == null || v.stock > 0)
const inStock = computed(() => okStock(variant.value))
const maxQty = computed(() => Math.max(1, Math.min(20, variant.value?.stock ?? 20)))
const price = computed(() => variant.value?.price_paise ?? p.value?.min_price_paise ?? 0)
const mrp = computed(() => (variant.value && variant.value.mrp_paise > variant.value.price_paise ? variant.value.mrp_paise : 0))
const images = computed(() => [p.value?.image_url, hoverPhoto(p.value)].filter(Boolean))
const steps = computed(() => String(p.value?.how_to_use || '').split(/\n+/).map((s) => s.trim()).filter(Boolean))
const saved = computed(() => !!p.value && wishlist.has(p.value.slug))

// ---- frequently bought together: this item + up to two others (same category first)
const others = ref([])
const picks = ref({})
const bundle = computed(() => [p.value, ...others.value].filter(Boolean))
const bundlePrice = (x) => (x === p.value ? price.value : catalogue.defaultVariant(x)?.price_paise ?? x.min_price_paise)
const bundleMrp = (x) => { const v = x === p.value ? variant.value : catalogue.defaultVariant(x); return v && v.mrp_paise > v.price_paise ? v.mrp_paise : 0 }
const bundleTotal = computed(() => bundle.value.filter((x) => picks.value[x.slug]).reduce((a, x) => a + bundlePrice(x) * (x === p.value ? qty.value : 1), 0))
const bundleCount = computed(() => bundle.value.filter((x) => picks.value[x.slug]).length)
async function loadOthers(product) {
  others.value = []
  try {
    const { items } = await catalogue.products({ per_page: 24 })
    const pool = items.filter((x) => x.slug !== product.slug && x.variants?.some(okStock))
    pool.sort((a, b) => (b.category?.slug === product.category?.slug) - (a.category?.slug === product.category?.slug))
    if (quick.product === product) {
      others.value = pool.slice(0, 2)
      picks.value = Object.fromEntries([product, ...others.value].map((x) => [x.slug, true]))
    }
  } catch (e) { /* the section is simply left out */ }
}

watch(p, async (product, before) => {
  document.body.style.overflow = product ? 'hidden' : ''
  if (!product) { if (before && lastFocus) lastFocus.focus?.(); return }
  if (!before) lastFocus = document.activeElement
  variant.value = catalogue.defaultVariant(product)
  qty.value = 1
  img.value = product.image_url
  loadOthers(product)
  await nextTick()
  dialog.value && dialog.value.focus({ preventScroll: true })   // keyboard users can Tab straight to the close button
})
onBeforeUnmount(() => { document.body.style.overflow = '' })

function close() { quick.close() }
function onKey(e) {
  if (e.key === 'Escape') { close(); return }
  if (e.key !== 'Tab' || !dialog.value) return
  // keep keyboard focus inside the window
  const f = [...dialog.value.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),[tabindex]:not([tabindex="-1"])')].filter((x) => x.offsetParent !== null)
  if (!f.length) return
  if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus() }
  else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus() }
}

async function addToCart() {
  if (!inStock.value || busy.value) return
  busy.value = true
  try { if (await cart.add(variant.value.id, qty.value)) toast.show(`${p.value.name} added to cart`) } finally { busy.value = false }
}
async function addBundle() {
  const chosen = bundle.value.filter((x) => picks.value[x.slug])
  if (!chosen.length || busy.value) return
  busy.value = true
  try {
    let ok = 0
    for (const x of chosen) {
      const v = x === p.value ? variant.value : catalogue.defaultVariant(x)
      if (v && okStock(v) && await cart.add(v.id, x === p.value ? qty.value : 1)) ok++
    }
    if (ok) toast.show(`${ok} ${ok === 1 ? 'item' : 'items'} added to cart`)
  } finally { busy.value = false }
}
function go(path) { close(); router.push(path) }
</script>

<template>
  <transition name="qv">
    <div v-if="p" class="qv-back" @click.self="close" @keydown="onKey">
      <div ref="dialog" class="qv" tabindex="-1" role="dialog" aria-modal="true" :aria-labelledby="'qv-title'">
        <button ref="closeBtn" type="button" class="qv-x" aria-label="Close quick view" @click="close"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>

        <div class="qv-media">
          <div class="qv-img"><img :src="img" :alt="p.name" width="700" height="700"></div>
          <div v-if="images.length > 1" class="qv-thumbs">
            <button v-for="(src, i) in images" :key="src" type="button" class="qv-thumb" :class="{ on: img === src }" :aria-label="`Show photo ${i + 1}`" :aria-pressed="img === src ? 'true' : 'false'" @click="img = src"><img :src="src" alt="" width="80" height="80"></button>
          </div>
          <a class="qv-more" :href="`/product/${p.slug}`" @click.prevent="go(`/product/${p.slug}`)">More Product Info
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
          </a>
        </div>

        <div class="qv-info">
          <h2 id="qv-title" class="qv-name">{{ p.name }}</h2>
          <div class="qv-price">
            <s v-if="mrp" class="was">{{ inrp(mrp) }}</s>
            <span class="now" :class="{ sale: mrp }">{{ inrp(price) }}</span>
            <span class="stock" :class="{ bad: !inStock }">({{ inStock ? 'In stock' : 'Out of stock' }})</span>
          </div>

          <p v-if="p.description" class="qv-desc">{{ p.description }}</p>

          <ul class="qv-seals" aria-label="Quality">
            <li><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#2E7D32"/><circle cx="32" cy="32" r="25" fill="none" stroke="#fff" stroke-width="1.4" stroke-dasharray="2 3"/><path d="M32 18l3.4 7 7.6 1.1-5.5 5.4 1.3 7.6L32 35.5l-6.8 3.6 1.3-7.6-5.5-5.4 7.6-1.1z" fill="#fff"/><text x="32" y="50" text-anchor="middle" font-size="6.5" font-weight="700" fill="#fff" font-family="sans-serif">PREMIUM</text></svg><span>Premium quality</span></li>
            <li><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#2E7D32"/><circle cx="32" cy="32" r="25" fill="none" stroke="#fff" stroke-width="1.4" stroke-dasharray="2 3"/><text x="32" y="33" text-anchor="middle" font-size="13" font-weight="800" fill="#fff" font-family="sans-serif">100%</text><text x="32" y="44" text-anchor="middle" font-size="7" font-weight="700" fill="#fff" font-family="sans-serif">NATURAL</text></svg><span>100% natural</span></li>
            <li><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#2E7D32"/><circle cx="32" cy="32" r="25" fill="none" stroke="#fff" stroke-width="1.4" stroke-dasharray="2 3"/><path d="M22 40c0-11 8-18 21-19-1 13-8 20-19 20M22 40c4-6 9-10 15-13" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/></svg><span>Farm fresh</span></li>
          </ul>

          <section v-if="p.ingredients" class="qv-sec"><h3>Ingredients</h3><p>{{ p.ingredients }}</p></section>
          <section v-if="steps.length" class="qv-sec"><h3>How to use</h3><ul><li v-for="(s, i) in steps" :key="i">{{ s }}</li></ul></section>

          <div v-if="variants.length > 1" class="qv-sizes" role="group" aria-label="Pack size">
            <span class="lbl">Pack size</span>
            <button v-for="v in variants" :key="v.id" type="button" class="sz" :class="{ on: variant && v.id === variant.id, dim: !okStock(v) }" :aria-pressed="variant && v.id === variant.id ? 'true' : 'false'" @click="variant = v; qty = 1">{{ v.size_label }}</button>
          </div>
          <div v-else-if="variant" class="qv-one">Pack size: <strong>{{ variant.size_label }}</strong></div>

          <div class="qv-buy">
            <div class="qty" role="group" aria-label="Quantity">
              <button type="button" aria-label="Decrease quantity" :disabled="qty <= 1" @click="qty = Math.max(1, qty - 1)">&minus;</button>
              <output aria-live="polite">{{ qty }}</output>
              <button type="button" aria-label="Increase quantity" :disabled="qty >= maxQty" @click="qty = Math.min(maxQty, qty + 1)">+</button>
            </div>
            <button type="button" class="qv-add" :disabled="!inStock || busy" :aria-busy="busy ? 'true' : 'false'" @click="addToCart">
              {{ inStock ? 'Add To Cart' : 'Out of stock' }}
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h3l2.6 12.2a1.5 1.5 0 001.5 1.2h8.3a1.5 1.5 0 001.5-1.1L21.5 8H6.4"/></svg>
            </button>
          </div>
          <button v-if="saved" type="button" class="qv-wish" @click="go('/wishlist')">View wishlist
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.4 1.1 5 3 1.6-1.9 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg>
          </button>
          <button v-else type="button" class="qv-wish line" @click="wishlist.toggle(p)">Add to wishlist
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.4 1.1 5 3 1.6-1.9 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg>
          </button>

          <section v-if="others.length" class="fbt" aria-labelledby="fbt-h">
            <h3 id="fbt-h">Frequently bought together</h3>
            <ul class="fbt-row">
              <template v-for="(x, i) in bundle" :key="x.slug">
                <li v-if="i" class="plus" aria-hidden="true">+</li>
                <li class="fbt-item" :class="{ off: !picks[x.slug] }">
                  <label class="fbt-chk"><input v-model="picks[x.slug]" type="checkbox" :aria-label="`Include ${x.name}`"></label>
                  <img :src="x.image_url" alt="" width="160" height="160" loading="lazy">
                  <div class="fbt-name">{{ i ? '1 × ' + x.name : 'This item: ' + x.name }}</div>
                  <div class="fbt-stock">In stock</div>
                  <div class="fbt-price"><s v-if="bundleMrp(x)">{{ inrp(bundleMrp(x)) }}</s> <mark>{{ inrp(bundlePrice(x)) }}</mark></div>
                </li>
              </template>
            </ul>
            <div class="fbt-foot">
              <div>Total for {{ bundleCount }} {{ bundleCount === 1 ? 'item' : 'items' }}: <strong>{{ inrp(bundleTotal) }}</strong></div>
              <button type="button" class="qv-add sm" :disabled="!bundleCount || busy || !inStock" @click="addBundle">Add selected to cart</button>
            </div>
          </section>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.qv-back { position: fixed; inset: 0; z-index: 120; background: rgba(20, 24, 20, .55); display: flex; align-items: center; justify-content: center; padding: 24px; }
.qv { outline: none; position: relative; width: min(1100px, 100%); height: min(680px, calc(100vh - 48px)); background: #fff; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.08fr); overflow: hidden; box-shadow: 0 30px 80px rgba(0, 0, 0, .35); }
.qv-x { position: absolute; z-index: 3; top: 14px; right: 18px; width: 44px; height: 44px; border: 0; background: rgba(255, 255, 255, .9); border-radius: 50%; color: #6B6B6B; display: inline-flex; align-items: center; justify-content: center; }
.qv-x:hover { color: var(--ink); background: #F2F2F2; }

/* left: photo + "More Product Info" */
.qv-media { position: relative; display: flex; flex-direction: column; min-height: 0; background: #EDE8D9; }
.qv-img { flex: 1; min-height: 0; overflow: hidden; }
.qv-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.qv-thumbs { position: absolute; left: 14px; bottom: 76px; display: flex; gap: 8px; }
.qv-thumb { width: 58px; height: 58px; padding: 0; border: 2px solid rgba(255, 255, 255, .7); border-radius: 8px; overflow: hidden; background: #fff; }
.qv-thumb.on { border-color: #fff; box-shadow: 0 0 0 2px var(--forest); }
.qv-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.qv-more { flex: none; display: flex; align-items: center; justify-content: center; gap: 12px; min-height: 60px; background: #FF6B5B; color: #fff; font-size: 17px; font-weight: 700; }
.qv-more:hover { background: #F2584A; color: #fff; }

/* right: details */
.qv-info { min-height: 0; overflow-y: auto; padding: 48px 44px 36px; overscroll-behavior: contain; }
.qv-name { margin: 0 48px 0 0; font-size: 32px; font-weight: 700; letter-spacing: -.4px; line-height: 1.15; }
.qv-price { margin-top: 18px; display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px 18px; }
.was { font-size: 20px; font-weight: 600; color: #9AA19B; }
.now { font-size: 26px; font-weight: 800; color: var(--ink); }
.now.sale { color: #EF5B4C; }
.stock { font-size: 16px; font-weight: 600; color: var(--ink); }
.stock.bad { color: var(--danger); }
.qv-desc { margin: 22px 0 0; font-size: 16px; line-height: 1.75; color: #4A4A4A; white-space: pre-line; }
.qv-seals { list-style: none; margin: 18px 0 0; padding: 0; display: flex; gap: 18px; }
.qv-seals li { display: flex; flex-direction: column; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: var(--forest); text-align: center; }
.qv-seals svg { width: 92px; height: 92px; filter: drop-shadow(0 4px 8px rgba(46, 125, 50, .3)); }
.qv-sec { margin-top: 28px; }
.qv-sec h3 { margin: 0; font-size: 30px; font-weight: 700; letter-spacing: -.3px; }
.qv-sec p, .qv-sec li { font-size: 16px; line-height: 1.7; color: #4A4A4A; }
.qv-sec p { margin: 8px 0 0; }
.qv-sec ul { margin: 8px 0 0; padding-left: 22px; }

.qv-sizes { margin-top: 28px; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.lbl { width: 100%; font-size: 14px; font-weight: 700; }
.sz { min-height: 40px; padding: 0 16px; border: 1.5px solid #D5D5D5; background: #fff; color: var(--ink); font-size: 14px; font-weight: 600; }
.sz.on { border-color: var(--ink); background: var(--ink); color: #fff; }
.sz.dim:not(.on) { color: #A0A0A0; text-decoration: line-through; }
.qv-one { margin-top: 24px; font-size: 14px; color: var(--muted); }
.qv-one strong { color: var(--ink); }

.qv-buy { margin-top: 22px; display: flex; flex-wrap: wrap; gap: 24px; }
.qty { display: inline-flex; align-items: stretch; height: 70px; border: 1px solid #D5D5D5; }
.qty button { width: 52px; border: 0; background: #fff; font-size: 26px; color: var(--ink); }
.qty button:disabled { color: #C8C8C8; cursor: not-allowed; }
.qty output { min-width: 50px; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 600; }
.qv-add { display: inline-flex; align-items: center; justify-content: center; gap: 16px; min-height: 70px; padding: 0 56px; border: 0; background: #222; color: #fff; font-size: 19px; font-weight: 600; }
.qv-add:hover:not(:disabled) { background: #000; }
.qv-add:disabled { background: #BDBDBD; cursor: not-allowed; }
.qv-add.sm { min-height: 48px; padding: 0 22px; font-size: 15px; }
.qv-wish { margin-top: 20px; display: inline-flex; align-items: center; gap: 16px; min-height: 66px; padding: 0 32px; border: 0; background: #2F353A; color: #fff; font-size: 19px; font-weight: 600; }
.qv-wish:hover { background: #1E2327; }
.qv-wish.line { background: #fff; color: var(--ink); border: 1.5px solid #2F353A; }
.qv-wish.line:hover { background: #F4F4F4; }

/* frequently bought together */
.fbt { margin-top: 30px; }
.fbt h3 { margin: 0 0 14px; font-size: 20px; font-weight: 700; }
.fbt-row { list-style: none; margin: 0; padding: 0 0 6px; display: flex; align-items: stretch; gap: 6px; overflow-x: auto; }
.plus { flex: none; align-self: center; font-size: 18px; color: #6B6B6B; }
.fbt-item { position: relative; flex: 1 1 0; min-width: 128px; border: 1px solid #DCDCDC; padding: 12px 12px 14px; text-align: center; transition: opacity .2s; }
.fbt-item.off { opacity: .45; }
.fbt-chk { position: absolute; top: 6px; right: 6px; }
.fbt-chk input { width: 18px; height: 18px; accent-color: var(--forest); }
.fbt-item img { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; }
.fbt-name { margin-top: 8px; font-size: 14px; line-height: 1.4; color: #333; }
.fbt-stock { font-size: 14px; color: #1E8E3E; }
.fbt-price { font-size: 14px; }
.fbt-price s { color: #9AA19B; display: block; }
.fbt-price mark { background: #FFF2B8; color: var(--ink); padding: 0 2px; }
.fbt-foot { margin-top: 14px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; font-size: 15px; }
.fbt-foot strong { font-size: 18px; }

.qv-enter-active, .qv-leave-active { transition: opacity .25s ease; }
.qv-enter-active .qv, .qv-leave-active .qv { transition: transform .3s var(--ease); }
.qv-enter-from, .qv-leave-to { opacity: 0; }
.qv-enter-from .qv, .qv-leave-to .qv { transform: translateY(18px) scale(.98); }

@media (max-width: 1000px) {
  .qv-info { padding: 40px 28px 28px; }
  .qv-name { font-size: 26px; }
  .qv-sec h3 { font-size: 22px; }
  .qty { height: 56px; }
  .qv-add { min-height: 56px; padding: 0 30px; font-size: 16px; }
  .qv-wish { min-height: 54px; font-size: 16px; padding: 0 24px; }
  .qv-buy { gap: 12px; }
}
/* phones: full-screen sheet, photo on top */
@media (max-width: 760px) {
  .qv-back { padding: 0; align-items: stretch; }
  .qv { height: 100%; display: block; overflow-y: auto; overscroll-behavior: contain; }
  .qv-media { height: min(100vw, 440px); }
  .qv-more { min-height: 50px; font-size: 15px; }
  .qv-thumbs { bottom: 62px; }
  .qv-thumb { width: 46px; height: 46px; }
  .qv-info { overflow: visible; padding: 22px 16px 28px; }
  .qv-x { top: 10px; right: 10px; }
  .qv-name { margin-right: 0; font-size: 24px; }
  .qv-price { margin-top: 10px; }
  .was { font-size: 16px; } .now { font-size: 22px; } .stock { font-size: 14px; }
  .qv-desc { margin-top: 14px; font-size: 15px; line-height: 1.65; }
  .qv-seals { gap: 12px; }
  .qv-seals svg { width: 68px; height: 68px; }
  .qv-sec { margin-top: 20px; }
  .qv-sec h3 { font-size: 20px; }
  .qv-sec p, .qv-sec li { font-size: 15px; }
  .qv-buy { gap: 10px; flex-wrap: nowrap; }
  .qty { height: 52px; }
  .qty button { width: 42px; }
  .qty output { min-width: 36px; }
  .qv-add { flex: 1; min-height: 52px; padding: 0 16px; gap: 10px; font-size: 16px; }
  .qv-wish { width: 100%; justify-content: center; min-height: 50px; margin-top: 10px; }
  .fbt-item { flex: 0 0 140px; }
  .fbt-name, .fbt-stock, .fbt-price { font-size: 13px; }
  .fbt-foot { flex-direction: column; align-items: stretch; text-align: center; }
  .fbt-foot .qv-add.sm { width: 100%; }
}
</style>
