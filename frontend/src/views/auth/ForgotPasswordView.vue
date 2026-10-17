<template>
<div class="dcroot">
<div style="width:100%;background:var(--bg)">
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">
    <SiteHeader variant="minimal" />

    <section class="lsec" style="display:flex;justify-content:center;padding:40px 0 96px">
      <div class="card lcard" style="flex:1 1 400px;max-width:460px;min-width:0;border-radius:20px;padding:40px;box-sizing:border-box">
        <h1 class="lh1" style="margin:0 0 10px;font-size:32px;line-height:1.1;font-weight:800;letter-spacing:-1px">Forgot password?</h1>
        <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:var(--muted)">Enter the email you used to register and we will send you a link to reset your password.</p>
        <form novalidate style="display:flex;flex-direction:column;gap:16px" @submit.prevent="submit">
          <label class="field">Email<input v-model="email" type="email" placeholder="you@example.com" autocomplete="email" :class="{ bad: error }"><span v-if="error" class="err" role="alert">{{ error }}</span></label>
          <button type="submit" class="btn" :disabled="busy" style="margin-top:4px">{{ busy ? 'Sending...' : 'Send reset link' }}</button>
        </form>
        <p v-if="sent" role="status" style="margin:18px 0 0;padding:14px 18px;border-radius:18px;background:var(--tint);border:1px solid #cfe3b4;font-size:14px;line-height:1.6;color:var(--green);font-weight:600">If this email is registered, a reset link has been sent.</p>
        <div style="display:flex;align-items:center;gap:12px;margin:22px 0;font-size:12px;color:var(--muted)"><span style="flex:1;height:1px;background:var(--line)"></span>or<span style="flex:1;height:1px;background:var(--line)"></span></div>
        <a href="/login" class="btn-line" style="display:flex">Back to login</a>
      </div>
    </section>
  </div>
</div>
</div>
</template>

<script setup>
import SiteHeader from '@/components/SiteHeader.vue'
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { apiError } from '@/services/api'

const auth = useAuthStore()
const email = ref('')
const error = ref('')
const sent = ref(false)
const busy = ref(false)

async function submit() {
  sent.value = false
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
    error.value = 'Please enter a valid email address.'
    return
  }
  error.value = ''
  busy.value = true
  try { await auth.forgotPassword(email.value.trim()); sent.value = true }
  catch (e) { const er = apiError(e); error.value = er.errors.email || er.message }
  finally { busy.value = false }
}
</script>

<style scoped>
@media (max-width:900px){.field input{font-size:16px!important}}
@media (max-width:600px){
.wrap{padding-left:16px!important;padding-right:16px!important}
.lsec{padding:16px 0 48px!important}
.lh1{font-size:28px!important;overflow-wrap:anywhere}
.lcard{padding:24px 18px!important;border-radius:16px!important}
}
</style>
