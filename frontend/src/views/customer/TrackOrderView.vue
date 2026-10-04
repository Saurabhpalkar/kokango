<template>
<div class="dcroot">
<div style="width:100%;position:relative;overflow:hidden;background:var(--bg)">
  <AnnouncementBar />
  <div style="position:absolute;top:-220px;right:-160px;width:520px;height:520px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;top:520px;left:-240px;width:460px;height:460px;border-radius:50%;background:var(--tint)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader />

    <h1 class="ph1" style="margin:24px 0 8px;font-size:42px;font-weight:800;letter-spacing:-1px">Track your order</h1>
    <p class="psub" style="margin:0 0 28px;font-size:16px;color:var(--muted)">Enter your order number and the email you used at checkout. <a v-if="!auth.isLoggedIn" href="/login?redirect=/orders" style="color:var(--green);font-weight:600">Have an account? Log in</a></p>

    <form v-if="!order" class="card pad" novalidate style="border-radius:36px;padding:32px;box-sizing:border-box;max-width:560px" @submit.prevent="submit">
      <div style="display:flex;flex-direction:column;gap:16px">
        <p v-if="errors.form" class="err" role="alert" style="margin:0;padding:12px 16px;border-radius:14px;background:#FBE4E0">{{ errors.form }}</p>
        <label class="field">Order number<input v-model="orderNo" placeholder="KKG-2026-000123" autocomplete="off" :class="{ bad: errors.order_no }"><span v-if="errors.order_no" class="err" role="alert">{{ errors.order_no }}</span></label>
        <label class="field">Email<input v-model="email" type="email" placeholder="you@example.com" autocomplete="email" :class="{ bad: errors.email }"><span v-if="errors.email" class="err" role="alert">{{ errors.email }}</span></label>
        <button type="submit" class="btn" :disabled="busy" style="margin-top:4px;background:var(--mango);color:var(--ink)">{{ busy ? 'Looking up...' : 'Track order' }}</button>
      </div>
    </form>

    <template v-else>
      <button type="button" class="btn-line" style="margin-top:4px" @click="order = null">&larr; Track another order</button>
      <OrderTracker :order="order" />
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
import { ref, reactive } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import OrderTracker from '@/components/OrderTracker.vue'
import * as orderService from '@/services/orderService'
import { apiError } from '@/services/api'

const auth = useAuthStore()
const route = useRoute()
const orderNo = ref(String(route.query.order || ''))
const email = ref('')
const errors = reactive({})
const busy = ref(false)
const order = ref(null)

async function submit() {
  for (const k of Object.keys(errors)) delete errors[k]
  if (!orderNo.value.trim()) errors.order_no = 'Please enter your order number.'
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) errors.email = 'Enter the email used for the order.'
  if (Object.keys(errors).length) return
  busy.value = true
  try { order.value = await orderService.track(orderNo.value.trim().toUpperCase(), email.value.trim()) }
  catch (e) {
    const er = apiError(e)
    if (er.errors.order_no) errors.order_no = er.errors.order_no
    if (er.errors.email) errors.email = er.errors.email
    if (!Object.keys(errors).length) errors.form = er.status === 404 ? 'We could not find an order with that number and email. Please check both and try again.' : er.message
  } finally { busy.value = false }
}
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
.field{display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:var(--muted)}
.field input{height:50px;box-sizing:border-box;background:var(--bg);border:1px solid var(--line);border-radius:14px;padding:0 16px;color:var(--ink);font-family:inherit;font-size:14px;font-weight:500}
.field input:focus{outline:2px solid var(--green);outline-offset:1px}
.field input.bad{border-color:#C0392B}
.err{font-size:12px;font-weight:600;color:#C0392B}
.btn:disabled{opacity:.7;cursor:progress}
input::placeholder{color:#8a948c}
@media (max-width:900px){.field input,select{font-size:16px!important}}
@media (max-width:600px){
.wrap{padding-left:16px!important;padding-right:16px!important}
.ph1{font-size:30px!important;letter-spacing:-.5px!important;margin:16px 0 8px!important}
.psub{font-size:15px!important;margin-bottom:20px!important;line-height:1.6}
.card{border-radius:24px!important}
.pad{padding:20px!important}
}
</style>
