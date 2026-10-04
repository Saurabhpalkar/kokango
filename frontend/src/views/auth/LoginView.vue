<template>
<div class="dcroot">
<div style="width:100%;min-height:900px;position:relative;overflow:hidden;background:var(--bg)">
  <div style="position:absolute;top:-180px;right:-120px;width:600px;height:600px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;bottom:-220px;left:-160px;width:560px;height:560px;border-radius:50%;background:var(--tint)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">
    <SiteHeader variant="minimal" />

    <section class="lsec" style="display:flex;flex-wrap:wrap;gap:56px;align-items:center;justify-content:center;padding:40px 0 96px">
      <div class="lhero" style="flex:1 1 380px;max-width:480px;min-width:0">
        <h1 class="lh1" style="margin:0 0 16px;font-size:52px;line-height:1.05;font-weight:800;letter-spacing:-1.5px">Welcome back to <span style="color:var(--green)">Kokango</span></h1>
        <p style="margin:0 0 28px;font-size:16px;line-height:1.7;color:var(--muted)">Log in to track your orders, save addresses and check out faster.</p>
        <div class="card limg" style="border-radius:32px;height:260px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:linear-gradient(160deg,#fff,var(--tint))"><img src="/images/jackfruit-chips.png" alt="Jackfruit chips pack" style="height:80%;width:auto;object-fit:contain;margin-right:-14px"><img src="/images/curry-leaf-powder.png" alt="Curry leaf powder pack" style="height:98%;width:auto;object-fit:contain;position:relative"><img src="/images/jamun-vadi.png" alt="Jamun vadi pack" style="height:80%;width:auto;object-fit:contain;margin-left:-14px"></div>
      </div>

      <div class="card lcard" style="flex:1 1 400px;max-width:460px;min-width:0;border-radius:44px;padding:40px;box-sizing:border-box">
        <div role="tablist" style="display:flex;margin:0 0 24px;background:var(--bg);border-radius:999px;padding:4px">
          <button type="button" role="tab" :aria-selected="tab === 'login'" @click="setTab('login')" :style="tabStyle('login')">Login</button>
          <button type="button" role="tab" :aria-selected="tab === 'register'" @click="setTab('register')" :style="tabStyle('register')">Register</button>
        </div>
        <form v-if="tab === 'login'" novalidate style="display:flex;flex-direction:column;gap:16px" @submit.prevent="submitLogin">
          <p v-if="errors.form" class="err" role="alert" style="margin:0;padding:12px 16px;border-radius:14px;background:#FBE4E0">{{ errors.form }}</p>
          <label class="field">Email or mobile<input v-model="lf.id" placeholder="you@example.com" autocomplete="username" :class="{ bad: errors.id }"><span v-if="errors.id" class="err" role="alert">{{ errors.id }}</span></label>
          <label class="field">Password<input v-model="lf.password" type="password" placeholder="Enter your password" autocomplete="current-password" :class="{ bad: errors.password }"><span v-if="errors.password" class="err" role="alert">{{ errors.password }}</span></label>
          <div style="display:flex;flex-wrap:wrap;gap:4px 12px;justify-content:space-between;align-items:center;font-size:13px"><label style="min-height:44px;display:flex;gap:8px;align-items:center;color:var(--muted);font-weight:500"><input type="checkbox" style="accent-color:#0B5D1E;width:18px;height:18px">Remember me</label><a href="/forgot-password" style="font-weight:600;color:var(--green);padding:12px 0">Forgot password?</a></div>
          <button type="submit" class="btn lbtn" :disabled="busy" style="margin-top:4px;min-height:72px">{{ busy ? 'Logging in...' : 'Login' }}</button>
        </form>
        <form v-else novalidate style="display:flex;flex-direction:column;gap:16px" @submit.prevent="submitRegister">
          <p v-if="errors.form" class="err" role="alert" style="margin:0;padding:12px 16px;border-radius:14px;background:#FBE4E0">{{ errors.form }}</p>
          <label class="field">Full name<input v-model="rf.name" placeholder="Rohan Patil" autocomplete="name" :class="{ bad: errors.name }"><span v-if="errors.name" class="err" role="alert">{{ errors.name }}</span></label>
          <label class="field">Email<input v-model="rf.email" type="email" placeholder="you@example.com" autocomplete="email" :class="{ bad: errors.email }"><span v-if="errors.email" class="err" role="alert">{{ errors.email }}</span></label>
          <label class="field">Mobile number (optional)<input v-model="rf.phone" inputmode="numeric" placeholder="98765 43210" autocomplete="tel" :class="{ bad: errors.phone }"><span v-if="errors.phone" class="err" role="alert">{{ errors.phone }}</span></label>
          <label class="field">Password<input v-model="rf.password" type="password" placeholder="At least 8 characters" autocomplete="new-password" :class="{ bad: errors.password }"><span v-if="errors.password" class="err" role="alert">{{ errors.password }}</span></label>
          <label class="field">Confirm password<input v-model="rf.confirm" type="password" placeholder="Re-enter your password" autocomplete="new-password" :class="{ bad: errors.confirm }"><span v-if="errors.confirm" class="err" role="alert">{{ errors.confirm }}</span></label>
          <button type="submit" class="btn lbtn" :disabled="busy" style="margin-top:4px;min-height:72px">{{ busy ? 'Creating account...' : 'Create account' }}</button>
        </form>
        <div style="display:flex;align-items:center;gap:12px;margin:22px 0;font-size:12px;color:var(--muted)"><span style="flex:1;height:1px;background:var(--line)"></span>or<span style="flex:1;height:1px;background:var(--line)"></span></div>
        <a href="/checkout" class="btn-line" style="display:flex">Continue as guest</a>
      </div>
    </section>
  </div>
</div>
</div>
</template>



<script setup>
import SiteHeader from '@/components/SiteHeader.vue'
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import { apiError } from '@/services/api'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const tab = ref('login')
const lf = reactive({ id: '', password: '' })
const rf = reactive({ name: '', email: '', phone: '', password: '', confirm: '' })
const busy = ref(false)
const errors = reactive({})

function clearErrors() { for (const k of Object.keys(errors)) delete errors[k] }
function setTab(t) { tab.value = t; clearErrors() }
function tabStyle(t) {
  return tab.value === t
    ? 'flex:1;height:44px;border:none;border-radius:999px;background:var(--green);color:#fff;font-weight:700;font-size:14px'
    : 'flex:1;height:44px;border:none;border-radius:999px;background:none;color:var(--ink);font-weight:600;font-size:14px'
}
function goAfter() {
  const r = route.query.redirect
  // only follow internal redirects
  router.push(typeof r === 'string' && r.startsWith('/') && !r.startsWith('//') ? r : '/account')
}
const looksValid = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) || /^\+?[\d\s-]{10,13}$/.test(v)

async function submitLogin() {
  clearErrors()
  const id = lf.id.trim()
  if (!id) errors.id = 'Please enter your email or mobile number.'
  else if (!looksValid(id)) errors.id = 'Enter a valid email or 10 digit mobile number.'
  if (!lf.password) errors.password = 'Please enter your password.'
  if (Object.keys(errors).length) return
  busy.value = true
  try {
    await auth.login(id.includes('@') ? id : id.replace(/[\s+-]/g, '').slice(-10), lf.password)
    toast.show('Welcome back')
    goAfter()
  } catch (e) {
    const er = apiError(e)
    if (er.errors.login) errors.id = er.errors.login
    if (er.errors.password) errors.password = er.errors.password
    if (!Object.keys(errors).length) errors.form = er.message
  } finally { busy.value = false }
}

async function submitRegister() {
  clearErrors()
  const phone = rf.phone.replace(/[\s-]/g, '')
  if (!rf.name.trim()) errors.name = 'Please enter your full name.'
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(rf.email.trim())) errors.email = 'Enter a valid email address.'
  if (phone && !/^\d{10}$/.test(phone)) errors.phone = 'Enter a 10 digit mobile number.'
  if (rf.password.length < 8) errors.password = 'Password must be at least 8 characters.'
  if (!rf.confirm) errors.confirm = 'Please confirm your password.'
  else if (rf.confirm !== rf.password) errors.confirm = 'Passwords do not match.'
  if (Object.keys(errors).length) return
  busy.value = true
  try {
    await auth.register({ name: rf.name.trim(), email: rf.email.trim(), phone: phone || undefined, password: rf.password, password_confirmation: rf.confirm })
    toast.show('Account created')
    goAfter()
  } catch (e) {
    const er = apiError(e)
    for (const [k, v] of Object.entries(er.errors)) {
      if (k === 'password_confirmation') errors.confirm = v
      else errors[k] = v
    }
    if (!Object.keys(errors).length) errors.form = er.message
  } finally { busy.value = false }
}
</script>

<style scoped>
.dcroot{--bg:#F8F7EF;--surface:#fff;--ink:#14301C;--muted:#566659;--line:#E6E2D0;--green:#0B5D1E;--leaf:#9ACD32;--mango:#FFB400;--amber:#9A5B00;--tint:#EAF3DD;--sun:#FFF1CC}
.dcroot{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans','Noto Sans Devanagari',system-ui,sans-serif}
a{color:var(--ink);text-decoration:none}a:hover{color:var(--green)}
button{font-family:inherit;cursor:pointer}
.card{background:var(--surface);border:1px solid var(--line);box-shadow:0 10px 30px rgba(20,48,28,0.06)}
.btn{background:var(--green);color:#fff;border:none;border-radius:999px;padding:12px 28px;font-size:15px;font-weight:600;min-height:48px;display:inline-flex;align-items:center;justify-content:center}
.btn:hover{background:#08481a;color:#fff}
.btn-line{background:transparent;color:var(--ink);border:1.5px solid var(--ink);border-radius:999px;padding:11px 26px;font-size:15px;font-weight:600;min-height:48px;display:inline-flex;align-items:center;justify-content:center}
.btn-line:hover{background:var(--ink);color:#fff}
.field{display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:600;color:var(--muted)}
.field input{height:50px;box-sizing:border-box;background:var(--bg);border:1px solid var(--line);border-radius:14px;padding:0 16px;color:var(--ink);font-family:inherit;font-size:14px;font-weight:500}
.field input.bad{border-color:#C0392B}
.err{font-size:12px;font-weight:600;color:#C0392B}
input::placeholder{color:#8a948c}
.btn:disabled{opacity:.7;cursor:progress}
@media (max-width:900px){.field input{font-size:16px!important}.lsec{gap:32px!important}}
@media (max-width:600px){
.wrap{padding-left:16px!important;padding-right:16px!important}
.lsec{padding:16px 0 48px!important;gap:24px!important}
.lh1{font-size:34px!important;letter-spacing:-1px!important;margin-bottom:12px!important;overflow-wrap:anywhere}
.limg{height:170px!important;border-radius:24px!important}
.lcard{padding:22px 18px!important;border-radius:28px!important}
.lbtn{min-height:52px!important}
}
</style>
