<script setup>
// One product card used on the home page, the shop, the wishlist and the "you may also like" row.
// Hover (or always, on touch screens) shows three actions: add to cart, quick view and wishlist.
import { computed, ref } from 'vue'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import { useWishlistStore } from '@/stores/wishlist'
import { useQuickViewStore } from '@/stores/quickview'
import { inrp } from '@/utils/format'
import { hoverPhoto } from '@/content/productPhotos'
import * as catalogue from '@/services/catalogueService'

const props = defineProps({
  product: { type: Object, required: true },
  // kept for older callers; sizes are now chosen in the quick view
  sizes: { type: Boolean, default: true },
})

const cart = useCartStore()
const toast = useToastStore()
const wishlist = useWishlistStore()
const quick = useQuickViewStore()

const variants = computed(() => (props.product.variants || []).filter((v) => v.is_active !== false))
const variant = computed(() => catalogue.defaultVariant(props.product))
const inStock = computed(() => variants.value.some((v) => v.in_stock !== false && (v.stock == null || v.stock > 0)))
const price = computed(() => (variant.value ? variant.value.price_paise : props.product.min_price_paise))
const mrp = computed(() => (variant.value && variant.value.mrp_paise > variant.value.price_paise ? variant.value.mrp_paise : 0))
const off = computed(() => (mrp.value ? Math.round((1 - price.value / mrp.value) * 100) : 0))
const priceFrom = computed(() => variants.value.length > 1 && new Set(variants.value.map((v) => v.price_paise)).size > 1)
const badge = computed(() => (typeof props.product.badge === 'string' ? props.product.badge.trim() : ''))
const altImg = computed(() => hoverPhoto(props.product))
const saved = computed(() => wishlist.has(props.product.slug))

const busy = ref(false)
async function addToCart() {
  if (!inStock.value) { toast.show(`${props.product.name} is sold out`); return }
  // several pack sizes: let the shopper choose one in the quick view
  if (variants.value.length > 1 || !variant.value) { quick.open(props.product); return }
  if (busy.value) return
  busy.value = true
  try { if (await cart.add(variant.value.id, 1)) toast.show(`${props.product.name} added to cart`) } finally { busy.value = false }
}
</script>

<template>
  <article class="pc" :class="{ out: !inStock }">
    <div class="pc-media">
      <a :href="`/product/${product.slug}`" class="pc-img" :aria-label="`View ${product.name}`" tabindex="-1">
        <img class="main" :src="product.image_url" :alt="product.name" loading="lazy" decoding="async" width="600" height="600">
        <img v-if="altImg" class="alt" :src="altImg" alt="" loading="lazy" decoding="async" width="600" height="600">
      </a>
      <span v-if="off" class="pc-off">-{{ off }}%</span>
      <span v-if="badge" class="pc-badge">{{ badge }}</span>
      <span v-if="!inStock" class="pc-oos">Sold out</span>

      <div class="pc-actions">
        <button type="button" class="act" :disabled="!inStock || busy" :aria-busy="busy ? 'true' : 'false'" :aria-label="!inStock ? `${product.name} is sold out` : (variants.length > 1 ? `Choose a size of ${product.name}` : `Add ${product.name} to cart`)" :title="variants.length > 1 ? 'Select options' : 'Add to cart'" @click="addToCart">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h3l2.6 12.2a1.5 1.5 0 001.5 1.2h8.3a1.5 1.5 0 001.5-1.1L21.5 8H6.4"/></svg>
        </button>
        <button type="button" class="act" :aria-label="`Quick view ${product.name}`" title="Quick view" @click="quick.open(product)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
        <button type="button" class="act" :class="{ on: saved }" :aria-pressed="saved ? 'true' : 'false'" :aria-label="saved ? `Remove ${product.name} from wishlist` : `Save ${product.name} to wishlist`" :title="saved ? 'In your wishlist' : 'Add to wishlist'" @click="wishlist.toggle(product)">
          <svg width="18" height="18" viewBox="0 0 24 24" :fill="saved ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.4 1.1 5 3 1.6-1.9 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg>
        </button>
      </div>
    </div>

    <div class="pc-body">
      <h3 class="pc-name"><a :href="`/product/${product.slug}`">{{ product.name }}</a></h3>
      <div class="pc-price">
        <template v-if="mrp"><s class="was"><span class="sr-only">Original price </span>{{ inrp(mrp) }}</s><span class="now sale"><span v-if="priceFrom" class="from">From </span>{{ inrp(price) }}</span></template>
        <span v-else class="now"><span v-if="priceFrom" class="from">From </span>{{ inrp(price) }}</span>
      </div>
    </div>
  </article>
</template>

<style scoped>
.pc { position: relative; display: flex; flex-direction: column; min-width: 0; }

.pc-media { position: relative; aspect-ratio: 1 / 1; overflow: hidden; border-radius: 4px; background: #EDE8D9; }
.pc-img { position: absolute; inset: 0; display: block; }
.pc-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: opacity .5s var(--ease), transform .8s var(--ease); }
.pc-img .alt { opacity: 0; }
/* hover: the second photo fades in, the picture zooms slightly and darkens a touch behind the buttons */
.pc-media::after { content: ''; position: absolute; inset: 0; background: linear-gradient(0deg, rgba(10, 30, 20, .35) 0%, transparent 45%); opacity: 0; transition: opacity .35s var(--ease); pointer-events: none; }
.pc:hover .pc-img img { transform: scale(1.06); }
.pc:hover .pc-img .alt { opacity: 1; }
.pc:hover .pc-media::after { opacity: 1; }
.out .pc-img img { filter: grayscale(.5); }

.pc-off { position: absolute; z-index: 2; top: 12px; left: 12px; padding: 5px 9px; border-radius: 6px; background: #2E8B3A; color: #fff; font-size: 13px; font-weight: 800; }
.pc-badge { position: absolute; z-index: 2; top: 0; right: 0; padding: 4px 9px; border-radius: 0 0 0 8px; background: #2E8B3A; color: #fff; font-size: 10px; font-weight: 800; letter-spacing: .5px; text-transform: uppercase; }
.pc-oos { position: absolute; z-index: 2; left: 12px; bottom: 12px; padding: 5px 12px; border-radius: 999px; background: rgba(20, 35, 27, .85); color: #fff; font-size: 12px; font-weight: 700; }

.pc-actions { position: absolute; z-index: 3; left: 0; right: 0; bottom: 18px; display: flex; justify-content: center; gap: 12px; opacity: 0; transform: translateY(14px); transition: opacity .3s var(--ease), transform .3s var(--ease); }
.pc:hover .pc-actions, .pc:focus-within .pc-actions { opacity: 1; transform: none; }
.act { width: 50px; height: 50px; border: 0; border-radius: 50%; background: #fff; color: var(--ink); display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 6px 18px rgba(0, 0, 0, .18); transition: background .2s, color .2s, transform .2s; }
.act:hover:not(:disabled) { background: var(--forest); color: #fff; transform: translateY(-2px); }
.act.on { color: #E0453A; }
.act.on:hover { color: #fff; background: #E0453A; }
.act:disabled { opacity: .55; cursor: not-allowed; }

.pc-body { padding: 14px 2px 4px; }
.pc-name { margin: 0; font-size: 17px; font-weight: 600; line-height: 1.3; letter-spacing: -.1px; }
.pc-name a { color: var(--ink); }
.pc-name a:hover { color: var(--forest); }
.pc-price { margin-top: 6px; display: flex; flex-wrap: wrap; align-items: baseline; gap: 2px 10px; }
.was { font-size: 15px; font-weight: 600; color: #9AA19B; }
.now { font-size: 17px; font-weight: 700; color: var(--ink); }
.now.sale { color: #EF5B4C; }
.from { font-size: 12px; font-weight: 600; color: var(--muted); }

/* touch screens have no hover: keep the actions visible, a little smaller */
@media (hover: none) {
  .pc-actions { opacity: 1; transform: none; bottom: 10px; gap: 8px; }
  .pc-media::after { opacity: 1; background: linear-gradient(0deg, rgba(10, 30, 20, .28) 0%, transparent 35%); }
  .act { width: 40px; height: 40px; }
}
@media (max-width: 640px) {
  .pc-off { top: 8px; left: 8px; font-size: 11px; padding: 3px 7px; }
  .pc-badge { font-size: 9px; padding: 3px 7px; }
  .act { width: 38px; height: 38px; }
  .act svg { width: 16px; height: 16px; }
  .pc-body { padding: 10px 2px 2px; }
  .pc-name { font-size: 14.5px; }
  .was { font-size: 13px; }
  .now { font-size: 15px; }
}
</style>
