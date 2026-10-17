<template>
<div class="dcroot">
<div style="width:100%;background:var(--bg)">
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">
    <SiteHeader variant="minimal" />

    <section class="lsec" style="display:flex;justify-content:center;padding:40px 0 96px">
      <div class="card lcard" style="flex:1 1 400px;max-width:460px;min-width:0;border-radius:20px;padding:40px;box-sizing:border-box">
        <h1 class="lh1" style="margin:0 0 10px;font-size:32px;line-height:1.1;font-weight:800;letter-spacing:-1px">Reset password</h1>
        <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:var(--muted)">Choose a new password for <strong>{{ email || 'your account' }}</strong>.</p>
        <p v-if="!token || !email" role="alert" style="margin:0 0 18px;padding:14px 18px;border-radius:18px;background:var(--danger-bg);font-size:14px;line-height:1.6;color:var(--danger);font-weight:600">This reset link is incomplete. Please request a new one.</p>
        <form v-if="!done" novalidate style="display:flex;flex-direction:column;gap:16px" @submit.prevent="submit">
          <p v-if="errors.form" class="err" role="alert" style="margin:0;padding:12px 16px;border-radius:14px;background:var(--danger-bg)">{{ errors.form }}</p>
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
@media (max-width:900px){.field input{font-size:16px!important}}
@media (max-width:600px){
.wrap{padding-left:16px!important;padding-right:16px!important}
.lsec{padding:16px 0 48px!important}
.lh1{font-size:28px!important;overflow-wrap:anywhere}
.lcard{padding:24px 18px!important;border-radius:16px!important}
}
</style>
