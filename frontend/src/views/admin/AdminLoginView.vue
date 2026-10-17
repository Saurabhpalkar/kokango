<template>
<div class="dcroot alogin">
  <aside class="apanel on-dark" aria-hidden="false">
    <a href="/" class="abrand"><img src="/images/logo.png" alt="Kokango logo"><span>Kokango</span><span class="abadge">ADMIN</span></a>
    <div class="apanel-text">
      <h2>Run the store with confidence.</h2>
      <p>Manage products, stock, orders, payments and shipments from one place.</p>
    </div>
    <div class="apanel-foot">&copy; Kokango</div>
  </aside>

  <main class="aform">
    <div class="acard card">
      <a href="/" class="abrand dark"><img src="/images/logo.png" alt="Kokango logo"><span>Kokango</span><span class="abadge">ADMIN</span></a>
      <h1>Admin login</h1>
      <p class="asub">Sign in to manage products, orders, payments and shipments.</p>
      <form novalidate class="aformf" @submit.prevent="submit">
        <label class="field">Email<input v-model="email" type="email" autocomplete="username" placeholder="admin@kokango.test"></label>
        <label class="field">Password<input v-model="password" type="password" autocomplete="current-password" placeholder="Enter your password"></label>
        <span v-if="error" class="alert-err" role="alert">{{ error }}</span>
        <button type="submit" class="btn" :disabled="busy">{{ busy ? 'Signing in…' : 'Login to admin' }}</button>
      </form>
      <div v-if="demo" class="ademo">
        <b>Local development login</b><br>Email: {{ demo.email }}<br>Password: {{ demo.password }}
        <div><button type="button" class="fill" @click="fillDemo">Fill demo details</button></div>
      </div>
      <a href="/" class="btn-line aback">Back to store</a>
    </div>
  </main>
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
.alogin{min-height:100vh;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.1fr)}
.apanel{display:flex;flex-direction:column;justify-content:space-between;gap:32px;padding:36px 48px;color:#fff;background:radial-gradient(circle at 85% 12%,rgba(55,146,55,.4),transparent 45%),linear-gradient(160deg,#103D2C,#0A2A1E)}
.abrand{display:inline-flex;align-items:center;gap:12px;font-weight:800;font-size:22px;letter-spacing:-.3px;color:#fff}
.abrand:hover{color:#fff}
.abrand img{height:40px;width:40px;object-fit:contain;background:#fff;border-radius:10px;padding:3px}
.abrand.dark{color:var(--ink);display:none;margin-bottom:22px}
.abrand.dark:hover{color:var(--ink)}
.abadge{font-size:10px;background:var(--mango);color:var(--ink);padding:3px 8px;border-radius:999px;font-weight:800;letter-spacing:.5px}
.apanel-text h2{margin:0 0 14px;font-size:clamp(30px,3.6vw,46px);line-height:1.08;font-weight:800;letter-spacing:-1.2px;max-width:14ch}
.apanel-text p{margin:0;max-width:380px;font-size:16px;line-height:1.7;color:#D3E2D6}
.apanel-foot{font-size:13px;color:#A9BFB0}
.aform{display:flex;align-items:center;justify-content:center;padding:40px 24px;background:var(--bg)}
.acard{width:100%;max-width:440px;padding:40px;box-sizing:border-box;border-radius:20px}
.acard h1{margin:0 0 8px;font-size:30px;line-height:1.1;font-weight:800;letter-spacing:-.8px}
.asub{margin:0 0 24px;font-size:15px;line-height:1.7;color:var(--muted)}
.aformf{display:flex;flex-direction:column;gap:16px}
.ademo{margin-top:20px;padding:14px 18px;border-radius:var(--r-md);background:var(--sun);font-size:13px;line-height:1.7;color:#4d4a35}
.fill{margin-top:8px;background:#fff;border:1.5px solid var(--line);border-radius:999px;padding:6px 16px;font-size:12px;font-weight:700;color:var(--ink);cursor:pointer}
.fill:hover{border-color:var(--forest);color:var(--forest)}
.aback{display:flex;margin-top:20px}
@media (max-width:900px){
  .alogin{grid-template-columns:1fr}
  .apanel{display:none}
  .abrand.dark{display:inline-flex}
  .aform{align-items:flex-start;padding:24px 16px 48px}
  .acard{padding:28px 20px;border-radius:16px}
  .field input{font-size:16px}
}
</style>
