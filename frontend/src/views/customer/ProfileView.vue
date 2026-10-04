<template>
<div class="dcroot">
<div style="width:100%;position:relative;overflow:hidden;background:var(--bg)">
  <AnnouncementBar />
  <div style="position:absolute;top:-220px;right:-160px;width:520px;height:520px;border-radius:50%;background:var(--sun)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader active-icon="account" />

    <h1 class="ph1" style="margin:24px 0 32px;font-size:42px;font-weight:800;letter-spacing:-1px">My Account</h1>

    <section class="split" style="display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start">
      <aside class="card split-side pside" style="flex:0 0 270px;border-radius:32px;padding:20px;box-sizing:border-box">
        <div class="phead" style="display:flex;align-items:center;gap:12px;padding:8px 8px 20px">
          <span style="width:52px;height:52px;border-radius:50%;background:var(--tint);color:var(--green);display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:18px">{{ initial }}</span>
          <div style="min-width:0"><div style="font-size:15px;font-weight:700;overflow-wrap:anywhere">{{ displayName }}</div><div style="font-size:12px;color:var(--muted);word-break:break-all">{{ displayEmail }}</div></div>
        </div>
        <button type="button" class="tab" :class="{ on: menu === 'orders' }" @click="tab = 'orders'">My Orders</button>
        <button type="button" class="tab" :class="{ on: menu === 'profile' }" @click="tab = 'profile'">Profile</button>
        <button type="button" class="tab" :class="{ on: menu === 'addresses' }" @click="tab = 'addresses'">Addresses</button>
        <button type="button" class="tab" :class="{ on: menu === 'password' }" @click="tab = 'password'">Change Password</button>
        <button type="button" class="tab" @click="logout">Logout</button>
      </aside>

      <div class="split-main" style="flex:1 1 640px;display:flex;flex-direction:column;gap:24px">
        <div v-if="show('orders')" class="card pad" style="border-radius:36px;padding:32px;box-sizing:border-box">
          <h2 style="margin:0 0 20px;font-size:22px;font-weight:800">My Orders</h2>
          <p v-if="ordersLoading" style="margin:0;font-size:14px;color:var(--muted)">Loading your orders...</p>
          <p v-else-if="ordersError" class="err" role="alert" style="margin:0">{{ ordersError }} <button type="button" class="lnk" @click="loadOrders">Try again</button></p>
          <div v-else-if="!orders.length" style="border-top:1px solid var(--line);padding-top:20px"><p style="margin:0 0 16px;font-size:14px;color:var(--muted)">You have not placed any orders yet.</p><a href="/products" class="btn">Browse products</a></div>
          <template v-for="o in orders" :key="o.order_no">
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:16px;padding:16px 0;border-top:1px solid var(--line)">
              <div style="flex:1 1 200px;min-width:0"><div style="font-size:15px;font-weight:700;overflow-wrap:anywhere">{{ o.order_no }}</div><div style="font-size:12px;color:var(--muted);margin-top:2px">{{ formatDate(o.placed_at) }} &middot; {{ o.items.length }} {{ o.items.length === 1 ? 'item' : 'items' }}</div></div>
              <span class="badge" :style="`background:${statusPill(o.status).bg};color:${statusPill(o.status).fg}`">{{ statusPill(o.status).label }}</span>
              <div class="otot" style="font-weight:800;min-width:70px;text-align:right">{{ inrp(o.total_paise) }}</div>
              <a :href="`/orders?order=${o.order_no}`" class="btn-line">Track</a>
            </div>
          </template>
        </div>

        <div v-if="show('profile')" class="card pad" style="border-radius:36px;padding:32px;box-sizing:border-box">
          <h2 style="margin:0 0 20px;font-size:22px;font-weight:800">Profile</h2>
          <form novalidate style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr));gap:16px" @submit.prevent="saveProfile">
            <p v-if="perr.form" class="err" role="alert" style="grid-column:1/-1;margin:0">{{ perr.form }}</p>
            <label class="field">Full name<input v-model="pf.name" :class="{ bad: perr.name }"><span v-if="perr.name" class="err" role="alert">{{ perr.name }}</span></label>
            <label class="field">Mobile number<input v-model="pf.mobile" inputmode="numeric" :class="{ bad: perr.mobile }"><span v-if="perr.mobile" class="err" role="alert">{{ perr.mobile }}</span></label>
            <label class="field" style="grid-column:1/-1">Email<input v-model="pf.email" :class="{ bad: perr.email }"><span v-if="perr.email" class="err" role="alert">{{ perr.email }}</span></label>
            <div style="grid-column:1/-1"><button type="submit" class="btn" :disabled="busy.profile" style="margin-top:4px">{{ busy.profile ? 'Saving...' : 'Save changes' }}</button></div>
          </form>
        </div>

        <div v-if="show('addresses')" class="card pad" style="border-radius:36px;padding:32px;box-sizing:border-box">
          <div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center;margin-bottom:20px"><h2 style="margin:0;font-size:22px;font-weight:800">Saved Addresses</h2><button type="button" class="btn-line" @click="openForm()">+ Add address</button></div>
          <form v-if="formOpen" novalidate class="aform" @submit.prevent="saveAddress">
            <div style="font-size:15px;font-weight:800;margin-bottom:14px">{{ editingId ? 'Edit address' : 'New address' }}</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(220px,100%),1fr));gap:14px">
              <label class="field">Full name<input v-model="af.name" :class="{ bad: aerr.name }"><span v-if="aerr.name" class="err" role="alert">{{ aerr.name }}</span></label>
              <label class="field">Mobile number<input v-model="af.phone" inputmode="numeric" :class="{ bad: aerr.phone }"><span v-if="aerr.phone" class="err" role="alert">{{ aerr.phone }}</span></label>
              <label class="field">Pincode<input v-model="af.pincode" inputmode="numeric" :class="{ bad: aerr.pincode }"><span v-if="aerr.pincode" class="err" role="alert">{{ aerr.pincode }}</span></label>
              <label class="field" style="grid-column:1/-1">Address<input v-model="af.line" placeholder="House no., street, area" :class="{ bad: aerr.line }"><span v-if="aerr.line" class="err" role="alert">{{ aerr.line }}</span></label>
              <label class="field">City<input v-model="af.city" :class="{ bad: aerr.city }"><span v-if="aerr.city" class="err" role="alert">{{ aerr.city }}</span></label>
              <label class="field">State<input v-model="af.state" :class="{ bad: aerr.state }"><span v-if="aerr.state" class="err" role="alert">{{ aerr.state }}</span></label>
            </div>
            <p v-if="aerr.form" class="err" role="alert" style="margin:12px 0 0">{{ aerr.form }}</p>
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:16px"><button type="submit" class="btn" :disabled="busy.address">{{ busy.address ? 'Saving...' : 'Save' }}</button><button type="button" class="btn-line" @click="formOpen = false">Cancel</button></div>
          </form>
          <p v-if="addrLoading" style="margin:0;font-size:14px;color:var(--muted)">Loading your addresses...</p>
          <p v-else-if="addrError" class="err" role="alert" style="margin:0">{{ addrError }} <button type="button" class="lnk" @click="loadAddresses">Try again</button></p>
          <p v-else-if="!addresses.length && !formOpen" style="margin:0;font-size:14px;color:var(--muted)">You have no saved addresses yet.</p>
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(280px,100%),1fr));gap:16px">
            <template v-for="a in addresses" :key="a.id">
              <div v-if="a.is_default" style="border:2px solid var(--green);border-radius:24px;padding:20px;background:var(--tint)">
                <div style="display:flex;justify-content:space-between;font-size:14px;margin-bottom:8px"><span style="font-weight:800">{{ a.name }}</span><span class="badge" style="background:var(--green);color:#fff">Default</span></div>
                <div style="font-size:13px;line-height:1.7;color:var(--muted)">{{ a.line1 }}<span v-if="a.line2">, {{ a.line2 }}</span><br>{{ a.city }}, {{ a.state }} {{ a.pincode }}<br>+91 {{ a.phone }}</div>
                <div v-if="confirmId === a.id" class="confirm">Delete this address? <button type="button" class="lnk" @click="removeAddress(a.id)">Yes, delete</button><button type="button" class="lnk" @click="confirmId = null">Cancel</button></div>
                <div v-else style="display:flex;gap:16px;margin-top:12px;font-size:13px;font-weight:700"><button type="button" class="lnk" @click="openForm(a)">Edit</button><button type="button" class="lnk" @click="confirmId = a.id">Delete</button></div>
              </div>
              <div v-else style="border:1.5px solid var(--line);border-radius:24px;padding:20px;background:#fff">
                <div style="font-size:14px;font-weight:800;margin-bottom:8px">{{ a.name }}</div>
                <div style="font-size:13px;line-height:1.7;color:var(--muted)">{{ a.line1 }}<span v-if="a.line2">, {{ a.line2 }}</span><br>{{ a.city }}, {{ a.state }} {{ a.pincode }}<br>+91 {{ a.phone }}</div>
                <div v-if="confirmId === a.id" class="confirm">Delete this address? <button type="button" class="lnk" @click="removeAddress(a.id)">Yes, delete</button><button type="button" class="lnk" @click="confirmId = null">Cancel</button></div>
                <div v-else style="display:flex;gap:16px;margin-top:12px;font-size:13px;font-weight:700"><button type="button" class="lnk" @click="openForm(a)">Edit</button><button type="button" class="lnk" @click="confirmId = a.id">Delete</button><button type="button" class="lnk" @click="setDefault(a.id)">Set default</button></div>
              </div>
            </template>
          </div>
        </div>

        <div v-if="show('password')" class="card pad" style="border-radius:36px;padding:32px;box-sizing:border-box">
          <h2 style="margin:0 0 20px;font-size:22px;font-weight:800">Change Password</h2>
          <form novalidate style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr));gap:16px" @submit.prevent="savePassword">
            <p v-if="werr.form" class="err" role="alert" style="grid-column:1/-1;margin:0">{{ werr.form }}</p>
            <label class="field" style="grid-column:1/-1">Current password<input v-model="pw.current" type="password" autocomplete="current-password" :class="{ bad: werr.current }"><span v-if="werr.current" class="err" role="alert">{{ werr.current }}</span></label>
            <label class="field">New password<input v-model="pw.next" type="password" autocomplete="new-password" :class="{ bad: werr.next }"><span v-if="werr.next" class="err" role="alert">{{ werr.next }}</span></label>
            <label class="field">Confirm new password<input v-model="pw.confirm" type="password" autocomplete="new-password" :class="{ bad: werr.confirm }"><span v-if="werr.confirm" class="err" role="alert">{{ werr.confirm }}</span></label>
            <div style="grid-column:1/-1"><button type="submit" class="btn" :disabled="busy.password" style="margin-top:4px">{{ busy.password ? 'Saving...' : 'Save' }}</button></div>
          </form>
        </div>
      </div>
    </section>

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
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { inrp, formatDate } from '@/utils/format'
import { statusPill } from '@/utils/orderStatus'
import { apiError } from '@/services/api'
import * as orderService from '@/services/orderService'
import * as addressService from '@/services/addressService'

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const displayName = computed(() => auth.user?.name || '')
const displayEmail = computed(() => auth.user?.email || '')
const initial = computed(() => displayName.value.trim().charAt(0).toUpperCase() || 'K')
const busy = reactive({ profile: false, address: false, password: false })
const clear = (o) => { for (const k of Object.keys(o)) delete o[k] }

// 'all' is the approved default layout (every section stacked); picking a menu item shows just that section.
const tab = ref('all')
const menu = computed(() => (tab.value === 'all' ? 'orders' : tab.value))
const show = (name) => tab.value === 'all' ? name !== 'password' : tab.value === name

async function logout() {
  await auth.logout()
  router.push('/')
}

// Orders
const orders = ref([])
const ordersLoading = ref(true)
const ordersError = ref('')
async function loadOrders() {
  ordersLoading.value = true; ordersError.value = ''
  try { orders.value = (await orderService.list({ per_page: 50 })).items }
  catch (e) { ordersError.value = apiError(e).message }
  finally { ordersLoading.value = false }
}

// Profile
const pf = reactive({ name: auth.user?.name || '', mobile: auth.user?.phone || '', email: auth.user?.email || '' })
const perr = reactive({})
async function saveProfile() {
  clear(perr)
  if (!pf.name.trim()) perr.name = 'Please enter your name.'
  if (pf.mobile.trim() && !/^\d{10}$/.test(pf.mobile.replace(/[\s-]/g, ''))) perr.mobile = 'Enter a 10 digit mobile number.'
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(pf.email.trim())) perr.email = 'Enter a valid email address.'
  if (Object.keys(perr).length) return
  busy.profile = true
  try {
    await auth.updateProfile({ name: pf.name.trim(), email: pf.email.trim(), phone: pf.mobile.replace(/[\s-]/g, '') || null })
    toast.show('Profile saved')
  } catch (e) {
    const er = apiError(e)
    if (er.errors.name) perr.name = er.errors.name
    if (er.errors.phone) perr.mobile = er.errors.phone
    if (er.errors.email) perr.email = er.errors.email
    if (!Object.keys(perr).length) perr.form = er.message
  } finally { busy.profile = false }
}

// Addresses
const addresses = ref([])
const addrLoading = ref(true)
const addrError = ref('')
const formOpen = ref(false)
const editingId = ref(null)
const confirmId = ref(null)
const af = reactive({ name: '', phone: '', line: '', city: '', state: '', pincode: '' })
const aerr = reactive({})
async function loadAddresses() {
  addrLoading.value = true; addrError.value = ''
  try { addresses.value = await addressService.list() }
  catch (e) { addrError.value = apiError(e).message }
  finally { addrLoading.value = false }
}
function openForm(a) {
  clear(aerr)
  confirmId.value = null
  editingId.value = a ? a.id : null
  Object.assign(af, a
    ? { name: a.name, phone: a.phone || '', line: a.line1, city: a.city, state: a.state, pincode: a.pincode }
    : { name: '', phone: auth.user?.phone || '', line: '', city: '', state: '', pincode: '' })
  formOpen.value = true
}
async function saveAddress() {
  clear(aerr)
  if (!af.name.trim()) aerr.name = 'Please enter the full name.'
  if (!/^\d{10}$/.test(af.phone.replace(/[\s-]/g, ''))) aerr.phone = 'Enter a 10 digit mobile number.'
  if (!af.line.trim()) aerr.line = 'Please enter the address.'
  if (!af.city.trim()) aerr.city = 'Please enter the city.'
  if (!af.state.trim()) aerr.state = 'Please enter the state.'
  if (!/^\d{6}$/.test(af.pincode.trim())) aerr.pincode = 'Enter a 6 digit pincode.'
  if (Object.keys(aerr).length) return
  const data = { name: af.name.trim(), phone: af.phone.replace(/[\s-]/g, ''), line1: af.line.trim(), city: af.city.trim(), state: af.state.trim(), pincode: af.pincode.trim() }
  busy.address = true
  try {
    if (editingId.value) { await addressService.update(editingId.value, data); toast.show('Address updated') }
    else { await addressService.create({ ...data, is_default: !addresses.value.length }); toast.show('Address added') }
    formOpen.value = false
    await loadAddresses()
  } catch (e) {
    const er = apiError(e)
    const map = { line1: 'line', name: 'name', phone: 'phone', city: 'city', state: 'state', pincode: 'pincode' }
    for (const [k, v] of Object.entries(er.errors)) if (map[k]) aerr[map[k]] = v
    if (!Object.keys(aerr).length) aerr.form = er.message
  } finally { busy.address = false }
}
async function removeAddress(id) {
  try { await addressService.remove(id); toast.show('Address deleted'); confirmId.value = null; await loadAddresses() }
  catch (e) { toast.show(apiError(e).message) }
}
async function setDefault(id) {
  try { await addressService.setDefault(id); toast.show('Default address updated'); await loadAddresses() }
  catch (e) { toast.show(apiError(e).message) }
}

// Change password
const pw = reactive({ current: '', next: '', confirm: '' })
const werr = reactive({})
async function savePassword() {
  clear(werr)
  if (!pw.current) werr.current = 'Please enter your current password.'
  if (pw.next.length < 8) werr.next = 'Password must be at least 8 characters.'
  if (!pw.confirm) werr.confirm = 'Please confirm your new password.'
  else if (pw.confirm !== pw.next) werr.confirm = 'Passwords do not match.'
  if (Object.keys(werr).length) return
  busy.password = true
  try {
    await auth.changePassword({ current_password: pw.current, password: pw.next, password_confirmation: pw.confirm })
    pw.current = pw.next = pw.confirm = ''
    toast.show('Password updated')
  } catch (e) {
    const er = apiError(e)
    if (er.errors.current_password) werr.current = er.errors.current_password
    if (er.errors.password) werr.next = er.errors.password
    if (!Object.keys(werr).length) werr.form = er.message
  } finally { busy.password = false }
}

onMounted(() => { loadOrders(); loadAddresses() })
</script>

<style scoped>
.dcroot{--bg:#F8F7EF;--surface:#fff;--ink:#14301C;--muted:#566659;--line:#E6E2D0;--green:#0B5D1E;--leaf:#9ACD32;--mango:#FFB400;--amber:#9A5B00;--tint:#EAF3DD;--sun:#FFF1CC}
.dcroot{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans','Noto Sans Devanagari',system-ui,sans-serif}
a{color:var(--ink);text-decoration:none}a:hover{color:var(--green)}
button{font-family:inherit;cursor:pointer}
.card{background:var(--surface);border:1px solid var(--line);box-shadow:0 10px 30px rgba(20,48,28,0.06)}
.btn{background:var(--green);color:#fff;border:none;border-radius:999px;padding:10px 24px;font-size:14px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.btn:hover{background:#08481a;color:#fff}
.btn-line{background:transparent;color:var(--ink);border:1.5px solid var(--ink);border-radius:999px;padding:9px 20px;font-size:14px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.btn-line:hover{background:var(--ink);color:#fff}
.field{display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:var(--muted)}
.field input{height:48px;box-sizing:border-box;background:var(--bg);border:1px solid var(--line);border-radius:14px;padding:0 16px;color:var(--ink);font-family:inherit;font-size:14px;font-weight:500}
.tab{display:block;width:100%;box-sizing:border-box;border:none;background:none;text-align:left;font-family:inherit;padding:12px 18px;border-radius:999px;font-size:14px;font-weight:600;color:var(--muted);cursor:pointer}
.tab:hover{color:var(--green)}
.tab.on{background:var(--tint);color:var(--green)}
.badge{display:inline-block;padding:5px 14px;border-radius:999px;font-size:12px;font-weight:700}
.field input.bad{border-color:#C0392B}
.err{font-size:12px;font-weight:600;color:#C0392B}
.btn:disabled{opacity:.7;cursor:progress}
.aform{border:1.5px solid var(--line);border-radius:24px;padding:20px;background:#fff;margin-bottom:16px}
.lnk{background:none;border:none;padding:0;font:inherit;font-weight:700;color:var(--ink);cursor:pointer}
.lnk:hover{color:var(--green)}
.confirm{display:flex;flex-wrap:wrap;align-items:center;gap:6px 14px;margin-top:12px;font-size:13px;font-weight:600;color:#C0392B}
@media (max-width:900px){
.split{gap:20px!important}
.split>.split-main{flex:1 1 100%!important;min-width:0!important}
.split>.split-side{flex:1 1 100%!important;min-width:0!important;width:100%}
.pside{display:flex;flex-wrap:wrap;gap:8px;padding:16px!important}
.pside .phead{flex:1 1 100%;padding:4px 4px 8px!important}
.pside .tab{flex:1 1 auto;width:auto;text-align:center;min-height:44px;border:1px solid var(--line);background:#fff;padding:10px 16px}
.pside .tab.on{background:var(--tint);border-color:var(--green)}
.lnk{min-height:44px;padding:0 8px}
.aform .field input,.field input{font-size:16px!important}
}
@media (max-width:600px){
.wrap{padding-left:16px!important;padding-right:16px!important}
.ph1{font-size:30px!important;letter-spacing:-.5px!important;margin:16px 0 20px!important}
.card{border-radius:24px!important}
.pad{padding:20px!important}
.pside .tab{flex:1 1 calc(50% - 4px)}
.aform{padding:16px!important;border-radius:20px!important}
.otot{min-width:0!important;margin-left:auto}
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
