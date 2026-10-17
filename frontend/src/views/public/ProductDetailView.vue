<template>
<div class="dcroot">
  <AnnouncementBar />
  <SiteHeader active="products" search />

  <main class="wrap page">
    <nav class="crumbs" aria-label="Breadcrumb">
      <a href="/">Home</a><span aria-hidden="true">/</span>
      <a :href="product ? `/products?category=${product.category.slug}` : '/products'">{{ product ? product.category.name : 'Shop' }}</a><span aria-hidden="true">/</span>
      <span aria-current="page">{{ product ? product.name : (loading ? 'Loading...' : 'Not found') }}</span>
    </nav>

    <section v-if="loading" class="pd" aria-busy="true" aria-label="Loading product">
      <div class="skeleton sk-main"></div>
      <div class="sk-info"><div class="skeleton" style="height:18px;width:90px"></div><div class="skeleton" style="height:40px;width:80%"></div><div class="skeleton" style="height:30px;width:40%"></div><div class="skeleton" style="height:90px"></div><div class="skeleton" style="height:48px;width:60%"></div></div>
    </section>
    <section v-else-if="loadError" class="card state"><h1>Something went wrong</h1><p role="alert">{{ loadError }}</p><button type="button" class="btn" @click="load">Try again</button></section>
    <section v-else-if="!product" class="card state"><h1>Product not found</h1><p>We could not find the product you are looking for.</p><a class="btn" href="/products">Browse all products</a></section>

    <template v-else>
      <section class="pd">
        <!-- gallery -->
        <div class="gal">
          <div class="mainbox card" :class="{ zooming }" @mousemove="onZoom" @mouseenter="zooming = true" @mouseleave="zooming = false">
            <img :src="mainImg" :alt="`${product.name} pack`" :style="{ transformOrigin: zoomOrigin }" width="560" height="560">
            <span v-if="savePct > 0" class="tag tag-sale sale">-{{ savePct }}%</span>
          </div>
          <div v-if="thumbs.length > 1" class="thumbs">
            <button v-for="(t, i) in thumbs" :key="i" type="button" class="thumb" :class="{ on: t.img === mainImg }" :aria-label="`Show image ${i + 1} of ${thumbs.length}`" :aria-pressed="t.img === mainImg ? 'true' : 'false'" @click="mainImg = t.img"><img :src="t.img" alt="" loading="lazy" width="96" height="96"></button>
          </div>
        </div>

        <!-- info -->
        <div class="info">
          <a class="catlink tag tag-soft" :href="`/products?category=${product.category.slug}`">{{ product.category.name }}</a>
          <h1>{{ product.name }}</h1>
          <div v-if="product.hindi_name" class="hindi">{{ product.hindi_name }}</div>

          <div class="price">
            <span class="now">{{ variant ? inrp(variant.price_paise) : '' }}</span>
            <template v-if="savePct > 0"><span class="was"><span class="sr-only">Original price </span>{{ inrp(variant.mrp_paise) }}</span><span class="tag tag-soft save">Save {{ savePct }}%</span></template>
          </div>
          <div class="tax">Inclusive of all taxes</div>

          <p class="desc">{{ product.description }}</p>

          <div class="lbl" id="size-l">Pack size</div>
          <div class="sizes" role="group" aria-labelledby="size-l">
            <button v-for="v in variants" :key="v.id" type="button" class="pill" :class="{ on: variant && v.id === variant.id, dim: !v.in_stock }" :aria-pressed="variant && v.id === variant.id ? 'true' : 'false'" @click="pickVariant(v)">{{ v.size_label }}</button>
          </div>
          <div v-if="variant && !variant.in_stock" role="status" class="stock bad">Out of stock</div>
          <div v-else-if="variant && variant.stock < 10" role="status" class="stock low">Only {{ variant.stock }} left</div>

          <div ref="buyEl" class="buy">
            <div class="qty" role="group" aria-label="Quantity">
              <button type="button" aria-label="Decrease quantity" :disabled="qty <= 1" @click="qty = Math.max(1, qty - 1)">&minus;</button>
              <output aria-live="polite">{{ qty }}</output>
              <button type="button" aria-label="Increase quantity" :disabled="qty >= maxQty" @click="qty = Math.min(maxQty, qty + 1)">+</button>
            </div>
            <button type="button" class="btn btn-sun grow" :disabled="!canBuy || busy" :aria-busy="busy ? 'true' : 'false'" @click="addToCart">{{ canBuy ? (busy ? 'Adding...' : 'Add to Cart') : 'Out of stock' }}</button>
            <a v-if="canBuy" href="/checkout" class="btn grow" @click.prevent="buyNow">Buy Now</a>
            <span v-else class="btn grow" aria-disabled="true">Buy Now</span>
          </div>

          <div class="card pin">
            <h2>Check delivery</h2>
            <form @submit.prevent="checkPin">
              <label for="pin" class="sr-only">Pincode</label>
              <input id="pin" v-model="pin" inputmode="numeric" maxlength="6" placeholder="Enter 6-digit pincode" autocomplete="postal-code">
              <button type="submit" class="btn btn-sm">Check</button>
            </form>
            <div v-if="pinState === 'checking'" class="pmsg" role="status">Checking...</div>
            <div v-else-if="pinState === 'ok'" class="pmsg ok" role="status">&#10003; Delivery available to {{ pinInfo.city ? pinInfo.city + ', ' + pinInfo.state : pinInfo.pincode }}. Estimated {{ stdDays }} days.</div>
            <div v-else-if="pinState === 'no'" class="pmsg bad" role="alert">Sorry, we do not deliver to {{ pinInfo.pincode }} yet.</div>
            <div v-else-if="pinState === 'error'" class="pmsg bad" role="alert">{{ pinMsg }}</div>
            <div v-else-if="pinState === 'bad'" class="pmsg bad" role="alert">Enter a valid 6-digit pincode</div>
          </div>

          <ul class="trust">
            <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>Fresh small batches</li>
            <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>Secure Razorpay payment</li>
            <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>Easy returns</li>
          </ul>

          <details v-if="product.ingredients" class="ship" open>
            <summary>Ingredients</summary>
            <p>{{ product.ingredients }}</p>
          </details>
          <details v-if="product.how_to_use" class="ship">
            <summary>How to use</summary>
            <ul class="steps"><li v-for="(st, i) in usageSteps" :key="i">{{ st }}</li></ul>
          </details>
          <details class="ship">
            <summary>Shipping &amp; returns</summary>
            <p>Orders are delivered across India. Use the pincode check above for your estimate. Read our <a href="/policy/shipping">Shipping Policy</a> and <a href="/policy/refund">Refund Policy</a> for details.</p>
          </details>
        </div>
      </section>

      <!-- related products from the same category -->
      <section v-if="related.length" class="rel" aria-labelledby="rel-h">
        <div class="sec-head"><div><div class="eyebrow">More to explore</div><h2 id="rel-h" class="sec-title">You may also like</h2></div><a :href="`/products?category=${product.category.slug}`" class="link-arrow">View all <span aria-hidden="true">&rarr;</span></a></div>
        <div class="pgrid"><ProductCard v-for="(p, k) in related" :key="p.id" v-reveal="{ delay: (k % 4) * 80 }" :product="p" :sizes="false" /></div>
      </section>
    </template>
  </main>

  <!-- phones: purchase actions stay within reach while reading -->
  <div v-if="product && !loading" class="mbar" :class="{ show: !buyInView }" role="region" aria-label="Purchase" :aria-hidden="buyInView ? 'true' : null" :inert="buyInView ? '' : null">
    <div class="mprice"><strong>{{ variant ? inrp(variant.price_paise) : '' }}</strong><span v-if="variant">{{ variant.size_label }}</span></div>
    <button type="button" class="btn btn-sun btn-sm" :disabled="!canBuy || busy" @click="addToCart">{{ canBuy ? 'Add to Cart' : 'Out of stock' }}</button>
    <button v-if="canBuy" type="button" class="btn btn-sm" @click="buyNow">Buy Now</button>
  </div>

  <SiteFooter />
  <WhatsAppFab :raised="!!product && !buyInView" />
</div>
</template>

<script setup>
import AnnouncementBar from '@/components/AnnouncementBar.vue'
import SiteHeader from '@/components/SiteHeader.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import WhatsAppFab from '@/components/WhatsAppFab.vue'
import ProductCard from '@/components/ProductCard.vue'
import { useCartStore } from '@/stores/cart'
import { useRouter, useRoute } from 'vue-router'
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { useToastStore } from '@/stores/toast'
import { inrp } from '@/utils/format'
import { apiError } from '@/services/api'
import * as catalogue from '@/services/catalogueService'
import { hoverPhoto } from '@/content/productPhotos'
import * as shippingService from '@/services/shippingService'

const cart = useCartStore()
const router = useRouter()
const route = useRoute()
const toast = useToastStore()

const product = ref(null)
const loading = ref(true)
const loadError = ref('')
const variant = ref(null)
const qty = ref(1)
const mainImg = ref('')
const pin = ref('')
const pinState = ref('')
const pinInfo = ref({})
const pinMsg = ref('')
const busy = ref(false)
const related = ref([])
const zooming = ref(false)

const usageSteps = computed(() => String(product.value?.how_to_use || '').split(/\n+/).map((s) => s.trim()).filter(Boolean))

// phones: the bottom purchase bar appears only once the main buttons have scrolled out of view
const buyEl = ref(null)
const buyInView = ref(true)
let buyObs = null
watch(buyEl, (el) => {
  buyObs && buyObs.disconnect()
  if (!el || typeof IntersectionObserver === 'undefined') { buyInView.value = !!el; return }
  buyObs = new IntersectionObserver(([e]) => { buyInView.value = e.isIntersecting || e.boundingClientRect.bottom > 0 })   // hidden until scrolled past
  buyObs.observe(el)
})
onBeforeUnmount(() => buyObs && buyObs.disconnect())
const zoomOrigin = ref('50% 50%')

const variants = computed(() => (product.value?.variants || []).filter((v) => v.is_active !== false))
const thumbs = computed(() => [...new Set([...(product.value?.images?.length ? product.value.images : [product.value?.image_url]), hoverPhoto(product.value)].filter(Boolean))].map((img) => ({ img, alt: product.value.name })))
const canBuy = computed(() => !!variant.value && variant.value.in_stock && variant.value.stock > 0)
const maxQty = computed(() => Math.max(1, Math.min(20, variant.value ? variant.value.stock : 20)))
const savePct = computed(() => {
  const v = variant.value
  return v && v.mrp_paise && v.mrp_paise > v.price_paise ? Math.round((1 - v.price_paise / v.mrp_paise) * 100) : 0
})
const stdDays = computed(() => pinInfo.value.methods?.find((m) => m.code === 'standard')?.days || '3 to 5')

function pickVariant(v) { variant.value = v; qty.value = Math.min(qty.value, Math.max(1, Math.min(20, v.stock))) }
function onZoom(e) {
  const r = e.currentTarget.getBoundingClientRect()
  zoomOrigin.value = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`
}

async function loadRelated(p) {
  related.value = []
  try {
    const { items } = await catalogue.products({ category: p.category.slug, per_page: 5 })
    if (product.value && product.value.slug === p.slug) related.value = items.filter((x) => x.slug !== p.slug).slice(0, 4)
  } catch (e) { /* the section is optional; nothing to show */ }
}

async function load() {
  loading.value = true; loadError.value = ''; product.value = null; related.value = []
  try {
    const p = await catalogue.product(route.params.slug)
    product.value = p
    variant.value = catalogue.defaultVariant(p)
    qty.value = 1
    mainImg.value = p.image_url || p.images?.[0] || ''
    pin.value = ''; pinState.value = ''
    loadRelated(p)
  } catch (e) {
    const er = apiError(e)
    if (er.status !== 404) loadError.value = er.message
  } finally { loading.value = false }
}
watch(() => route.params.slug, (n) => { if (n) load() }, { immediate: true })

async function addLine() {
  if (!canBuy.value) return false
  busy.value = true
  try { return await cart.add(variant.value.id, qty.value) } finally { busy.value = false }
}
const addToCart = async () => {
  if (await addLine()) toast.show(`${product.value.name} (${variant.value.size_label}) added to cart`)
}
const buyNow = async () => { if (await addLine()) router.push('/checkout') }

async function checkPin() {
  const code = pin.value.trim()
  if (!/^\d{6}$/.test(code)) { pinState.value = 'bad'; return }
  pinState.value = 'checking'
  try {
    const r = await shippingService.serviceability(code)
    pinInfo.value = r
    pinState.value = r.serviceable ? 'ok' : 'no'
  } catch (e) {
    const er = apiError(e)
    pinMsg.value = er.errors.pincode || er.message
    pinState.value = er.status === 422 ? 'bad' : 'error'
  }
}
</script>

<style scoped>
.page { padding-bottom: 8px; }
.crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 10px; padding: 18px 0 22px; font-size: 13px; color: var(--muted); line-height: 1.6; }
.crumbs a { display: inline-flex; align-items: center; min-height: 32px; }
.crumbs a:hover { color: var(--forest); text-decoration: underline; }
.crumbs [aria-current] { color: var(--ink); font-weight: 600; }

.pd { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 48px; align-items: start; }
.sk-main { aspect-ratio: 1; border-radius: var(--r-lg); }
.sk-info { display: flex; flex-direction: column; gap: 16px; }

/* gallery */
.gal { position: sticky; top: 130px; min-width: 0; }
.mainbox { position: relative; aspect-ratio: 1; overflow: hidden; display: flex; align-items: center; justify-content: center; background: radial-gradient(circle at 50% 42%, #fff, var(--tint)); }
.mainbox img { width: 100%; height: 100%; object-fit: cover; transition: transform .25s ease-out; }
.sale { position: absolute; top: 14px; left: 14px; font-size: 12px; }
@media (hover: hover) { .mainbox { cursor: zoom-in; } .mainbox.zooming img { transform: scale(1.9); } }
.thumbs { display: grid; grid-template-columns: repeat(auto-fill, minmax(72px, 88px)); gap: 10px; margin-top: 12px; }
.thumb { aspect-ratio: 1; padding: 0; border-radius: var(--r-md); border: 2px solid var(--line); background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; transition: border-color .2s; }
.thumb:hover { border-color: var(--leaf); }
.thumb.on { border-color: var(--forest); }
.thumb img { width: 100%; height: 100%; object-fit: cover; }

/* info */
.info { min-width: 0; }
.catlink { text-transform: uppercase; letter-spacing: .6px; }
.catlink:hover { background: var(--forest); color: #fff; }
.info h1 { margin: 12px 0 0; font-size: clamp(28px, 4.2vw, 42px); font-weight: 800; line-height: 1.1; letter-spacing: -.9px; }
.hindi { margin-top: 6px; font-size: 17px; color: var(--muted); }
.price { margin-top: 20px; display: flex; align-items: baseline; flex-wrap: wrap; gap: 6px 12px; }
.now { font-size: clamp(30px, 4vw, 38px); font-weight: 800; color: var(--forest); }
.was { font-size: 17px; color: #7A877E; text-decoration: line-through; }
.save { font-size: 12px; }
.tax { margin-top: 4px; font-size: 12px; color: var(--muted); }
.desc { margin: 18px 0 22px; font-size: 15px; line-height: 1.8; color: var(--muted); max-width: 540px; }
.lbl { font-size: 14px; font-weight: 700; margin-bottom: 10px; }
.sizes { display: flex; flex-wrap: wrap; gap: 10px; }
.pill.dim:not(.on) { color: #8A948C; }
.stock { margin-top: 12px; font-size: 13px; font-weight: 700; }
.stock.bad { color: var(--danger); } .stock.low { color: var(--amber); }

.buy { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 24px; }
.grow { flex: 1 1 150px; }
.qty { display: inline-flex; align-items: center; height: 48px; border: 1.5px solid var(--line); border-radius: 999px; background: #fff; }
.qty button { width: 46px; height: 46px; border: 0; background: none; color: var(--ink); font-size: 20px; border-radius: 50%; }
.qty button:hover:not(:disabled) { color: var(--forest); background: var(--tint); }
.qty button:disabled { opacity: .35; cursor: not-allowed; }
.qty output { min-width: 30px; text-align: center; font-weight: 700; }

.pin { margin-top: 28px; padding: 20px 22px; }
.pin h2 { margin: 0 0 12px; font-size: 15px; font-weight: 800; }
.pin form { display: flex; gap: 10px; }
.pin input { flex: 1; min-width: 0; height: 46px; padding: 0 16px; border: 1px solid #CFCBB6; border-radius: 999px; background: #fff; color: var(--ink); font-size: 14px; }
.pin input:focus { outline: none; border-color: var(--forest); box-shadow: 0 0 0 3px rgba(55, 146, 55, .28); }
.pmsg { margin-top: 12px; font-size: 13px; font-weight: 600; color: var(--muted); }
.pmsg.ok { color: #1F6B2A; } .pmsg.bad { color: var(--danger); }

.trust { list-style: none; margin: 22px 0 0; padding: 0; display: flex; flex-wrap: wrap; gap: 10px 22px; font-size: 13px; font-weight: 600; color: var(--muted); }
.trust li { display: inline-flex; align-items: center; gap: 6px; }
.trust svg { color: var(--leaf); }
.ship { margin-top: 18px; border-top: 1px solid var(--line); }
.ship summary { cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center; min-height: 48px; font-size: 14px; font-weight: 700; }
.ship summary::-webkit-details-marker { display: none; }
.ship summary::after { content: '+'; font-size: 22px; font-weight: 400; color: var(--forest); }
.ship[open] summary::after { content: '\2212'; }
.ship p { margin: 0 0 14px; font-size: 14px; line-height: 1.7; color: var(--muted); }
.ship a { color: var(--forest); font-weight: 600; text-decoration: underline; text-underline-offset: 3px; }

.rel { padding-top: 72px; }
.state { padding: 56px 24px; text-align: center; }
.state h1 { margin: 0; font-size: 32px; font-weight: 800; }
.state p { margin: 12px 0 22px; color: var(--muted); }

.steps { margin: 4px 0 0; padding-left: 20px; font-size: 14px; line-height: 1.7; color: var(--muted); }

/* mobile purchase bar */
.mbar { display: none; }

@media (max-width: 900px) {
  .pd { grid-template-columns: 1fr; gap: 28px; }
  .gal { position: static; }
  .mainbox { aspect-ratio: 1 / .9; }
  .rel { padding-top: 56px; }
  .mbar {
    display: flex; align-items: center; gap: 8px; position: fixed; z-index: 55; left: 0; right: 0; bottom: 0;
    padding: 10px var(--gutter) calc(10px + env(safe-area-inset-bottom, 0px)); background: rgba(255, 255, 255, .97);
    border-top: 1px solid var(--line); box-shadow: 0 -8px 24px rgba(16, 61, 44, .1);
    transform: translateY(110%); transition: transform .25s var(--ease);
  }
  .mbar.show { transform: none; }
  .mprice { display: flex; flex-direction: column; margin-right: auto; line-height: 1.2; min-width: 0; }
  .mprice strong { font-size: 18px; font-weight: 800; color: var(--forest); }
  .mprice span { font-size: 12px; color: var(--muted); }
  .buy .btn-sun.grow { flex: 1 1 0; min-width: 0; }   /* quantity + Add to Cart on one row */
  .buy a.grow, .buy span.grow { flex-basis: 100%; }
}
/* keep the footer's last line clear of the fixed purchase bar */
@media (max-width: 900px) { :deep(.foot) { padding-bottom: 72px; } }
@media (max-width: 600px) {
  .crumbs { padding: 14px 0 16px; }
  .pin { padding: 16px; }
}
@media (pointer: coarse) { .crumbs a { min-height: 40px; } }
</style>
