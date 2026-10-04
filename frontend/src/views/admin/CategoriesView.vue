<template>
<AdminLayout active="categories">
  <div class="hd"><div><h1>Categories</h1><div class="sub">{{ cats.length }} categories</div></div></div>

  <div class="card" style="border-radius:30px;padding:22px 26px;margin-bottom:24px">
    <form novalidate style="display:flex;gap:12px;flex-wrap:wrap;align-items:center" @submit.prevent="add">
      <label for="newcat" style="position:absolute;left:-9999px">New category name</label>
      <input id="newcat" v-model="newName" class="s" placeholder="New category name" style="flex:1 1 240px">
      <button class="btn" type="submit" :disabled="busy">Add category</button>
    </form>
    <div v-if="err" class="err" role="alert" style="margin-top:10px">{{ err }}</div>
    <div v-if="msg" class="ok" style="margin-top:10px">{{ msg }}</div>
  </div>

  <div class="card" style="border-radius:30px;padding:16px 26px">
    <div style="overflow-x:auto">
    <table class="rt" style="width:100%;border-collapse:collapse;min-width:640px">
      <thead><tr><th>CATEGORY</th><th>SLUG</th><th>PRODUCTS</th><th>STATUS</th><th></th></tr></thead>
      <tbody>
        <tr v-for="c in cats" :key="c.id">
          <td data-label="Category" style="font-weight:700">
            <form v-if="editing === c.id" novalidate style="display:flex;gap:8px;align-items:center;flex-wrap:wrap" @submit.prevent="saveRename(c)">
              <input v-model="editName" class="s" style="min-width:180px;height:38px" :aria-label="'Rename ' + c.name">
              <button class="btn" type="submit" style="min-height:38px;padding:6px 16px" :disabled="busy">Save</button>
              <button class="ghost" type="button" @click="editing = null">Cancel</button>
            </form>
            <span v-else>{{ c.name }}</span>
          </td>
          <td data-label="Slug" style="color:var(--muted)">{{ c.slug }}</td>
          <td data-label="Products" style="font-weight:700">{{ c.products_count }}</td>
          <td data-label="Status"><span class="badge" :style="c.is_active ? 'background:#EAF3DD;color:#0B5D1E' : 'background:#EDEDE4;color:#3b4a3f'">{{ c.is_active ? 'Active' : 'Inactive' }}</span></td>
          <td data-label="">
            <div v-if="confirming === c.id" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
              <span style="font-size:13px;font-weight:600;color:#B3261E">Delete {{ c.name }}?</span>
              <button class="ghost danger" :disabled="busy" @click="remove(c)">Yes, delete</button>
              <button class="ghost" @click="confirming = null">Keep</button>
            </div>
            <div v-else style="display:flex;gap:8px;flex-wrap:wrap"><button class="ghost" @click="startEdit(c)">Rename</button><button class="ghost" :disabled="busy" @click="toggle(c)">{{ c.is_active ? 'Deactivate' : 'Activate' }}</button><button class="ghost" @click="confirming = c.id">Delete</button></div>
            <div v-if="rowErr[c.id]" class="err" role="alert" style="margin-top:6px">{{ rowErr[c.id] }}</div>
          </td>
        </tr>
        <tr v-if="loading && !cats.length"><td colspan="5" class="state">Loading categories&hellip;</td></tr>
        <tr v-else-if="!cats.length && !loadErr"><td colspan="5" style="color:var(--muted)">No categories yet. Add one above.</td></tr>
      </tbody>
    </table>
    </div>
    <div v-if="loadErr" class="errbox" role="alert" style="margin:12px 0 4px"><span>{{ loadErr }}</span><button type="button" @click="load">Retry</button></div>
  </div>
</AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { apiError } from '@/services/api'
import { adminCategories } from '@/services/adminService'

const cats = ref([])
const loading = ref(false)
const loadErr = ref('')
const newName = ref('')
const err = ref('')
const msg = ref('')
const rowErr = ref({})
const editing = ref(null)
const editName = ref('')
const confirming = ref(null)
const busy = ref(false)

async function load() {
  loading.value = true; loadErr.value = ''
  try { cats.value = await adminCategories.list() } catch (e) { loadErr.value = apiError(e, 'Could not load categories.').message } finally { loading.value = false }
}
function reset() { err.value = ''; msg.value = ''; rowErr.value = {} }

async function add() {
  reset()
  const name = newName.value.trim()
  if (!name) { err.value = 'Please enter a category name.'; return }
  busy.value = true
  try { const c = await adminCategories.create({ name, is_active: true }); cats.value.push(c); msg.value = `Added "${c.name}".`; newName.value = '' }
  catch (e) { const ae = apiError(e, 'Could not add the category.'); err.value = ae.errors.name || ae.message } finally { busy.value = false }
}
function startEdit(c) { reset(); editing.value = c.id; editName.value = c.name; confirming.value = null }
async function saveRename(c) {
  reset()
  const name = editName.value.trim()
  if (!name) { rowErr.value = { [c.id]: 'Name is required.' }; return }
  busy.value = true
  try { Object.assign(c, await adminCategories.update(c.id, { name })); editing.value = null; msg.value = `Renamed to "${c.name}".` }
  catch (e) { const ae = apiError(e, 'Could not rename.'); rowErr.value = { [c.id]: ae.errors.name || ae.message } } finally { busy.value = false }
}
async function toggle(c) {
  reset(); busy.value = true
  try { Object.assign(c, await adminCategories.update(c.id, { name: c.name, is_active: !c.is_active })); msg.value = `${c.name} is now ${c.is_active ? 'active' : 'inactive'}.` }
  catch (e) { rowErr.value = { [c.id]: apiError(e).message } } finally { busy.value = false }
}
async function remove(c) {
  reset(); busy.value = true
  try { await adminCategories.remove(c.id); cats.value = cats.value.filter((x) => x.id !== c.id); confirming.value = null; msg.value = `Deleted "${c.name}".` }
  catch (e) { rowErr.value = { [c.id]: apiError(e, 'Could not delete the category.').message }; confirming.value = null } finally { busy.value = false }
}
onMounted(load)
</script>
