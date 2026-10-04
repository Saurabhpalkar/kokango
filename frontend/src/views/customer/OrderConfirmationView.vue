<template>
<div class="dcroot">
<div style="width:100%;position:relative;overflow:hidden;background:var(--bg)">
  <AnnouncementBar />
  <div style="position:absolute;top:-220px;right:-160px;width:520px;height:520px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;top:420px;left:-240px;width:460px;height:460px;border-radius:50%;background:var(--tint)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader />

    <div v-if="loading" class="card pad" style="border-radius:40px;padding:56px 32px;box-sizing:border-box;text-align:center;max-width:640px;margin:48px auto 0;color:var(--muted)">Loading your order...</div>

    <div v-else-if="!order" class="card pad" style="border-radius:40px;padding:56px 32px;box-sizing:border-box;text-align:center;max-width:640px;margin:48px auto 0">
      <div style="font-size:24px;font-weight:800">{{ error ? 'We could not find this order' : 'No recent order to show' }}</div>
      <p style="margin:10px 0 24px;font-size:15px;color:var(--muted)">{{ error || 'When you place an order, your confirmation will appear here.' }}</p>
      <a href="/products" class="btn" style="background:var(--mango);color:var(--ink)">Browse products</a>
    </div>

    <section v-else style="max-width:760px;margin:16px auto 0">
      <div class="card cpad" style="border-radius:44px;padding:44px;box-sizing:border-box">
        <div style="text-align:center">
          <span style="width:64px;height:64px;border-radius:50%;background:var(--green);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:30px;font-weight:700">&#10003;</span>
          <h1 class="chead" style="margin:18px 0 6px;font-size:38px;font-weight:800;letter-spacing:-1px">Thank you, order placed</h1>
          <div style="font-size:15px;color:var(--muted);overflow-wrap:anywhere">A confirmation has been sent to {{ order.customer?.email }}</div>
        </div>

        <div class="cmeta" style="display:flex;flex-wrap:wrap;gap:12px 32px;justify-content:space-between;margin:28px 0 8px;padding:18px 22px;border-radius:24px;background:var(--tint);border:1px solid #cfe3b4;font-size:14px">
          <div><div style="font-size:12px;color:var(--muted);font-weight:600">Order number</div><div style="font-weight:800;font-size:17px;margin-top:2px">{{ order.order_no }}</div></div>
          <div><div style="font-size:12px;color:var(--muted);font-weight:600">Placed on</div><div style="font-weight:700;margin-top:4px">{{ formatDate(order.placed_at) }}</div></div>
          <div><div style="font-size:12px;color:var(--muted);font-weight:600">Expected delivery</div><div style="font-weight:700;margin-top:4px">{{ order.shipping_method === 'express' ? '1 to 2 days' : '3 to 5 days' }}</div></div>
          <div><div style="font-size:12px;color:var(--muted);font-weight:600">Payment</div><div style="font-weight:700;margin-top:4px;text-transform:capitalize">{{ order.payment_status }}</div></div>
        </div>

        <div style="margin-top:20px">
          <template v-for="it in order.items" :key="it.id">
            <div style="display:flex;align-items:center;gap:16px;padding:12px 0;border-top:1px solid var(--line)">
              <div style="flex:0 0 68px;height:68px;border-radius:16px;background:linear-gradient(160deg,#fff,var(--tint));border:1px solid var(--line);display:flex;align-items:center;justify-content:center;overflow:hidden"><img :src="it.image_url" alt="" style="height:100%;width:auto;object-fit:contain"></div>
              <div style="flex:1;min-width:0;overflow-wrap:anywhere;font-size:15px;font-weight:700">{{ it.name }}<div style="font-size:12px;color:var(--muted);font-weight:500">{{ it.size_label }} &middot; Qty {{ it.qty }}</div></div>
              <div style="font-weight:800;white-space:nowrap">{{ inrp(it.total_paise) }}</div>
            </div>
          </template>
        </div>

        <div style="border-top:1px dashed var(--line);margin-top:8px;padding-top:12px">
          <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;color:var(--muted)"><span>Subtotal</span><span>{{ inrp(order.subtotal_paise) }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;color:var(--muted)"><span>Shipping</span><span>{{ inrp(order.shipping_paise) }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;color:var(--muted)"><span>Tax (GST 5%)</span><span>{{ inrp(order.tax_paise) }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:14px 0 0;margin-top:8px;border-top:1px dashed var(--line);font-size:21px;font-weight:800"><span>{{ order.payment_status === 'paid' ? 'Total paid' : 'Total' }}</span><span style="color:var(--green)">{{ inrp(order.total_paise) }}</span></div>
        </div>

        <div class="cact" style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin-top:32px">
          <a :href="auth.isLoggedIn ? `/orders?order=${order.order_no}` : '/track-order'" class="btn" style="background:var(--mango);color:var(--ink)">Track order</a>
          <a href="/products" class="btn-line">Continue shopping</a>
        </div>
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
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { inrp, formatDate } from '@/utils/format'
import { useAuthStore } from '@/stores/auth'
import * as orderService from '@/services/orderService'
import { apiError } from '@/services/api'

const auth = useAuthStore()
const route = useRoute()
const order = ref(null)
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  const last = orderService.readLastOrder()
  const no = route.query.order || last?.order_no
  if (!no) { loading.value = false; return }
  const token = last && last.order_no === no ? last.token : undefined
  try { order.value = await orderService.show(no, token) }
  catch (e) { error.value = apiError(e).status === 404 ? 'We could not find that order.' : apiError(e).message }
  finally { loading.value = false }
})
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
.btn-line{background:transparent;color:var(--ink);border:1.5px solid var(--ink);border-radius:999px;padding:11px 26px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.btn-line:hover{background:var(--ink);color:#fff}
@media (max-width:600px){
.wrap{padding-left:16px!important;padding-right:16px!important}
.card{border-radius:24px!important}
.pad{padding:32px 20px!important}
.cpad{padding:24px 18px!important}
.chead{font-size:26px!important;letter-spacing:-.5px!important}
.cmeta{display:grid!important;grid-template-columns:1fr 1fr;padding:16px!important;border-radius:20px!important}
.cmeta>div{min-width:0}
.cact>a{flex:1 1 100%}
}
</style>
