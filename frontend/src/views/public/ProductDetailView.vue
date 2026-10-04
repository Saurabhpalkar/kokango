<template>
<div class="dcroot">
<div style="width:100%;position:relative;overflow:hidden;background:var(--bg)">
  <AnnouncementBar />
  <div style="position:absolute;top:-200px;right:-160px;width:560px;height:560px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;top:500px;left:-220px;width:460px;height:460px;border-radius:50%;background:var(--tint)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader active="products" search />

    <div style="font-size:13px;color:var(--muted);padding:8px 0 24px;line-height:1.6"><a href="/">Home</a> &nbsp;/&nbsp; <a :href="`/products?category=${product ? product.category.slug : ''}`">{{ product ? product.category.name : 'Products' }}</a> &nbsp;/&nbsp; <span style="color:var(--ink);font-weight:600">{{ product ? product.name : (loading ? 'Loading...' : 'Not found') }}</span></div>

    <section v-if="loading" class="card" style="border-radius:40px;padding:64px 24px;text-align:center;box-sizing:border-box;color:var(--muted)">Loading product...</section>
    <section v-else-if="loadError" class="card" style="border-radius:40px;padding:64px 24px;text-align:center;box-sizing:border-box"><h1 style="margin:0;font-size:36px;font-weight:800">Something went wrong</h1><p role="alert" style="margin:12px 0 24px;color:var(--muted)">{{ loadError }}</p><button type="button" class="btn" @click="load">Try again</button></section>
    <section v-else-if="!product" class="card" style="border-radius:40px;padding:64px 24px;text-align:center;box-sizing:border-box"><h1 style="margin:0;font-size:36px;font-weight:800">Product not found</h1><p style="margin:12px 0 24px;color:var(--muted)">We could not find the product you are looking for.</p><a class="btn" href="/products">Browse all products</a></section>
    <section v-else class="pd" style="display:flex;flex-wrap:wrap;gap:56px;align-items:flex-start">
      <div class="pd-gal" style="flex:1 1 480px;max-width:600px;min-width:0">
        <div class="card pd-main" style="border-radius:48px;padding:24px;box-sizing:border-box"><div class="pd-mainbox" style="height:480px;display:flex;align-items:center;justify-content:center;border-radius:32px;background:linear-gradient(160deg,#fff,var(--tint));overflow:hidden"><img :src="mainImg" :alt="`${product.name} pack`" style="height:100%;width:auto;object-fit:contain"></div></div>
        <div v-if="thumbs.length > 1" class="pd-thumbs" style="display:flex;gap:14px;margin-top:16px">
          <template v-for="(t, t_i) in thumbs" :key="t_i">
            <button type="button" class="card" @click="mainImg = t.img" :aria-label="`Show ${t.alt}`" :style="`flex:1;height:96px;border-radius:22px;padding:8px;box-sizing:border-box;border:2px solid ${t.img === mainImg ? '#0B5D1E' : '#E6E2D0'};display:flex;align-items:center;justify-content:center;overflow:hidden;cursor:pointer`"><img :src="t.img" :alt="t.alt" style="height:100%;width:auto;object-fit:contain"></button>
          </template>
        </div>
      </div>

      <div class="pd-info" style="flex:1 1 440px;min-width:0">
        <span style="display:inline-block;background:var(--tint);color:var(--green);font-size:12px;font-weight:700;padding:6px 14px;border-radius:999px;letter-spacing:0.5px">{{ product.category.name.toUpperCase() }}</span>
        <h1 style="margin:14px 0 0;font-size:clamp(30px,8vw,46px);font-weight:800;line-height:1.08;letter-spacing:-1px">{{ product.name }}</h1>
        <div style="font-size:18px;color:var(--muted);margin-top:6px">{{ product.hindi_name }}</div>
        <div style="display:flex;align-items:center;gap:10px;margin-top:16px"><span style="color:var(--amber);letter-spacing:2px">&#9733;&#9733;&#9733;&#9733;&#9733;</span><span style="font-size:13px;color:var(--muted)">4.8 &nbsp;(126 reviews)</span></div>
        <div style="margin-top:20px;display:flex;align-items:baseline;gap:12px;flex-wrap:wrap"><span style="font-size:clamp(32px,8vw,40px);font-weight:800;color:var(--green)">{{ variant ? inrp(variant.price_paise) : '' }}</span><template v-if="savePct > 0"><span style="font-size:17px;color:#8a948c;text-decoration:line-through">{{ inrp(variant.mrp_paise) }}</span><span style="font-size:13px;font-weight:600;color:var(--green);background:var(--tint);padding:4px 10px;border-radius:999px">Save {{ savePct }}%</span></template></div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">Inclusive of all taxes</div>
        <p style="margin:20px 0;font-size:15px;line-height:1.8;color:var(--muted);max-width:520px">{{ product.description }}</p>

        <div style="font-size:14px;font-weight:700;margin-bottom:10px">Pack size</div>
        <div style="display:flex;gap:12px;flex-wrap:wrap"><template v-for="v in variants" :key="v.id"><button type="button" class="pill" :class="{ on: variant && v.id === variant.id }" :style="v.in_stock ? '' : 'opacity:.55'" @click="pickVariant(v)">{{ v.size_label }}</button></template></div>
        <div v-if="variant && !variant.in_stock" role="status" style="margin-top:12px;font-size:13px;font-weight:700;color:#C0392B">Out of stock</div>
        <div v-else-if="variant && variant.stock < 10" role="status" style="margin-top:12px;font-size:13px;font-weight:700;color:var(--amber)">Only {{ variant.stock }} left</div>

        <div class="pd-buy" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-top:28px">
          <div class="card" style="display:flex;align-items:center;border-radius:999px;height:48px;box-shadow:none"><button type="button" aria-label="Decrease" @click="qty = Math.max(1, qty - 1)" style="width:46px;height:48px;background:none;border:none;color:var(--ink);font-size:20px">&minus;</button><span style="width:34px;text-align:center;font-weight:700">{{ qty }}</span><button type="button" aria-label="Increase" @click="qty = Math.min(maxQty, qty + 1)" style="width:46px;height:48px;background:none;border:none;color:var(--ink);font-size:20px">+</button></div>
          <button type="button" class="btn btn-sun" :disabled="!canBuy || busy" @click="addToCart">{{ canBuy ? 'Add to Cart' : 'Out of stock' }}</button>
          <a v-if="canBuy" href="/checkout" class="btn" @click.prevent="buyNow">Buy Now</a>
          <span v-else class="btn" aria-disabled="true" style="opacity:.45;cursor:not-allowed">Buy Now</span>
        </div>

        <div class="card pd-pin" style="margin-top:32px;border-radius:28px;padding:22px 26px;box-sizing:border-box">
          <div style="font-size:14px;font-weight:700;margin-bottom:12px">Check delivery</div>
          <form style="display:flex" @submit.prevent="checkPin"><label for="pin" style="position:absolute;left:-9999px">Pincode</label><input id="pin" v-model="pin" inputmode="numeric" maxlength="6" placeholder="Enter 6-digit pincode" style="flex:1;min-width:0;height:46px;box-sizing:border-box;background:var(--bg);border:1px solid var(--line);border-right:none;border-radius:999px 0 0 999px;padding:0 20px;color:var(--ink);font-size:14px"><button type="submit" class="btn" style="border-radius:0 999px 999px 0;padding:0 24px;height:46px">Check</button></form>
          <div v-if="pinState === 'checking'" style="margin-top:12px;font-size:13px;font-weight:600;color:var(--muted)">Checking...</div>
          <div v-else-if="pinState === 'ok'" style="margin-top:12px;font-size:13px;font-weight:600;color:var(--green)">&#10003; Delivery available to {{ pinInfo.city ? pinInfo.city + ', ' + pinInfo.state : pinInfo.pincode }}. Estimated {{ stdDays }} days.</div>
          <div v-else-if="pinState === 'no'" role="alert" style="margin-top:12px;font-size:13px;font-weight:600;color:#C0392B">Sorry, we do not deliver to {{ pinInfo.pincode }} yet.</div>
          <div v-else-if="pinState === 'error'" role="alert" style="margin-top:12px;font-size:13px;font-weight:600;color:#C0392B">{{ pinMsg }}</div>
          <div v-else-if="pinState === 'bad'" role="alert" style="margin-top:12px;font-size:13px;font-weight:600;color:#C0392B">Enter a valid 6-digit pincode</div>
        </div>

        <div class="pd-trust" style="display:flex;gap:24px;flex-wrap:wrap;margin-top:24px;font-size:13px;font-weight:600;color:var(--muted)"><span>&#10003; Fresh small batches</span><span>&#10003; Secure Razorpay payment</span><span>&#10003; Easy returns</span></div>
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
import { useRouter, useRoute } from 'vue-router'
import { ref, computed, watch } from 'vue'
import { useToastStore } from '@/stores/toast'
import { inrp } from '@/utils/format'
import { apiError } from '@/services/api'
import * as catalogue from '@/services/catalogueService'
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

const variants = computed(() => (product.value?.variants || []).filter((v) => v.is_active !== false))
const thumbs = computed(() => (product.value?.images?.length ? product.value.images : [product.value?.image_url].filter(Boolean)).map((img) => ({ img, alt: `${product.value.name} pack` })))
const canBuy = computed(() => !!variant.value && variant.value.in_stock && variant.value.stock > 0)
const maxQty = computed(() => Math.max(1, Math.min(20, variant.value ? variant.value.stock : 20)))
const savePct = computed(() => {
  const v = variant.value
  return v && v.mrp_paise && v.mrp_paise > v.price_paise ? Math.round((1 - v.price_paise / v.mrp_paise) * 100) : 0
})
const stdDays = computed(() => pinInfo.value.methods?.find((m) => m.code === 'standard')?.days || '3 to 5')

function pickVariant(v) { variant.value = v; qty.value = Math.min(qty.value, Math.max(1, Math.min(20, v.stock))) }

async function load() {
  loading.value = true; loadError.value = ''; product.value = null
  try {
    const p = await catalogue.product(route.params.slug)
    product.value = p
    variant.value = catalogue.defaultVariant(p)
    qty.value = 1
    mainImg.value = p.image_url || p.images?.[0] || ''
    pin.value = ''; pinState.value = ''
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
.dcroot{--bg:#F8F7EF;--surface:#fff;--ink:#14301C;--muted:#566659;--line:#E6E2D0;--green:#0B5D1E;--leaf:#9ACD32;--mango:#FFB400;--amber:#9A5B00;--tint:#EAF3DD;--sun:#FFF1CC}
.dcroot{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans','Noto Sans Devanagari',system-ui,sans-serif}
a{color:var(--ink);text-decoration:none}a:hover{color:var(--green)}
button{font-family:inherit;cursor:pointer}
.card{background:var(--surface);border:1px solid var(--line);box-shadow:0 10px 30px rgba(20,48,28,0.06)}
.btn{background:var(--green);color:#fff;border:none;border-radius:999px;padding:12px 28px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:8px}
.btn:hover{background:#08481a;color:#fff}
.btn:disabled{opacity:.5;cursor:not-allowed}
.btn-sun{background:var(--mango);color:var(--ink)}.btn-sun:hover{background:#ffc22e;color:var(--ink)}
.btn-line{background:transparent;color:var(--ink);border:1.5px solid var(--ink);border-radius:999px;padding:11px 26px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.btn-line:hover{background:var(--ink);color:#fff}
.pill{background:#fff;color:var(--ink);border:1.5px solid var(--line);border-radius:999px;padding:10px 22px;font-size:14px;font-weight:600;min-height:44px}
.pill.on{border-color:var(--green);color:var(--green);background:var(--tint)}
input{font-family:inherit}input::placeholder{color:#7b8a7e}

/* ---- responsive ---- */
img{max-width:100%}
@media (max-width:600px){.wrap{padding-left:16px!important;padding-right:16px!important}}

@media (max-width:900px){.pd{gap:36px!important}.pd-gal{flex-basis:100%!important;max-width:none!important}.pd-info{flex-basis:100%!important}}
@media (max-width:600px){
  .pd{gap:28px!important}
  .pd-main{padding:14px!important;border-radius:32px!important}
  .pd-mainbox{height:min(380px,92vw)!important;border-radius:22px!important}
  .pd-thumbs{gap:10px!important}
  .pd-thumbs .card{height:72px!important;border-radius:16px!important}
  .pd-buy{gap:10px!important}
  .pd-buy .btn{flex:1 1 auto;padding-left:20px;padding-right:20px}
  .pd-pin{padding:18px!important}
  .pd-trust{gap:10px 18px!important}
  .pill{min-height:44px}
}
</style>
