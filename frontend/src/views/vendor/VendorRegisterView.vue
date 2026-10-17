<template>
<PanelAuthLayout headline="Become a Kokango vendor." text="Tell us about your store. Our team reviews every application before products go live.">
  <h1 class="t">Create your vendor account</h1>
  <p class="s">It takes about two minutes.</p>
  <form novalidate class="f" @submit.prevent="submit">
    <div class="two">
      <label class="field">Store name<input v-model="form.store_name" placeholder="e.g. Sahyadri Farms" :class="{ bad: errs.store_name }"><span v-if="errs.store_name" class="err">{{ errs.store_name }}</span></label>
      <label class="field">Your name<input v-model="form.owner_name" autocomplete="name" :class="{ bad: errs.owner_name }"><span v-if="errs.owner_name" class="err">{{ errs.owner_name }}</span></label>
    </div>
    <div class="two">
      <label class="field">Email<input v-model="form.email" type="email" autocomplete="email" :class="{ bad: errs.email }"><span v-if="errs.email" class="err">{{ errs.email }}</span></label>
      <label class="field">Mobile<input v-model="form.phone" inputmode="numeric" maxlength="10" autocomplete="tel" :class="{ bad: errs.phone }"><span v-if="errs.phone" class="err">{{ errs.phone }}</span></label>
    </div>
    <div class="two">
      <label class="field">City<input v-model="form.city" :class="{ bad: errs.city }"><span v-if="errs.city" class="err">{{ errs.city }}</span></label>
      <label class="field">GST number <span class="opt-t">(optional)</span><input v-model="form.gst_no" maxlength="15"></label>
    </div>
    <label class="field">Password<input v-model="form.password" type="password" autocomplete="new-password" placeholder="At least 8 characters" :class="{ bad: errs.password }"><span v-if="errs.password" class="err">{{ errs.password }}</span></label>
    <label class="check"><input v-model="agree" type="checkbox"> I agree to the vendor terms and the <a href="/policy/terms">Terms &amp; Conditions</a>.</label>
    <span v-if="errs.agree" class="err">{{ errs.agree }}</span>
    <span v-if="error" class="alert-err" role="alert">{{ error }}</span>
    <button type="submit" class="btn" :disabled="busy">{{ busy ? 'Creating account…' : 'Create vendor account' }}</button>
  </form>
  <p class="alt">Already selling with us? <a href="/vendor/login">Log in</a></p>
</PanelAuthLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import PanelAuthLayout from '@/components/PanelAuthLayout.vue'
import { useVendorStore } from '@/stores/vendor'
import { apiError } from '@/services/api'

const router = useRouter(); const vendor = useVendorStore()
const form = reactive({ store_name: '', owner_name: '', email: '', phone: '', city: '', gst_no: '', password: '' })
const agree = ref(false); const errs = reactive({}); const error = ref(''); const busy = ref(false)

function validate() {
  Object.keys(errs).forEach((k) => delete errs[k])
  if (!form.store_name.trim()) errs.store_name = 'Enter your store name.'
  if (!form.owner_name.trim()) errs.owner_name = 'Enter your name.'
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) errs.email = 'Enter a valid email.'
  if (!/^[6-9]\d{9}$/.test(form.phone.trim())) errs.phone = 'Enter a 10-digit mobile number.'
  if (!form.city.trim()) errs.city = 'Enter your city.'
  if (form.password.length < 8) errs.password = 'Use at least 8 characters.'
  if (!agree.value) errs.agree = 'Please accept the terms to continue.'
  return !Object.keys(errs).length
}
async function submit() {
  error.value = ''
  if (!validate()) return
  busy.value = true
  try { await vendor.register({ ...form }); router.push('/vendor') }
  catch (e) { const er = apiError(e, 'Could not create your account.'); Object.assign(errs, er.errors || {}); error.value = er.message } finally { busy.value = false }
}
</script>

<style scoped>
.t{margin:0 0 6px;font-size:28px;line-height:1.1;font-weight:800;letter-spacing:-.8px}
.s{margin:0 0 22px;font-size:15px;color:var(--muted)}
.f{display:flex;flex-direction:column;gap:14px}
.two{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.opt-t{font-weight:500;color:var(--muted)}
.check{display:flex;gap:10px;align-items:flex-start;font-size:13px;line-height:1.5;color:var(--muted)}
.check input{margin-top:3px;accent-color:var(--forest)}
.check a{color:var(--forest);font-weight:700;text-decoration:underline}
.alt{margin:20px 0 0;text-align:center;font-size:14px;color:var(--muted)}
.alt a{font-weight:700;color:var(--forest);text-decoration:underline;text-underline-offset:3px}
@media (max-width:560px){.two{grid-template-columns:1fr}.field input{font-size:16px}}
</style>
