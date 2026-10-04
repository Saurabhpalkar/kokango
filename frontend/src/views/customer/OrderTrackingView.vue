<template>
<div class="dcroot">
<div style="width:100%;position:relative;overflow:hidden;background:var(--bg)">
  <AnnouncementBar />
  <div style="position:absolute;top:-220px;right:-160px;width:520px;height:520px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;top:520px;left:-240px;width:460px;height:460px;border-radius:50%;background:var(--tint)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader active="orders" orders-nav />

    <div v-if="loading" class="card pad" style="border-radius:36px;padding:56px 32px;box-sizing:border-box;text-align:center;max-width:640px;margin:48px auto 0;color:var(--muted)">Loading your order...</div>

    <div v-else-if="error" class="card pad" style="border-radius:40px;padding:56px 32px;box-sizing:border-box;text-align:center;max-width:640px;margin:48px auto 0">
      <div style="font-size:24px;font-weight:800">{{ notFound ? 'We could not find this order' : 'Something went wrong' }}</div>
      <p role="alert" style="margin:10px 0 24px;font-size:15px;color:var(--muted)">{{ error }}</p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap"><button type="button" class="btn" style="background:var(--mango);color:var(--ink)" @click="load">Try again</button><a href="/account" class="btn-line">My orders</a></div>
    </div>

    <div v-else-if="!order" class="card pad" style="border-radius:40px;padding:56px 32px;box-sizing:border-box;text-align:center;max-width:640px;margin:48px auto 0">
      <div style="font-size:24px;font-weight:800">You have no orders yet</div>
      <p style="margin:10px 0 24px;font-size:15px;color:var(--muted)">When you place an order, you can follow it here.</p>
      <a href="/products" class="btn" style="background:var(--mango);color:var(--ink)">Browse products</a>
    </div>

    <template v-else>
      <div v-if="orders.length > 1" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px 20px;margin:8px 0 0;font-size:13px;font-weight:600;color:var(--muted)">
        <label class="osel" style="display:flex;align-items:center;gap:10px">Showing order<select :value="order.order_no" @change="switchTo($event.target.value)"><option v-for="o in orders" :key="o.order_no" :value="o.order_no">{{ o.order_no }} &middot; {{ formatDate(o.placed_at) }}</option></select></label>
        <a href="/account" style="color:var(--green)">All orders &rarr;</a>
      </div>
      <OrderTracker :order="order" allow-cancel @updated="onUpdated" />
    </template>

    <SiteFooter :links="false" />
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
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { formatDate } from '@/utils/format'
import OrderTracker from '@/components/OrderTracker.vue'
import * as orderService from '@/services/orderService'
import { apiError } from '@/services/api'

const route = useRoute()
const router = useRouter()
const orders = ref([])
const order = ref(null)
const loading = ref(true)
const error = ref('')
const notFound = ref(false)

async function load() {
  loading.value = true; error.value = ''; notFound.value = false; order.value = null
  try {
    orders.value = (await orderService.list({ per_page: 50 })).items
    const no = route.query.order
    if (no) order.value = await orderService.show(String(no))
    else if (orders.value.length) order.value = await orderService.show(orders.value[0].order_no)
  } catch (e) {
    const er = apiError(e)
    notFound.value = er.status === 404
    error.value = notFound.value ? 'This order does not exist on your account.' : er.message
  } finally { loading.value = false }
}
const switchTo = (no) => router.push(`/orders?order=${no}`)
function onUpdated(o) {
  order.value = o
  const i = orders.value.findIndex((x) => x.order_no === o.order_no)
  if (i >= 0) orders.value[i] = o
}
onMounted(load)
watch(() => route.query.order, (n, o) => { if (n !== o) load() })
</script>

<style scoped>
.dcroot{--bg:#F8F7EF;--surface:#fff;--ink:#14301C;--muted:#566659;--line:#E6E2D0;--green:#0B5D1E;--leaf:#9ACD32;--mango:#FFB400;--amber:#9A5B00;--tint:#EAF3DD;--sun:#FFF1CC}
.dcroot{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans','Noto Sans Devanagari',system-ui,sans-serif}
a{color:var(--ink);text-decoration:none}a:hover{color:var(--green)}
button{font-family:inherit;cursor:pointer}
.card{background:var(--surface);border:1px solid var(--line);box-shadow:0 10px 30px rgba(20,48,28,0.06)}
.btn-line{background:transparent;color:var(--ink);border:1.5px solid var(--ink);border-radius:999px;padding:10px 22px;font-size:14px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.btn-line:hover{background:var(--ink);color:#fff}

.btn{background:var(--green);color:#fff;border:none;border-radius:999px;padding:12px 28px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.btn:hover{background:#08481a;color:#fff}
select{height:40px;background:#fff;border:1px solid var(--line);border-radius:999px;color:var(--ink);padding:0 16px;font-family:inherit;font-size:13px;font-weight:600}
select{max-width:100%;min-width:0}
@media (max-width:900px){select{font-size:16px!important;height:44px}}
@media (max-width:600px){
.wrap{padding-left:16px!important;padding-right:16px!important}
.card{border-radius:24px!important}
.pad{padding:32px 20px!important}
.osel{flex:1 1 100%;flex-wrap:wrap}
.osel select{flex:1 1 200px}
}
</style>
