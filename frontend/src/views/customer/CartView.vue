<template>
<div class="dcroot">
<div style="width:100%;position:relative;overflow:hidden;background:var(--bg)">
  <AnnouncementBar />
  <div style="position:absolute;top:-220px;right:-160px;width:520px;height:520px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;top:420px;left:-240px;width:460px;height:460px;border-radius:50%;background:var(--tint)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader active-icon="cart" />

    <h1 class="ph1" style="margin:24px 0 32px;font-size:42px;font-weight:800;letter-spacing:-1px">Your Cart <span style="font-size:16px;color:var(--muted);font-weight:500">&nbsp;{{ cart.items.length }} {{ cart.items.length === 1 ? 'item' : 'items' }}</span></h1>

    <section class="split" style="display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start">
      <div class="split-main" style="flex:1 1 640px;display:flex;flex-direction:column;gap:18px">
        <div v-if="!cart.loaded" class="card pad" style="border-radius:32px;padding:56px 28px;box-sizing:border-box;text-align:center;color:var(--muted)">Loading your cart...</div>
        <div v-else-if="!cart.items.length" class="card pad" style="border-radius:32px;padding:56px 28px;box-sizing:border-box;text-align:center"><div style="font-size:22px;font-weight:800">Your cart is empty</div><p style="margin:10px 0 24px;font-size:15px;color:var(--muted)">Looks like you have not added anything yet. Fresh products from the Konkan are waiting for you.</p><a href="/products" class="btn" style="background:var(--mango);color:var(--ink)">Browse products</a></div>
        <template v-for="it in cart.items" :key="it.id">
          <div class="card citem" style="display:flex;flex-wrap:wrap;align-items:center;gap:24px;border-radius:32px;padding:18px 28px 18px 18px;box-sizing:border-box">
            <a class="cimg" :href="`/product/${it.slug}`" style="flex:0 0 120px;height:120px;border-radius:22px;background:linear-gradient(160deg,#fff,var(--tint));display:flex;align-items:center;justify-content:center;overflow:hidden"><img :src="it.image_url" :alt="`${it.name} pack`" style="height:100%;width:auto;object-fit:contain"></a>
            <div class="cinfo" style="flex:1 1 200px">
              <a :href="`/product/${it.slug}`" style="display:block;font-size:19px;font-weight:700">{{ it.name }}</a>
              <div style="font-size:13px;color:var(--muted);margin-top:4px">{{ it.size_label }}</div>
              <div style="font-size:15px;color:var(--green);margin-top:8px;font-weight:700">{{ inrp(it.unit_price_paise) }}</div>
            </div>
            <div class="cqty" style="display:flex;align-items:center;border:1.5px solid var(--line);border-radius:999px;height:46px;background:#fff"><button type="button" aria-label="Decrease" @click="cart.setQty(it.id, it.qty - 1)" style="width:42px;height:46px;background:none;border:none;color:var(--ink);font-size:18px">&minus;</button><span style="width:28px;text-align:center;font-weight:700">{{ it.qty }}</span><button type="button" aria-label="Increase" @click="cart.setQty(it.id, it.qty + 1)" style="width:42px;height:46px;background:none;border:none;color:var(--ink);font-size:18px">+</button></div>
            <div class="ctot" style="flex:0 0 80px;text-align:right;font-size:18px;font-weight:800">{{ inrp(it.line_total_paise) }}</div>
            <button type="button" class="icon" :aria-label="`Remove ${it.name}`" @click="cart.remove(it.id)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M5 7h14M10 7V4h4v3M7 7l1 13h8l1-13"/></svg></button>
          </div>
        </template>
        <a href="/products" style="font-size:14px;font-weight:600;margin-top:4px;color:var(--green)">&larr; Continue shopping</a>
      </div>

      <aside class="card split-side pad" style="flex:0 0 390px;border-radius:40px;padding:32px;box-sizing:border-box">
        <h2 style="margin:0 0 20px;font-size:22px;font-weight:800">Order Summary</h2>
        <div style="display:flex;justify-content:space-between;padding:10px 0;font-size:15px;color:var(--muted)"><span>Subtotal</span><span style="color:var(--ink);font-weight:600">{{ inrp(cart.subtotalPaise) }}</span></div>
        <div style="display:flex;justify-content:space-between;padding:10px 0;font-size:15px;color:var(--muted)"><span>Shipping</span><span style="color:var(--ink);font-weight:600">{{ inrp(cart.shippingPaise) }}</span></div>
        <div style="display:flex;justify-content:space-between;padding:10px 0;font-size:15px;color:var(--muted)"><span>Tax (GST 5%)</span><span style="color:var(--ink);font-weight:600">{{ inrp(cart.taxPaise) }}</span></div>
        <div style="display:flex;justify-content:space-between;padding:18px 0 22px;margin-top:8px;border-top:1px dashed var(--line);font-size:21px;font-weight:800"><span>Total</span><span style="color:var(--green)">{{ inrp(cart.totalPaise) }}</span></div>
        <a v-if="cart.items.length" href="/checkout" class="btn" style="display:flex;width:100%;box-sizing:border-box;background:var(--mango);color:var(--ink)">Proceed to Checkout</a>
        <span v-else class="btn" aria-disabled="true" style="display:flex;width:100%;box-sizing:border-box;background:var(--mango);color:var(--ink);opacity:.45;cursor:not-allowed">Proceed to Checkout</span>
        <p style="margin:16px 0 0;font-size:12px;line-height:1.6;color:var(--muted);text-align:center">Shipping and tax are recalculated at checkout. Guest checkout is available.</p>
      </aside>
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
import { inrp } from '@/utils/format'
import { useCartStore } from '@/stores/cart'

const cart = useCartStore()
</script>

<style scoped>
.dcroot{--bg:#F8F7EF;--surface:#fff;--ink:#14301C;--muted:#566659;--line:#E6E2D0;--green:#0B5D1E;--leaf:#9ACD32;--mango:#FFB400;--amber:#9A5B00;--tint:#EAF3DD;--sun:#FFF1CC}
.dcroot{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans','Noto Sans Devanagari',system-ui,sans-serif}
a{color:var(--ink);text-decoration:none}a:hover{color:var(--green)}
button{font-family:inherit;cursor:pointer}
.card{background:var(--surface);border:1px solid var(--line);box-shadow:0 10px 30px rgba(20,48,28,0.06)}
.btn{background:var(--green);color:#fff;border:none;border-radius:999px;padding:12px 28px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:8px}
.btn:hover{background:#08481a;color:#fff}
span.btn:hover{background:var(--mango);color:var(--ink)}
.icon{width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;background:#fff;color:var(--ink);border:1px solid var(--line);border-radius:50%;padding:0;position:relative}
.icon:hover{border-color:var(--green);color:var(--green)}
@media (max-width:900px){
.split{gap:20px!important}
.split>.split-main{flex:1 1 100%!important;min-width:0!important}
.split>.split-side{flex:1 1 100%!important;min-width:0!important;width:100%}
.field input,.field select,select{font-size:16px!important}
}
@media (max-width:600px){
.wrap{padding-left:16px!important;padding-right:16px!important}
.ph1{font-size:30px!important;letter-spacing:-.5px!important;margin:16px 0 20px!important;overflow-wrap:anywhere}
.card{border-radius:24px!important}
.pad{padding:20px!important}
.citem{padding:14px!important;gap:10px 12px!important}
.cimg{flex:0 0 84px!important;height:84px!important;border-radius:18px!important}
.cinfo{flex:1 1 calc(100% - 100px)!important;min-width:0}
.cinfo a{overflow-wrap:anywhere}
.ctot{flex:1 1 0!important;min-width:0}
}
@media (min-width:901px) and (max-width:999px){
.split{gap:20px!important}
.split>.split-main{flex:1 1 100%!important;min-width:0!important}
.split>.split-side{flex:1 1 100%!important;min-width:0!important;width:100%}
}
@media (min-width:1000px) and (max-width:1199px){
.split>.split-main{flex:1 1 480px!important;min-width:0!important}
}
</style>
