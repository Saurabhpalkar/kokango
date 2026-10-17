<template>
<div class="dcroot">
<div style="width:100%;background:var(--bg)">
  <AnnouncementBar />
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader />

    <h1 class="ph1" style="margin:24px 0 8px;font-size:42px;font-weight:800;letter-spacing:-1px">Track your order</h1>
    <p class="psub" style="margin:0 0 28px;font-size:16px;color:var(--muted)">Enter your order number and the email you used at checkout. <a v-if="!auth.isLoggedIn" href="/login?redirect=/orders" style="color:var(--green);font-weight:600">Have an account? Log in</a></p>

    <form v-if="!order" class="card pad" novalidate style="border-radius:16px;padding:32px;box-sizing:border-box;max-width:560px" @submit.prevent="submit">
      <div style="display:flex;flex-direction:column;gap:16px">
        <p v-if="errors.form" class="err" role="alert" style="margin:0;padding:12px 16px;border-radius:14px;background:var(--danger-bg)">{{ errors.form }}</p>
        <label class="field">Order number<input v-model="orderNo" placeholder="KKG-2026-000123" autocomplete="off" :class="{ bad: errors.order_no }"><span v-if="errors.order_no" class="err" role="alert">{{ errors.order_no }}</span></label>
        <label class="field">Email<input v-model="email" type="email" placeholder="you@example.com" autocomplete="email" :class="{ bad: errors.email }"><span v-if="errors.email" class="err" role="alert">{{ errors.email }}</span></label>
        <button type="submit" class="btn btn-sun" :disabled="busy" style="margin-top:4px">{{ busy ? 'Looking up...' : 'Track order' }}</button>
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

select{height:40px;background:#fff;border:1px solid var(--line);border-radius:999px;color:var(--ink);padding:0 16px;font-family:inherit;font-size:13px;font-weight:600}
@media (max-width:900px){.field input,select{font-size:16px!important}}
@media (max-width:600px){
.wrap{padding-left:16px!important;padding-right:16px!important}
.ph1{font-size:30px!important;letter-spacing:-.5px!important;margin:16px 0 8px!important}
.psub{font-size:15px!important;margin-bottom:20px!important;line-height:1.6}
.card{border-radius:14px!important}
.pad{padding:20px!important}
}
</style>
