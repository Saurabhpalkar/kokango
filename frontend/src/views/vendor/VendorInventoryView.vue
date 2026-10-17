<template>
<VendorLayout active="inventory">
  <div class="hd">
    <div><h1>Inventory</h1><div class="sub">{{ rows.length }} variants &middot; {{ lowCount }} low on stock</div></div>
    <div><label class="sr-only" for="q">Search inventory</label><input id="q" v-model="q" class="s" placeholder="Search product or SKU"></div>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div class="card" style="padding:16px 26px;box-sizing:border-box">
    <div style="overflow-x:auto">
    <table class="rt" style="width:100%;border-collapse:collapse;min-width:640px">
      <thead><tr><th>Product</th><th>Size</th><th>SKU</th><th>In stock</th><th>Set stock</th></tr></thead>
      <tbody>
        <tr v-for="r in rows" :key="r.variant_id" :class="{ low: r.low }">
          <td data-label="Product"><div style="display:flex;align-items:center;gap:12px"><div class="thumb"><img v-if="r.image_url" :src="r.image_url" alt=""></div><b>{{ r.product_name }}</b></div></td>
          <td data-label="Size">{{ r.size_label }}</td>
          <td data-label="SKU" class="muted">{{ r.sku || '—' }}</td>
          <td data-label="In stock"><b :style="`color:${r.low ? 'var(--danger)' : 'var(--ink)'}`">{{ r.stock }}</b> <span v-if="r.low" class="badge" style="background:var(--danger-bg);color:var(--danger)">Low stock</span></td>
          <td data-label="Set stock"><form class="adj" @submit.prevent="save(r)"><input v-model.number="edit[r.variant_id]" type="number" min="0" class="s num" :aria-label="`Stock for ${r.product_name} ${r.size_label}`"><button class="btn btn-sm" :disabled="busyId === r.variant_id || edit[r.variant_id] === r.stock">Save</button></form></td>
        </tr>
        <tr v-if="loading && !rows.length"><td colspan="5" class="state">Loading inventory&hellip;</td></tr>
        <tr v-else-if="!rows.length && !error"><td colspan="5" style="color:var(--muted)">No products yet.</td></tr>
      </tbody>
    </table>
    </div>
  </div>
</VendorLayout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import VendorLayout from '@/layouts/VendorLayout.vue'
import { vendorInventory } from '@/services/vendorService'
import { apiError } from '@/services/api'
import { debounce } from '@/services/adminGuard'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const rows = ref([]); const q = ref(''); const loading = ref(false); const error = ref(''); const busyId = ref(null); const edit = reactive({})
const lowCount = computed(() => rows.value.filter((r) => r.low).length)
async function load() {
  loading.value = true; error.value = ''
  try { rows.value = await vendorInventory.list({ search: q.value }); rows.value.forEach((r) => { edit[r.variant_id] = r.stock }) }
  catch (e) { error.value = apiError(e, 'Could not load inventory.').message } finally { loading.value = false }
}
watch(q, debounce(load, 300))
async function save(r) {
  busyId.value = r.variant_id
  try { await vendorInventory.adjust(r.variant_id, edit[r.variant_id]); toast.show('Stock updated'); await load() }
  catch (e) { error.value = apiError(e, 'Could not update stock.').message } finally { busyId.value = null }
}
onMounted(load)
</script>

<style scoped>
.thumb{flex:0 0 44px;height:44px;border-radius:10px;background:var(--tint);display:flex;align-items:center;justify-content:center;overflow:hidden}
.thumb img{height:100%;width:auto;object-fit:contain}
.muted{color:var(--muted)}
.adj{display:flex;gap:8px;align-items:center}
tr.low td{background:#FFF8F6}
</style>
