<template>
<AdminLayout active="inventory">
  <div class="hd">
    <div><h1>Inventory</h1><div class="sub">{{ rows.length }} variants &middot; {{ lowCount }} low on stock</div></div>
    <div><label style="position:absolute;left:-9999px" for="iq">Search inventory</label><input id="iq" v-model="q" class="s" placeholder="Search product, size or SKU"></div>
  </div>

  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>

  <div class="card" style="border-radius:30px;padding:16px 26px">
    <div style="overflow-x:auto">
    <table class="rt" style="width:100%;border-collapse:collapse;min-width:820px">
      <thead><tr><th>PRODUCT</th><th>VARIANT</th><th>SKU</th><th>STOCK</th><th>SET STOCK</th><th>REASON</th><th></th></tr></thead>
      <tbody>
        <tr v-for="r in rows" :key="r.variant_id" :style="r.low ? 'background:#FFF6F5' : ''">
          <td data-label="Product"><div style="display:flex;align-items:center;gap:12px"><div style="flex:0 0 48px;height:48px;border-radius:12px;background:var(--tint);display:flex;align-items:center;justify-content:center;overflow:hidden"><img v-if="r.image_url" :src="r.image_url" alt="" style="height:100%;width:auto;object-fit:contain"></div><span style="font-weight:700">{{ r.product_name }}</span></div></td>
          <td data-label="Variant">{{ r.size_label }}</td>
          <td data-label="SKU" style="color:var(--muted);white-space:nowrap">{{ r.sku }}</td>
          <td data-label="Stock"><span :style="`font-weight:800;color:${r.low ? '#B3261E' : 'var(--ink)'}`">{{ r.stock }}</span> <span v-if="r.low" class="badge" style="background:#FBE3E1;color:#B3261E;margin-left:6px">Low stock</span></td>
          <td data-label="Set Stock">
            <div style="display:flex;gap:6px;align-items:center">
              <button class="ghost" style="min-width:38px;padding:6px 0" :aria-label="'Decrease ' + r.product_name + ' ' + r.size_label" @click="r.edit = Math.max(0, (Number(r.edit) || 0) - 1)">&minus;</button>
              <input class="s num" type="number" min="0" step="1" v-model="r.edit" :aria-label="'Stock for ' + r.product_name + ' ' + r.size_label">
              <button class="ghost" style="min-width:38px;padding:6px 0" :aria-label="'Increase ' + r.product_name + ' ' + r.size_label" @click="r.edit = (Number(r.edit) || 0) + 1">+</button>
            </div>
          </td>
          <td data-label="Reason"><input class="s" style="min-width:130px;height:38px;width:150px" v-model="r.reason" placeholder="Optional" :aria-label="'Reason for ' + r.product_name + ' ' + r.size_label"></td>
          <td data-label=""><div style="display:flex;gap:10px;align-items:center"><button class="btn" style="min-height:38px;padding:6px 18px" :disabled="r.busy" @click="save(r)">Save</button><span v-if="r.justSaved" class="ok">Saved</span><span v-if="r.err" class="err">{{ r.err }}</span></div></td>
        </tr>
        <tr v-if="loading && !rows.length"><td colspan="7" class="state">Loading inventory&hellip;</td></tr>
        <tr v-else-if="!rows.length && !error"><td colspan="7" style="color:var(--muted)">No inventory matches your search.</td></tr>
      </tbody>
    </table>
    </div>
  </div>
</AdminLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { apiError } from '@/services/api'
import { adminInventory } from '@/services/adminService'
import { debounce } from '@/services/adminGuard'

const rows = ref([])
const q = ref('')
const loading = ref(false)
const error = ref('')
let seq = 0
const lowCount = computed(() => rows.value.filter((r) => r.low).length)
const wrap = (r) => ({ ...r, edit: r.stock, reason: '', busy: false, justSaved: false, err: '' })

async function load() {
  const my = ++seq
  loading.value = true; error.value = ''
  try { const list = await adminInventory.list({ search: q.value.trim() }); if (my === seq) rows.value = list.map(wrap) }
  catch (e) { if (my === seq) error.value = apiError(e, 'Could not load inventory.').message } finally { if (my === seq) loading.value = false }
}
watch(q, debounce(load))

async function save(r) {
  r.err = ''
  const n = Number(r.edit)
  if (r.edit === '' || !Number.isInteger(n) || n < 0) { r.err = 'Enter a whole number, 0 or more.'; return }
  r.busy = true
  try {
    const body = { stock: n }
    if (r.reason.trim()) body.reason = r.reason.trim()
    const row = await adminInventory.adjust(r.variant_id, body)
    Object.assign(r, row, { edit: row.stock, reason: '', justSaved: true })
    setTimeout(() => { r.justSaved = false }, 2500)
  } catch (e) { const ae = apiError(e, 'Could not save.'); r.err = ae.errors.stock || ae.message } finally { r.busy = false }
}
onMounted(load)
</script>
