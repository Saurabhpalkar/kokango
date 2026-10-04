<template>
<div class="dcroot">
<div style="width:100%;min-height:900px;position:relative;overflow:hidden;background:var(--bg)">
  <div style="position:absolute;top:-180px;right:-120px;width:600px;height:600px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;bottom:-220px;left:-160px;width:560px;height:560px;border-radius:50%;background:var(--tint)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">
    <SiteHeader variant="minimal" />

    <section class="lsec" style="display:flex;justify-content:center;padding:40px 0 96px">
      <div class="card lcard" style="flex:1 1 400px;max-width:460px;min-width:0;border-radius:44px;padding:40px;box-sizing:border-box">
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
@media (max-width:900px){.field input{font-size:16px!important}}
@media (max-width:600px){
.wrap{padding-left:16px!important;padding-right:16px!important}
.lsec{padding:16px 0 48px!important}
.lh1{font-size:28px!important;overflow-wrap:anywhere}
.lcard{padding:24px 18px!important;border-radius:28px!important}
}
</style>
