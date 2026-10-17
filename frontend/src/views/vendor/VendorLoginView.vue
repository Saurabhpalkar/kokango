<template>
<PanelAuthLayout headline="Sell your flavours on Kokango." text="List products, manage orders and stock, and track your earnings from one place.">
  <h1 class="t">Vendor login</h1>
  <p class="s">Sign in to manage your store.</p>
  <form novalidate class="f" @submit.prevent="submit">
    <label class="field">Email<input v-model="email" type="email" autocomplete="username" placeholder="you@yourstore.com"></label>
    <label class="field">Password<input v-model="password" type="password" autocomplete="current-password" placeholder="Enter your password"></label>
    <span v-if="error" class="alert-err" role="alert">{{ error }}</span>
    <button type="submit" class="btn" :disabled="busy">{{ busy ? 'Signing in…' : 'Login to vendor panel' }}</button>
  </form>
  <div v-if="demo" class="demo"><b>Demo vendor account</b> (sample data only)<br>Email: {{ demo.email }}<br>Password: {{ demo.password }}<div><button type="button" class="fill" @click="fill">Fill demo details</button></div></div>
  <p class="alt">New to Kokango? <a href="/vendor/register">Become a vendor</a></p>
  <a href="/" class="btn-line back">Back to store</a>
</PanelAuthLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PanelAuthLayout from '@/components/PanelAuthLayout.vue'
import { useVendorStore } from '@/stores/vendor'
import { apiError } from '@/services/api'
import { VENDOR_MOCK, DEMO_VENDOR } from '@/services/vendorService'

const route = useRoute(); const router = useRouter(); const vendor = useVendorStore()
const demo = VENDOR_MOCK ? DEMO_VENDOR : null
const email = ref(''); const password = ref(''); const error = ref(''); const busy = ref(false)
function fill() { email.value = demo.email; password.value = demo.password }
async function submit() {
  error.value = ''
  if (!email.value.trim() || !password.value) { error.value = 'Enter your email and password.'; return }
  busy.value = true
  try {
    await vendor.login(email.value.trim(), password.value)
    const to = String(route.query.redirect || '')
    router.push(to.startsWith('/vendor') ? to : '/vendor')
  } catch (e) { error.value = apiError(e, 'Wrong email or password.').message } finally { busy.value = false }
}
</script>

<style scoped>
.t{margin:0 0 8px;font-size:30px;line-height:1.1;font-weight:800;letter-spacing:-.8px}
.s{margin:0 0 24px;font-size:15px;line-height:1.7;color:var(--muted)}
.f{display:flex;flex-direction:column;gap:16px}
.demo{margin-top:20px;padding:14px 18px;border-radius:var(--r-md);background:var(--sun);font-size:13px;line-height:1.7;color:#4d4a35}
.fill{margin-top:8px;background:#fff;border:1.5px solid var(--line);border-radius:999px;padding:6px 16px;font-size:12px;font-weight:700;color:var(--ink);cursor:pointer}
.fill:hover{border-color:var(--forest);color:var(--forest)}
.alt{margin:20px 0 14px;text-align:center;font-size:14px;color:var(--muted)}
.alt a{font-weight:700;color:var(--forest);text-decoration:underline;text-underline-offset:3px}
.back{display:flex}
@media (max-width:900px){.field input{font-size:16px}}
</style>
