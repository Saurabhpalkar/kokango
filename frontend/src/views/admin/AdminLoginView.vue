<template>
<div class="dcroot">
<div style="width:100%;min-height:100vh;position:relative;overflow:hidden;background:var(--bg)">
  <div style="position:absolute;top:-180px;right:-120px;width:600px;height:600px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;bottom:-220px;left:-160px;width:560px;height:560px;border-radius:50%;background:var(--tint)"></div>
  <div style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">
    <header style="padding:24px 0"><a href="/" style="display:inline-flex;align-items:center;gap:12px;font-weight:800;font-size:24px;letter-spacing:-0.5px"><img src="/images/logo.png" alt="Kokango logo" style="height:46px;width:auto"><span>Kokango</span><span style="font-size:11px;background:var(--mango);color:var(--ink);padding:3px 9px;border-radius:999px;letter-spacing:.5px">ADMIN</span></a></header>

    <section style="display:flex;justify-content:center;padding:40px 0 96px">
      <div class="card" style="flex:1 1 400px;max-width:460px;border-radius:44px;padding:40px;box-sizing:border-box">
        <h1 style="margin:0 0 10px;font-size:32px;line-height:1.1;font-weight:800;letter-spacing:-1px">Admin login</h1>
        <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:var(--muted)">Sign in to manage products, orders, payments and shipments.</p>
        <form novalidate style="display:flex;flex-direction:column;gap:16px" @submit.prevent="submit">
          <label class="field">Email<input v-model="email" type="email" autocomplete="username" placeholder="admin@kokango.test"></label>
          <label class="field">Password<input v-model="password" type="password" autocomplete="current-password" placeholder="Enter your password"></label>
          <span v-if="error" class="err" role="alert">{{ error }}</span>
          <button type="submit" class="btn" style="margin-top:4px" :disabled="busy">{{ busy ? 'Signing in…' : 'Login to admin' }}</button>
        </form>
        <div v-if="demo" style="margin-top:22px;padding:14px 18px;border-radius:18px;background:var(--sun);font-size:13px;line-height:1.7;color:#4d4a35">
          <b>Local development login</b><br>Email: {{ demo.email }}<br>Password: {{ demo.password }}
          <div><button type="button" class="fill" @click="fillDemo">Fill demo details</button></div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;margin:22px 0;font-size:12px;color:var(--muted)"><span style="flex:1;height:1px;background:var(--line)"></span>or<span style="flex:1;height:1px;background:var(--line)"></span></div>
        <a href="/" class="btn-line" style="display:flex">Back to store</a>
      </div>
    </section>
  </div>
</div>
</div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { apiError } from '@/services/api'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
// Local development helper only: the seeded admin from the backend seeder. Not shown in production builds.
const demo = import.meta.env.DEV ? { email: 'admin@kokango.test', password: 'Admin@12345' } : null
const email = ref('')
const password = ref('')
const error = ref('')
const busy = ref(false)

function fillDemo() { email.value = demo.email; password.value = demo.password }

async function submit() {
  error.value = ''
  if (!email.value.trim() || !password.value) { error.value = 'Enter your email and password.'; return }
  busy.value = true
  try {
    await auth.login(email.value.trim(), password.value)
    if (!auth.isAdmin) { await auth.logout(); error.value = 'This account does not have admin access. Please sign in with an admin account.'; return }
    const to = String(route.query.redirect || '')
    router.push(to.startsWith('/admin') ? to : '/admin')
  } catch (e) {
    error.value = apiError(e, 'Wrong email or password.').message
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
.err{font-size:13px;font-weight:600;color:#C0392B}
.fill{margin-top:8px;background:#fff;border:1.5px solid var(--line);border-radius:999px;padding:6px 16px;font-size:12px;font-weight:700;color:var(--ink)}
.fill:hover{border-color:var(--green);color:var(--green)}
input::placeholder{color:#8a948c}
</style>
