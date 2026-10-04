<template>
<div class="dcroot">
<div style="width:100%;min-height:900px;position:relative;overflow:hidden;background:var(--bg)">
  <div style="position:absolute;top:-180px;right:-120px;width:600px;height:600px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;bottom:-220px;left:-160px;width:560px;height:560px;border-radius:50%;background:var(--tint)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">
    <SiteHeader variant="minimal" />

    <section class="lsec" style="display:flex;justify-content:center;padding:40px 0 96px">
      <div class="card lcard" style="flex:1 1 400px;max-width:460px;min-width:0;border-radius:44px;padding:40px;box-sizing:border-box">
        <h1 class="lh1" style="margin:0 0 10px;font-size:32px;line-height:1.1;font-weight:800;letter-spacing:-1px">Reset password</h1>
        <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:var(--muted)">Choose a new password for <strong>{{ email || 'your account' }}</strong>.</p>
        <p v-if="!token || !email" role="alert" style="margin:0 0 18px;padding:14px 18px;border-radius:18px;background:#FBE4E0;font-size:14px;line-height:1.6;color:#C0392B;font-weight:600">This reset link is incomplete. Please request a new one.</p>
        <form v-if="!done" novalidate style="display:flex;flex-direction:column;gap:16px" @submit.prevent="submit">
          <p v-if="errors.form" class="err" role="alert" style="margin:0;padding:12px 16px;border-radius:14px;background:#FBE4E0">{{ errors.form }}</p>
          <label class="field">New password<input v-model="password" type="password" placeholder="At least 8 characters" autocomplete="new-password" :class="{ bad: errors.password }"><span v-if="errors.password" class="err" role="alert">{{ errors.password }}</span></label>
          <label class="field">Confirm new password<input v-model="confirm" type="password" placeholder="Re-enter your password" autocomplete="new-password" :class="{ bad: errors.confirm }"><span v-if="errors.confirm" class="err" role="alert">{{ errors.confirm }}</span></label>
          <button type="submit" class="btn" :disabled="busy || !token || !email" style="margin-top:4px">{{ busy ? 'Saving...' : 'Reset password' }}</button>
        </form>
        <p v-else role="status" style="margin:0 0 6px;padding:14px 18px;border-radius:18px;background:var(--tint);border:1px solid #cfe3b4;font-size:14px;line-height:1.6;color:var(--green);font-weight:600">Your password has been reset. You can log in with the new password now.</p>
        <div style="display:flex;align-items:center;gap:12px;margin:22px 0;font-size:12px;color:var(--muted)"><span style="flex:1;height:1px;background:var(--line)"></span>or<span style="flex:1;height:1px;background:var(--line)"></span></div>
        <a :href="done ? '/login' : '/forgot-password'" class="btn-line" style="display:flex">{{ done ? 'Go to login' : 'Request a new link' }}</a>
      </div>
    </section>
  </div>
</div>
</div>
</template>

<script setup>
import SiteHeader from '@/components/SiteHeader.vue'
import { reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import api, { apiError } from '@/services/api'

const route = useRoute()
const token = String(route.query.token || '')
const email = String(route.query.email || '')
const password = ref('')
const confirm = ref('')
const errors = reactive({})
const busy = ref(false)
const done = ref(false)

async function submit() {
  for (const k of Object.keys(errors)) delete errors[k]
  if (password.value.length < 8) errors.password = 'Password must be at least 8 characters.'
  if (!confirm.value) errors.confirm = 'Please confirm your new password.'
  else if (confirm.value !== password.value) errors.confirm = 'Passwords do not match.'
  if (Object.keys(errors).length) return
  busy.value = true
  try {
    await api.post('/auth/reset-password', { token, email, password: password.value, password_confirmation: confirm.value })
    done.value = true
  } catch (e) {
    const er = apiError(e)
    if (er.errors.password) errors.password = er.errors.password
    if (er.errors.password_confirmation) errors.confirm = er.errors.password_confirmation
    if (!Object.keys(errors).length) errors.form = er.errors.token || er.errors.email || er.message
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
@media (max-width:900px){.field input{font-size:16px!important}}
@media (max-width:600px){
.wrap{padding-left:16px!important;padding-right:16px!important}
.lsec{padding:16px 0 48px!important}
.lh1{font-size:28px!important;overflow-wrap:anywhere}
.lcard{padding:24px 18px!important;border-radius:28px!important}
}
</style>
