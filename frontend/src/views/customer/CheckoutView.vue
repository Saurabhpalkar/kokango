<template>
<div class="dcroot">
<div style="width:100%;background:var(--bg)">
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader variant="minimal">
      <span style="font-size:13px;font-weight:600;color:var(--green);background:var(--tint);padding:8px 16px;border-radius:999px;white-space:nowrap">&#128274; Secure checkout</span>
    </SiteHeader>

    <div class="stepbar" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin:16px 0 36px;font-size:14px;font-weight:600">
      <span style="display:flex;align-items:center;gap:8px"><span style="width:30px;height:30px;border-radius:50%;background:var(--green);color:#fff;display:inline-flex;align-items:center;justify-content:center">1</span>Address</span>
      <span class="stsp" style="width:40px;height:2px;background:var(--line)"></span>
      <span style="display:flex;align-items:center;gap:8px;color:var(--muted)"><span style="width:30px;height:30px;border-radius:50%;border:1.5px solid var(--line);background:#fff;display:inline-flex;align-items:center;justify-content:center">2</span>Shipping</span>
      <span class="stsp" style="width:40px;height:2px;background:var(--line)"></span>
      <span style="display:flex;align-items:center;gap:8px;color:var(--muted)"><span style="width:30px;height:30px;border-radius:50%;border:1.5px solid var(--line);background:#fff;display:inline-flex;align-items:center;justify-content:center">3</span>Payment</span>
    </div>

    <div v-if="!cart.loaded" class="card pad" style="border-radius:16px;padding:56px 32px;box-sizing:border-box;text-align:center;max-width:640px;margin:0 auto;color:var(--muted)">Loading your cart...</div>

    <div v-else-if="!cart.items.length" class="card pad" style="border-radius:16px;padding:56px 32px;box-sizing:border-box;text-align:center;max-width:640px;margin:0 auto">
      <div style="font-size:22px;font-weight:800">Your cart is empty</div>
      <p style="margin:10px 0 24px;font-size:15px;color:var(--muted)">Add something from our shop before checking out.</p>
      <a href="/products" class="btn btn-sun">Browse products</a>
    </div>

    <form v-else novalidate @submit.prevent="submit">
    <section class="split" style="display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start">
      <div class="split-main" style="flex:1 1 640px;display:flex;flex-direction:column;gap:24px">
        <div class="card pad" style="border-radius:16px;padding:32px;box-sizing:border-box">
          <h2 style="margin:0 0 20px;font-size:22px;font-weight:800">Shipping Address</h2>
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr));gap:16px">
            <label v-if="auth.isLoggedIn && saved.length" class="field" style="grid-column:1/-1">Saved addresses<select v-model="savedId" @change="applySaved"><option value="">Enter a new address</option><option v-for="a in saved" :key="a.id" :value="String(a.id)">{{ a.name }} &middot; {{ a.line1 }}, {{ a.city }} {{ a.pincode }}{{ a.is_default ? ' (default)' : '' }}</option></select></label>
            <label class="field">Full name<input v-model="f.name" placeholder="Rohan Patil" autocomplete="name" :class="{ bad: errors.name }"><span v-if="errors.name" class="err" role="alert">{{ errors.name }}</span></label>
            <label class="field">Mobile number<input v-model="f.mobile" placeholder="98765 43210" inputmode="numeric" autocomplete="tel" :class="{ bad: errors.mobile }"><span v-if="errors.mobile" class="err" role="alert">{{ errors.mobile }}</span></label>
            <label class="field">Email<input v-model="f.email" placeholder="rohan@example.com" autocomplete="email" :class="{ bad: errors.email }"><span v-if="errors.email" class="err" role="alert">{{ errors.email }}</span></label>
            <label class="field">Pincode<input v-model="f.pincode" placeholder="415612" inputmode="numeric" maxlength="6" :class="{ bad: errors.pincode || (svc && !svc.serviceable) }"><span v-if="errors.pincode" class="err" role="alert">{{ errors.pincode }}</span><span v-else-if="svcLoading" class="hint">Checking delivery...</span><span v-else-if="svc && svc.serviceable" class="hint ok">&#10003; We deliver to {{ svc.city ? svc.city + ', ' + svc.state : 'this pincode' }}</span><span v-else-if="svc" class="err" role="alert">Sorry, we do not deliver to this pincode yet.</span></label>
            <label class="field" style="grid-column:1/-1">Address line<input v-model="f.line" placeholder="House no., street, area" autocomplete="street-address" :class="{ bad: errors.line }"><span v-if="errors.line" class="err" role="alert">{{ errors.line }}</span></label>
            <label class="field">City<input v-model="f.city" placeholder="Ratnagiri" :class="{ bad: errors.city }"><span v-if="errors.city" class="err" role="alert">{{ errors.city }}</span></label>
            <label class="field">District<input v-model="f.district" placeholder="Ratnagiri"></label>
            <label class="field">State<input v-model="f.state" placeholder="Maharashtra" :class="{ bad: errors.state }"><span v-if="errors.state" class="err" role="alert">{{ errors.state }}</span></label>
            <label class="field">Country<input v-model="f.country" readonly></label>
          </div>
          <label v-if="auth.isLoggedIn && !savedId" style="min-height:44px;display:flex;gap:10px;align-items:center;margin-top:18px;font-size:14px;font-weight:500;color:var(--muted)"><input type="checkbox" v-model="saveAddr" style="accent-color:#103D2C;width:18px;height:18px">Save this address to my account</label>
        </div>

        <div class="card pad" style="border-radius:16px;padding:32px;box-sizing:border-box">
          <h2 style="margin:0 0 20px;font-size:22px;font-weight:800">Shipping Method</h2>
          <div style="display:flex;flex-direction:column;gap:12px">
            <label v-for="m in methodList" :key="m.code" class="opt" :class="{ on: method === m.code }"><input type="radio" name="ship" :value="m.code" v-model="method" style="accent-color:#103D2C"><span style="flex:1;min-width:0;font-weight:600">{{ m.label }} <span style="color:var(--muted);font-weight:500">&middot; {{ m.days }} days</span></span><span style="font-weight:800">{{ m.price_paise === 0 ? 'Free' : inrp(m.price_paise) }}</span></label>
          </div>
        </div>

        <div class="card pad" style="border-radius:16px;padding:32px;box-sizing:border-box">
          <h2 style="margin:0 0 20px;font-size:22px;font-weight:800">Payment</h2>
          <div style="display:flex;flex-direction:column;gap:12px">
            <label class="opt" :class="{ on: payMethod === 'online' }"><input type="radio" name="pay" value="online" v-model="payMethod" style="accent-color:#103D2C"><span style="flex:1;min-width:0;font-weight:600">Pay online with Razorpay <span style="color:var(--muted);font-weight:500">&middot; UPI, cards, netbanking, wallets</span></span></label>
            <label v-if="codAvailable" class="opt" :class="{ on: payMethod === 'cod' }"><input type="radio" name="pay" value="cod" v-model="payMethod" style="accent-color:#103D2C"><span style="flex:1;min-width:0;font-weight:600">Cash on Delivery <span style="color:var(--muted);font-weight:500">&middot; pay in cash when your parcel arrives</span></span></label>
          </div>
        </div>
      </div>

      <aside class="card split-side pad" style="flex:0 0 390px;border-radius:20px;padding:32px;box-sizing:border-box">
        <h2 style="margin:0 0 16px;font-size:22px;font-weight:800">Order Summary</h2>
        <template v-for="it in cart.items" :key="it.id">
          <div style="display:flex;align-items:center;gap:14px;padding:10px 0">
            <div style="flex:0 0 60px;height:60px;border-radius:14px;background:linear-gradient(160deg,#fff,var(--tint));border:1px solid var(--line);display:flex;align-items:center;justify-content:center;overflow:hidden"><img :src="it.image_url" alt="" style="height:100%;width:auto;object-fit:contain"></div>
            <div style="flex:1;min-width:0;overflow-wrap:anywhere;font-size:14px;font-weight:600">{{ it.name }}<div style="font-size:12px;color:var(--muted);font-weight:500">{{ it.size_label }} &middot; Qty {{ it.qty }}</div></div>
            <div style="font-size:14px;font-weight:700;white-space:nowrap">{{ inrp(it.line_total_paise) }}</div>
          </div>
        </template>
        <div class="coupon">
          <form v-if="!couponApplied" class="coupon-f" novalidate @submit.prevent="applyCoupon">
            <label class="sr-only" for="coupon">Coupon code</label>
            <input id="coupon" v-model="couponInput" placeholder="Coupon code" autocomplete="off" autocapitalize="characters" :aria-invalid="couponErr ? 'true' : 'false'" aria-describedby="coupon-msg">
            <button type="submit" class="btn btn-sm" :disabled="couponBusy || !couponInput.trim()">{{ couponBusy ? 'Checking…' : 'Apply' }}</button>
          </form>
          <div v-else class="coupon-on"><span><b>{{ couponApplied }}</b> applied{{ quote?.coupon?.description ? ': ' + quote.coupon.description : '' }}</span><button type="button" class="link-btn" @click="removeCoupon">Remove</button></div>
          <p id="coupon-msg" class="coupon-msg" :class="{ bad: couponErr }" role="status">{{ couponErr || couponNote }}</p>
        </div>
        <div style="border-top:1px dashed var(--line);margin-top:12px;padding-top:12px">
          <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:14px;color:var(--muted)"><span>Subtotal</span><span style="color:var(--ink);font-weight:600">{{ inrp(sum.subtotal) }}</span></div>
          <div v-if="sum.discount" style="display:flex;justify-content:space-between;padding:8px 0;font-size:14px;color:#1E7A34"><span>Coupon {{ couponApplied }}</span><span style="font-weight:700">&minus; {{ inrp(sum.discount) }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:14px;color:var(--muted)"><span>Shipping</span><span style="color:var(--ink);font-weight:600">{{ inrp(sum.shipping) }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:14px;color:var(--muted)"><span>Tax (GST 5%)</span><span style="color:var(--ink);font-weight:600">{{ inrp(sum.tax) }}</span></div>
          <div style="display:flex;justify-content:space-between;padding:16px 0 20px;font-size:21px;font-weight:800"><span>Total</span><span style="color:var(--green)">{{ inrp(sum.total) }}</span></div>
        </div>
        <button type="submit" class="btn paybtn btn-sun" :disabled="processing" style="display:flex;width:100%;box-sizing:border-box">{{ processing ? (payMethod === 'cod' ? 'Placing your order…' : 'Processing payment…') : (payMethod === 'cod' ? `Place order · pay ${inrp(sum.total)} on delivery` : `Pay ${inrp(sum.total)} with Razorpay`) }}</button>
        <p v-if="serverError" class="err" role="alert" style="margin:12px 0 0;text-align:center;font-size:13px">{{ serverError }}</p>
        <p v-if="hasErrors" class="err" role="alert" style="margin:12px 0 0;text-align:center">Please fix the highlighted fields above.</p>
        <p style="margin:14px 0 0;font-size:12px;color:var(--muted);text-align:center">By placing your order you agree to our <a href="/policy/terms" style="color:inherit">Terms</a> and <a href="/policy/refund" style="color:inherit">Refund Policy</a>.</p>
      </aside>
    </section>
    </form>

    <SiteFooter :links="false" />
  </div>
</div>
</div>
</template>

<script setup>
import SiteHeader from '@/components/SiteHeader.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import { reactive, ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { inrp } from '@/utils/format'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import * as checkoutService from '@/services/checkoutService'
import * as shippingService from '@/services/shippingService'
import * as addressService from '@/services/addressService'
import * as orderService from '@/services/orderService'
import * as catalogue from '@/services/catalogueService'
import { apiError } from '@/services/api'

const router = useRouter()
const cart = useCartStore()
const auth = useAuthStore()

const f = reactive({
  name: auth.user?.name || '', mobile: auth.user?.phone || '', email: auth.user?.email || '', pincode: '',
  line: '', city: '', district: '', state: '', country: 'India',
})
const method = ref('standard')
const payMethod = ref('online')
const errors = reactive({})
const processing = ref(false)
const serverError = ref('')
const saveAddr = ref(false)
const saved = ref([])
const savedId = ref('')
const settings = ref(null)
const quote = ref(null)
const svc = ref(null)
const svcLoading = ref(false)

const hasErrors = computed(() => Object.keys(errors).length > 0)

// ---- shipping methods: real prices from the pincode lookup, else the store settings, else the cart estimate
const methodList = computed(() => {
  if (svc.value?.serviceable && svc.value.methods?.length) return svc.value.methods
  const st = settings.value
  return [
    { code: 'standard', label: 'Standard delivery', days: '3 to 5', price_paise: cart.shippingPaise },
    { code: 'express', label: 'Express delivery', days: '1 to 2', price_paise: st ? st.shipping_express_paise : 12000 },
  ]
})
watch(methodList, (list) => { if (!list.some((m) => m.code === method.value)) method.value = list[0]?.code || 'standard' })

// ---- order summary from the server quote (re-quoted when the method or the cart changes)
const sum = computed(() => quote.value
  ? { subtotal: quote.value.subtotal_paise, discount: quote.value.discount_paise || 0, shipping: quote.value.shipping_paise, tax: quote.value.tax_paise, total: quote.value.total_paise }
  : { subtotal: cart.subtotalPaise, shipping: cart.shippingPaise, tax: cart.taxPaise, total: cart.totalPaise })
// Cash on Delivery is offered when the shop allows it and the order is not above the shop's COD limit.
const codAvailable = computed(() => {
  const st = settings.value
  if (!st || !st.cod_enabled) return false
  return !st.cod_max_paise || sum.value.total <= st.cod_max_paise
})
watch(codAvailable, (ok) => { if (!ok && payMethod.value === 'cod') payMethod.value = 'online' })
let quoteSeq = 0
// ---- coupon: the server checks the code and computes the discount; the page only shows the result
const couponInput = ref('')
const couponApplied = ref('')
const couponErr = ref('')
const couponNote = ref('')
const couponBusy = ref(false)
async function refreshQuote() {
  if (!cart.items.length) { quote.value = null; return }
  const seq = ++quoteSeq
  try { const q = await checkoutService.quote(method.value, couponApplied.value); if (seq === quoteSeq) quote.value = q }
  catch (e) {
    if (seq !== quoteSeq) return
    const err = apiError(e)
    // the cart changed and the coupon no longer applies: drop it, keep the order going
    if (couponApplied.value && err.errors.coupon_code) { couponErr.value = err.errors.coupon_code; couponApplied.value = ''; refreshQuote(); return }
    quote.value = null
  }
}
async function applyCoupon() {
  const code = couponInput.value.trim().toUpperCase()
  if (!code) return
  couponBusy.value = true; couponErr.value = ''; couponNote.value = ''
  try {
    const q = await checkoutService.quote(method.value, code)
    ++quoteSeq; quote.value = q; couponApplied.value = q.coupon?.code || code; couponInput.value = ''
    couponNote.value = 'You save ' + inrp(q.discount_paise) + '.'
  } catch (e) { const err = apiError(e, 'This coupon could not be applied.'); couponErr.value = err.errors.coupon_code || err.message }
  finally { couponBusy.value = false }
}
function removeCoupon() { couponApplied.value = ''; couponNote.value = ''; couponErr.value = ''; refreshQuote() }
watch([method, () => cart.cart], refreshQuote, { immediate: true })

// ---- pincode lookup (debounced, 6 digits)
let pinTimer = null
let pinSeq = 0
watch(() => f.pincode, (v) => {
  clearTimeout(pinTimer)
  const pin = String(v || '').trim()
  delete errors.pincode; serverError.value = ""
  svc.value = null
  if (!/^\d{6}$/.test(pin)) { svcLoading.value = false; return }
  svcLoading.value = true
  const seq = ++pinSeq
  pinTimer = setTimeout(async () => {
    try {
      const r = await shippingService.serviceability(pin)
      if (seq !== pinSeq) return
      svc.value = r
      if (r.serviceable) {
        if (!f.city.trim() && r.city) f.city = r.city
        if (!f.state.trim() && r.state) f.state = r.state
      }
    } catch (e) { if (seq === pinSeq) svc.value = null }
    finally { if (seq === pinSeq) svcLoading.value = false }
  }, 400)
})
onBeforeUnmount(() => clearTimeout(pinTimer))

// ---- saved addresses (logged-in users)
function fillFrom(a) {
  f.name = a.name || f.name; f.mobile = a.phone || f.mobile
  f.line = a.line1 || ''; f.district = a.line2 || ''; f.city = a.city || ''; f.state = a.state || ''; f.pincode = a.pincode || ''
}
function applySaved() {
  const a = saved.value.find((x) => String(x.id) === savedId.value)
  if (a) fillFrom(a)
}
onMounted(async () => {
  catalogue.publicSettings().then((s) => { settings.value = s }).catch(() => {})
  if (auth.isLoggedIn) {
    try {
      saved.value = await addressService.list()
      const def = saved.value.find((a) => a.is_default)
      if (def && !f.line) { savedId.value = String(def.id); fillFrom(def) }
    } catch (e) { /* the form still works without saved addresses */ }
  }
})

function validate() {
  for (const k of Object.keys(errors)) delete errors[k]
  if (!f.name.trim()) errors.name = 'Please enter your full name.'
  if (!/^\d{10}$/.test(f.mobile.replace(/[\s-]/g, ''))) errors.mobile = 'Enter a 10 digit mobile number.'
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email.trim())) errors.email = 'Enter a valid email address.'
  if (!f.line.trim()) errors.line = 'Please enter your address.'
  if (!f.city.trim()) errors.city = 'Please enter your city.'
  if (!f.state.trim()) errors.state = 'Please enter your state.'
  if (!/^\d{6}$/.test(f.pincode.trim())) errors.pincode = 'Enter a 6 digit pincode.'
  return !hasErrors.value
}

// ---- payment helpers
const payErr = (m) => Object.assign(new Error(m), { payment: true })
const sleep = (ms) => new Promise((r) => setTimeout(r, ms))
let razorpayLoading = null
function loadRazorpay() {
  if (window.Razorpay) return Promise.resolve()
  if (!razorpayLoading) {
    razorpayLoading = new Promise((resolve, reject) => {
      const el = document.createElement('script')
      el.src = 'https://checkout.razorpay.com/v1/checkout.js'
      el.onload = resolve
      el.onerror = () => { razorpayLoading = null; el.remove(); reject(payErr('Could not load the payment window. Check your internet and try again.')) }
      document.head.appendChild(el)
    })
  }
  return razorpayLoading
}

// Resolves with the verified order, or rejects with Error(message) (cancelled / failed).
async function payWithRazorpay(order, pay) {
  await loadRazorpay()
  return new Promise((resolve, reject) => {
    let settled = false
    const fail = async (reason, message) => {
      if (settled) return
      settled = true
      try { await checkoutService.failed({ order_no: order.order_no, token: order.token, reason }) } catch (e) { /* ignore */ }
      reject(payErr(message))
    }
    const rzp = new window.Razorpay({
      key: pay.key_id, amount: pay.amount_paise, currency: pay.currency || 'INR', order_id: pay.gateway_order_id,
      name: 'Kokango', description: `Order ${order.order_no}`, prefill: pay.prefill,
      handler: async (r) => {
        if (settled) return
        settled = true
        try {
          resolve(await checkoutService.verify({ order_no: order.order_no, token: order.token, gateway_order_id: r.razorpay_order_id, gateway_payment_id: r.razorpay_payment_id, signature: r.razorpay_signature }))
        } catch (e) { reject(payErr(apiError(e).message)) }
      },
      modal: { ondismiss: () => fail('dismissed', 'Payment cancelled, you can try again.') },
    })
    rzp.on && rzp.on('payment.failed', (r) => fail(r?.error?.description || 'failed', 'Payment failed, you can try again.'))
    rzp.open()
  })
}

async function submit() {
  if (processing.value || !cart.items.length) return
  serverError.value = ''
  if (!validate()) return
  if (svc.value && !svc.value.serviceable) { serverError.value = 'Sorry, we do not deliver to this pincode yet. Please use a different address.'; return }
  processing.value = true
  const phone = f.mobile.replace(/[\s-]/g, '')
  const body = {
    address: { name: f.name.trim(), phone, line1: f.line.trim(), line2: f.district.trim() || undefined, city: f.city.trim(), state: f.state.trim(), pincode: f.pincode.trim() },
    shipping_method: method.value,
    payment_method: payMethod.value === 'cod' && codAvailable.value ? 'cod' : 'online',
    ...(couponApplied.value ? { coupon_code: couponApplied.value } : {}),
  }
  if (!auth.isLoggedIn) body.customer = { name: f.name.trim(), email: f.email.trim(), phone }
  let order = null
  try {
    const res = await checkoutService.placeOrder(body)
    order = res.order
    let paid
    if (res.payment.gateway === 'cod') paid = order // Cash on Delivery: nothing to pay now
    else if (res.payment.gateway === 'razorpay') paid = await payWithRazorpay(order, res.payment)
    else {
      await sleep(1000) // pretend the payment takes a second
      paid = await checkoutService.verify({ order_no: order.order_no, token: order.token, gateway_order_id: res.payment.gateway_order_id, gateway_payment_id: 'pay_fake_' + Math.random().toString(36).slice(2, 12), signature: 'fake' })
    }
    orderService.saveLastOrder(order.order_no, order.token)
    if (auth.isLoggedIn && saveAddr.value && !savedId.value) {
      try { await addressService.create({ ...body.address, is_default: !saved.value.length }) } catch (e) { /* the order is placed; ignore */ }
    }
    catalogue.clearCatalogueCache()
    await cart.load()
    await router.push(`/order-confirmation?order=${paid?.order_no || order.order_no}`)
  } catch (e) {
    if (e?.payment) serverError.value = e.message
    else {
      const er = apiError(e)
      const map = { 'customer.name': 'name', 'customer.email': 'email', 'customer.phone': 'mobile', 'address.name': 'name', 'address.phone': 'mobile', 'address.line1': 'line', 'address.city': 'city', 'address.state': 'state', 'address.pincode': 'pincode', shipping_method: null }
      for (const [k, v] of Object.entries(er.errors)) if (map[k]) errors[map[k]] = v
      serverError.value = er.message
    }
    cart.load()
  } finally { processing.value = false }
}
</script>

<style scoped>
.coupon { margin-top: 14px; }
.coupon-f { display: flex; gap: 8px; }
.coupon-f input { flex: 1; min-width: 0; min-height: 42px; padding: 0 14px; border: 1.5px dashed #BFB9A0; border-radius: 12px; background: #FFFDF6; font: inherit; font-size: 14px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--ink); }
.coupon-f input:focus { outline: none; border-color: var(--forest); border-style: solid; box-shadow: 0 0 0 3px rgba(55, 146, 55, .2); }
.coupon-f input::placeholder { text-transform: none; letter-spacing: 0; font-weight: 500; }
.coupon-on { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 14px; border-radius: 12px; background: var(--tint); color: var(--forest); font-size: 13.5px; animation: pop-in .35s var(--ease); }
.link-btn { border: 0; background: none; padding: 4px; color: var(--forest); font-weight: 700; text-decoration: underline; font-size: 13px; }
.coupon-msg { margin: 6px 2px 0; min-height: 18px; font-size: 12.5px; font-weight: 600; color: #1E7A34; }
.coupon-msg.bad { color: var(--danger); }
@keyframes pop-in { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: none; } }
@media (max-width:900px){
.split{gap:20px!important}
.split>.split-main{flex:1 1 100%!important;min-width:0!important}
.split>.split-side{flex:1 1 100%!important;min-width:0!important;width:100%}
.field input,.field select,select{font-size:16px!important}
}
@media (max-width:600px){
.wrap{padding-left:16px!important;padding-right:16px!important}
.ph1{font-size:30px!important;letter-spacing:-.5px!important;margin:16px 0 20px!important;overflow-wrap:anywhere}
.card{border-radius:14px!important}
.pad{padding:20px!important}
.stepbar{gap:8px!important;font-size:13px!important;margin:12px 0 24px!important;flex-wrap:nowrap!important}
.stsp{width:14px!important;flex:0 1 14px}
.stepbar>span:not(.stsp){gap:6px!important}
.paybtn{height:auto;padding-top:10px;padding-bottom:10px;text-align:center;line-height:1.3}
}
@media (max-width:360px){.stepbar{font-size:12px!important}.stsp{width:8px!important;flex-basis:8px}}
@media (min-width:901px) and (max-width:999px){
.split{gap:20px!important}
.split>.split-main{flex:1 1 100%!important;min-width:0!important}
.split>.split-side{flex:1 1 100%!important;min-width:0!important;width:100%}
}
@media (min-width:1000px) and (max-width:1199px){
.split>.split-main{flex:1 1 480px!important;min-width:0!important}
}
</style>
