<template>
<div>
    <div class="obanner" :style="`border-radius:28px;padding:20px 28px;margin:16px 0 28px;display:flex;align-items:center;gap:16px;background:${banner.bg};border:1px solid ${banner.edge}`">
      <span :style="`flex:0 0 auto;width:38px;height:38px;border-radius:50%;background:${banner.dot};color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700`">{{ banner.mark }}</span>
      <div><div style="font-size:18px;font-weight:800">{{ banner.title }}</div><div style="font-size:13px;color:var(--muted);margin-top:2px">A confirmation has been sent to {{ order.customer?.email }}</div></div>
    </div>

    <h1 class="oh1" style="margin:0 0 8px;font-size:38px;font-weight:800;letter-spacing:-1px">Order {{ order.order_no }}</h1>
    <div style="font-size:14px;color:var(--muted);margin-bottom:32px">Placed on {{ formatDate(order.placed_at) }} &middot; {{ paymentText }}</div>

    <section class="split" style="display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start">
      <div class="split-main" style="flex:1 1 640px;display:flex;flex-direction:column;gap:24px">
        <div class="card pad" style="border-radius:36px;padding:32px;box-sizing:border-box">
          <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;align-items:center;margin-bottom:28px">
            <h2 style="margin:0;font-size:22px;font-weight:800">Shipment Tracking</h2>
            <div style="font-size:13px;color:var(--muted);overflow-wrap:anywhere">Courier: {{ order.shipment?.courier || 'To be assigned' }} &middot; AWB: <span style="color:var(--ink);font-weight:700">{{ order.shipment?.awb || 'Assigned on dispatch' }}</span></div>
          </div>
          <div v-if="order.shipment" style="margin:-14px 0 22px;font-size:13px;color:var(--muted)">Shipment status: <span style="color:var(--ink);font-weight:700;text-transform:capitalize">{{ String(order.shipment.status).replace(/_/g, ' ') }}</span></div>
          <div style="display:flex;flex-direction:column">
            <template v-for="s in steps" :key="s.key">
              <div style="display:flex;gap:18px">
                <div style="display:flex;flex-direction:column;align-items:center">
                  <span :style="`width:30px;height:30px;border-radius:50%;background:${s.dot};border:2px solid ${s.edge};color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:14px;font-weight:700`">{{ s.mark }}</span>
                  <span :style="`width:2px;flex:1;min-height:30px;background:${s.line}`"></span>
                </div>
                <div style="padding-bottom:22px"><div :style="`font-size:16px;font-weight:700;color:${s.color}`">{{ s.label }}</div><div style="font-size:12px;color:var(--muted);margin-top:2px">{{ s.time }}</div></div>
              </div>
            </template>
          </div>
        </div>

        <div class="card pad" style="border-radius:36px;padding:32px;box-sizing:border-box">
          <h2 style="margin:0 0 16px;font-size:22px;font-weight:800">Items</h2>
          <template v-for="it in order.items" :key="it.id">
            <div style="display:flex;align-items:center;gap:16px;padding:12px 0;border-top:1px solid var(--line)">
              <div style="flex:0 0 68px;height:68px;border-radius:16px;background:linear-gradient(160deg,#fff,var(--tint));border:1px solid var(--line);display:flex;align-items:center;justify-content:center;overflow:hidden"><img :src="it.image_url" alt="" style="height:100%;width:auto;object-fit:contain"></div>
              <div style="flex:1;min-width:0;overflow-wrap:anywhere;font-size:15px;font-weight:700"><a v-if="it.product_slug" :href="`/product/${it.product_slug}`">{{ it.name }}</a><template v-else>{{ it.name }}</template><div style="font-size:12px;color:var(--muted);font-weight:500">{{ it.size_label }} &middot; Qty {{ it.qty }}</div></div>
              <div style="font-weight:800;white-space:nowrap">{{ inrp(it.total_paise) }}</div>
            </div>
          </template>
        </div>
      </div>

      <aside class="split-side" style="flex:0 0 390px;display:flex;flex-direction:column;gap:24px">
        <div class="card pad" style="border-radius:32px;padding:28px;box-sizing:border-box">
          <h2 style="margin:0 0 14px;font-size:18px;font-weight:800">Delivery Address</h2>
          <div style="font-size:14px;line-height:1.8;color:var(--muted)"><span style="color:var(--ink);font-weight:700">{{ order.address?.name }}</span><br>{{ order.address?.line1 }}<template v-if="order.address?.line2"><br>{{ order.address.line2 }}</template><br>{{ order.address?.city }}, {{ order.address?.state }} {{ order.address?.pincode }}<br>India<br>+91 {{ order.address?.phone }}</div>
        </div>
        <div class="card pad" style="border-radius:32px;padding:28px;box-sizing:border-box">
          <h2 style="margin:0 0 14px;font-size:18px;font-weight:800">Payment Summary</h2>
          <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;color:var(--muted)"><span>Subtotal</span><span>{{ inrp(order.subtotal_paise) }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;color:var(--muted)"><span>Shipping</span><span>{{ inrp(order.shipping_paise) }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;color:var(--muted)"><span>Tax (GST 5%)</span><span>{{ inrp(order.tax_paise) }}</span></div>
          <div v-if="order.discount_paise" style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;color:var(--muted)"><span>Discount</span><span>- {{ inrp(order.discount_paise) }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:14px 0 0;margin-top:8px;border-top:1px dashed var(--line);font-size:19px;font-weight:800"><span>{{ order.payment_status === 'paid' ? 'Total paid' : 'Total' }}</span><span style="color:var(--green)">{{ inrp(order.total_paise) }}</span></div>
        </div>
        <div class="oact" style="display:flex;gap:12px;flex-wrap:wrap"><a class="btn-line" :href="order.shipment?.tracking_url || COURIER_TRACK_URL" target="_blank" rel="noopener">Track on courier site</a><a class="btn-line" :href="WHATSAPP_URL" target="_blank" rel="noopener">Need help? WhatsApp</a></div>
        <div v-if="allowCancel && cancellable" class="card" style="border-radius:32px;padding:24px 28px;box-sizing:border-box">
          <div v-if="!confirming"><button type="button" class="btn-line" @click="confirming = true" style="border-color:#C0392B;color:#C0392B">Cancel order</button></div>
          <div v-else>
            <div style="font-size:14px;font-weight:700;margin-bottom:12px">Cancel this order? This cannot be undone.</div>
            <div style="display:flex;gap:12px;flex-wrap:wrap"><button type="button" class="btn-line" :disabled="cancelling" @click="doCancel" style="border-color:#C0392B;background:#C0392B;color:#fff">{{ cancelling ? 'Cancelling...' : 'Yes, cancel order' }}</button><button type="button" class="btn-line" :disabled="cancelling" @click="confirming = false">Keep order</button></div>
          </div>
          <p v-if="cancelError" role="alert" style="margin:12px 0 0;font-size:13px;font-weight:600;color:#C0392B">{{ cancelError }}</p>
        </div>
      </aside>
    </section>
</div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { WHATSAPP_URL, COURIER_TRACK_URL } from '@/utils/links'
import { inrp, formatDate, formatDateTime } from '@/utils/format'
import { isCancellable } from '@/utils/orderStatus'
import * as orderService from '@/services/orderService'
import { apiError } from '@/services/api'
import { useToastStore } from '@/stores/toast'

// Shared tracking details: used by the logged-in /orders page and the guest /track-order page.
const props = defineProps({ order: { type: Object, required: true }, allowCancel: { type: Boolean, default: false } })
const emit = defineEmits(['updated'])
const toast = useToastStore()

const confirming = ref(false)
const cancelling = ref(false)
const cancelError = ref('')
watch(() => props.order.order_no, () => { confirming.value = false; cancelError.value = '' })

const cancellable = computed(() => isCancellable(props.order))
async function doCancel() {
  cancelling.value = true; cancelError.value = ''
  try {
    const o = await orderService.cancel(props.order.order_no)
    toast.show('Order cancelled')
    confirming.value = false
    emit('updated', o)
  } catch (e) { cancelError.value = apiError(e).message }
  finally { cancelling.value = false }
}

const banner = computed(() => {
  const st = props.order.status
  const ok = { bg: 'var(--tint)', edge: '#cfe3b4', dot: 'var(--green)', mark: '✓' }
  if (st === 'cancelled') return { bg: '#FBE4E0', edge: '#f1c4bd', dot: '#C0392B', mark: '✕', title: 'This order was cancelled' }
  if (st === 'pending') return { bg: 'var(--sun)', edge: '#f0dca0', dot: 'var(--amber)', mark: '!', title: props.order.payment_status === 'failed' ? 'Payment failed for this order' : 'Waiting for payment' }
  if (st === 'processing') return { ...ok, title: 'Your order is being prepared' }
  if (st === 'shipped') return { ...ok, title: 'Your order is on its way' }
  if (st === 'delivered') return { ...ok, title: 'Your order has been delivered' }
  return { ...ok, title: 'Thank you, your order is confirmed' }
})

const paymentText = computed(() => {
  const o = props.order
  if (o.payment_status === 'paid') return o.payment?.gateway === 'razorpay' ? 'Paid online with Razorpay' : 'Paid online'
  if (o.payment_status === 'refunded') return 'Payment refunded'
  if (o.payment_status === 'failed') return 'Payment failed'
  return 'Payment pending'
})

const steps = computed(() => (props.order.timeline || []).map((t) => t.done
  ? { key: t.key, label: t.label, time: t.at ? formatDateTime(t.at) : 'Done', dot: '#0B5D1E', edge: '#0B5D1E', mark: '✓', line: '#0B5D1E', color: '#14301C' }
  : { key: t.key, label: t.label, time: t.key === 'delivered' && props.order.status !== 'cancelled' ? (props.order.shipping_method === 'express' ? 'Expected in 1 to 2 days' : 'Expected in 3 to 5 days') : 'Pending', dot: '#ffffff', edge: '#cfcab4', mark: '', line: '#E6E2D0', color: '#566659' }))
</script>

<style scoped>
a{color:var(--ink);text-decoration:none}a:hover{color:var(--green)}
.card{background:var(--surface);border:1px solid var(--line);box-shadow:0 10px 30px rgba(20,48,28,0.06)}
.btn-line{background:transparent;color:var(--ink);border:1.5px solid var(--ink);border-radius:999px;padding:10px 22px;font-size:14px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center;font-family:inherit;cursor:pointer}
.btn-line:hover{background:var(--ink);color:#fff}
.btn-line:disabled{opacity:.7;cursor:progress}
@media (max-width:900px){
.split{gap:20px!important}
.split>.split-main{flex:1 1 100%!important;min-width:0!important}
.split>.split-side{flex:1 1 100%!important;min-width:0!important}
}
@media (max-width:600px){
.obanner{padding:14px 16px!important;margin:12px 0 20px!important;border-radius:22px!important;gap:12px!important}
.obanner>div{min-width:0}
.oh1{font-size:26px!important;letter-spacing:-.5px!important;overflow-wrap:anywhere}
.card{border-radius:24px!important}
.pad{padding:20px!important}
.oact>a{flex:1 1 100%}
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
