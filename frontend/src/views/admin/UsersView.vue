<template>
<AdminLayout active="users">
  <div class="hd">
    <div><h1>Users</h1><div class="sub">{{ meta ? meta.total : 0 }} accounts &middot; admins, staff and customers</div></div>
    <div><button class="btn" type="button" @click="showForm = !showForm">{{ showForm ? 'Close' : '+ Add user' }}</button></div>
  </div>

  <div v-if="showForm" class="card" style="border-radius:30px;padding:26px 28px;margin-bottom:24px">
    <h2 style="margin:0 0 16px;font-size:19px;font-weight:800">Add user</h2>
    <form novalidate @submit.prevent="create">
      <div class="grid">
        <div><label class="lbl" for="un">NAME</label><input id="un" class="in" v-model="f.name" autocomplete="off"><span v-if="errors.name" class="err">{{ errors.name }}</span></div>
        <div><label class="lbl" for="ue">EMAIL</label><input id="ue" class="in" type="email" v-model="f.email" autocomplete="off"><span v-if="errors.email" class="err">{{ errors.email }}</span></div>
        <div><label class="lbl" for="up">PASSWORD</label><input id="up" class="in" type="password" v-model="f.password" autocomplete="new-password"><span v-if="errors.password" class="err">{{ errors.password }}</span></div>
        <div><label class="lbl" for="ur">ROLE</label><select id="ur" v-model="f.role"><option v-for="r in roles" :key="r" :value="r">{{ label(r) }}</option></select><span v-if="errors.role" class="err">{{ errors.role }}</span></div>
      </div>
      <div style="display:flex;gap:12px;margin-top:20px;align-items:center;flex-wrap:wrap"><button class="btn" type="submit" :disabled="creating">{{ creating ? 'Adding…' : 'Add user' }}</button><button class="ghost" type="button" @click="showForm = false">Cancel</button><span v-if="formErr" class="err" role="alert">{{ formErr }}</span></div>
    </form>
  </div>

  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div v-if="notice" class="banner">{{ notice }}</div>

  <div class="card" style="border-radius:30px;padding:16px 26px">
    <div style="overflow-x:auto">
    <table class="rt" style="width:100%;border-collapse:collapse;min-width:820px">
      <thead><tr><th>USER</th><th>ROLE</th><th>CHANGE ROLE</th><th>STATUS</th><th></th></tr></thead>
      <tbody>
        <tr v-for="u in rows" :key="u.id">
          <td data-label="User"><div style="display:flex;align-items:center;gap:12px"><span style="flex:0 0 38px;height:38px;border-radius:50%;background:var(--tint);color:var(--green);display:inline-flex;align-items:center;justify-content:center;font-weight:800">{{ initial(u.name) }}</span><div style="font-weight:700">{{ u.name }}<div style="font-size:12px;color:var(--muted);font-weight:500">{{ u.email }}</div></div></div></td>
          <td data-label="Role"><span class="badge" :style="u.is_admin ? 'background:#FFF1CC;color:#7A4A00' : 'background:#E1ECFB;color:#1D4E9E'">{{ label(u.roles[0] || 'customer') }}</span></td>
          <td data-label="Change Role"><select style="height:38px;width:150px;padding:0 10px;font-size:13px" :value="u.roles[0]" :disabled="u.busy || isMe(u)" :aria-label="'Role for ' + u.name" @change="changeRole(u, $event.target.value)"><option v-for="r in roles" :key="r" :value="r">{{ label(r) }}</option></select></td>
          <td data-label="Status"><span class="badge" :style="u.is_active ? 'background:#EAF3DD;color:#0B5D1E' : 'background:#EDEDE4;color:#3b4a3f'">{{ u.is_active ? 'Active' : 'Inactive' }}</span></td>
          <td data-label="">
            <button class="ghost" :disabled="u.busy || isMe(u)" :aria-label="(u.is_active ? 'Deactivate ' : 'Activate ') + u.name" @click="toggle(u)">{{ u.is_active ? 'Deactivate' : 'Activate' }}</button>
            <span v-if="isMe(u)" style="font-size:12px;color:var(--muted);margin-left:8px">You</span>
            <div v-if="u.err" class="err" role="alert" style="margin-top:4px">{{ u.err }}</div>
          </td>
        </tr>
        <tr v-if="loading && !rows.length"><td colspan="5" class="state">Loading users&hellip;</td></tr>
        <tr v-else-if="!rows.length && !error"><td colspan="5" style="color:var(--muted)">No users found.</td></tr>
      </tbody>
    </table>
    </div>
    <AdminPager :meta="meta" @page="go" />
  </div>
</AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AdminPager from './AdminPager.vue'
import { apiError } from '@/services/api'
import { adminUsers } from '@/services/adminService'
import { useAuthStore } from '@/stores/auth'
import { label, initial } from './adminUi'

const auth = useAuthStore()
const rows = ref([])
const meta = ref(null)
const roles = ref(['admin', 'customer'])
const page = ref(1)
const loading = ref(false)
const error = ref('')
const notice = ref('')
const showForm = ref(false)
const f = reactive({ name: '', email: '', password: '', role: 'customer' })
const errors = ref({})
const formErr = ref('')
const creating = ref(false)
let seq = 0
const isMe = (u) => auth.user?.id === u.id
const wrap = (u) => ({ ...u, busy: false, err: '' })

async function load() {
  const my = ++seq
  loading.value = true; error.value = ''
  try {
    const res = await adminUsers.list({ page: page.value, per_page: 10 })
    if (my !== seq) return
    rows.value = res.data.map(wrap); meta.value = res.meta
  } catch (e) { if (my === seq) error.value = apiError(e, 'Could not load users.').message } finally { if (my === seq) loading.value = false }
}
function go(p) { page.value = p; load() }

async function create() {
  errors.value = {}; formErr.value = ''
  const e = {}
  if (!f.name.trim()) e.name = 'Name is required.'
  if (!f.email.trim()) e.email = 'Email is required.'
  if (f.password.length < 8) e.password = 'Password must be at least 8 characters.'
  if (Object.keys(e).length) { errors.value = e; return }
  creating.value = true
  try {
    const u = await adminUsers.create({ name: f.name.trim(), email: f.email.trim(), password: f.password, role: f.role })
    notice.value = `Added ${u.name} as ${label(u.roles[0])}.`
    Object.assign(f, { name: '', email: '', password: '', role: 'customer' }); showForm.value = false
    await load(); if (meta.value && meta.value.last_page > page.value) { page.value = meta.value.last_page; await load() } // new accounts sort last
  } catch (x) { const ae = apiError(x, 'Could not add the user.'); errors.value = ae.errors; formErr.value = ae.message } finally { creating.value = false }
}

async function patch(u, body, done) {
  u.busy = true; u.err = ''; notice.value = ''
  try { Object.assign(u, await adminUsers.update(u.id, body)); notice.value = done(u) }
  catch (x) { u.err = apiError(x, 'Could not update the user.').message; await load() } finally { u.busy = false }
}
const toggle = (u) => patch(u, { is_active: !u.is_active }, (x) => `${x.name} is now ${x.is_active ? 'active' : 'inactive'}.`)
const changeRole = (u, role) => patch(u, { role }, (x) => `${x.name} is now ${label(x.roles[0])}.`)

onMounted(async () => {
  load()
  try { const r = await adminUsers.roles(); if (r?.length) { roles.value = r; if (!r.includes(f.role)) f.role = r[0] } } catch (e) { /* keep defaults */ }
})
</script>

<style scoped>
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
.grid .err{display:block;margin-top:4px}
</style>
