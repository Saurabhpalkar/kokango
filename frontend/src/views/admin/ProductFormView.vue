<template>
<AdminLayout active="products">
  <div class="hd">
    <div><h1>{{ isNew ? 'Add product' : 'Edit product' }}</h1><div class="sub"><RouterLink to="/admin/products" style="color:var(--green);font-weight:700">&larr; Back to products</RouterLink></div></div>
  </div>

  <div v-if="loading" class="card state" style="border-radius:30px">Loading product&hellip;</div>

  <div v-else-if="notFound" class="card" style="border-radius:30px;padding:28px">
    {{ loadError }} <RouterLink to="/admin/products" style="color:var(--green);font-weight:700">Back to products</RouterLink>
  </div>

  <form v-else novalidate @submit.prevent="save" style="display:flex;flex-wrap:wrap;gap:24px;align-items:flex-start">
    <section class="card" style="flex:2 1 520px;border-radius:30px;padding:28px;box-sizing:border-box;min-width:0">
      <div v-if="err" class="errbox" role="alert" style="margin-bottom:16px"><span>{{ err }}</span></div>
      <div class="grid">
        <div><label class="lbl" for="pn">NAME</label><input id="pn" class="in" v-model="f.name"><span v-if="errors.name" class="err">{{ errors.name }}</span></div>
        <div><label class="lbl" for="ph">HINDI NAME</label><input id="ph" class="in" v-model="f.hindi_name"><span v-if="errors.hindi_name" class="err">{{ errors.hindi_name }}</span></div>
        <div><label class="lbl" for="pc">CATEGORY</label><select id="pc" v-model="f.category_id"><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}{{ c.is_active ? '' : ' (inactive)' }}</option></select><span v-if="errors.category_id" class="err">{{ errors.category_id }}</span></div>
        <div><label class="lbl" for="pb">BADGE</label><input id="pb" class="in" v-model="f.badge" placeholder="e.g. Bestseller" maxlength="20"><span v-if="errors.badge" class="err">{{ errors.badge }}</span></div>
        <div style="grid-column:1/-1"><label class="lbl" for="pd">DESCRIPTION</label><textarea id="pd" class="in" rows="4" v-model="f.description"></textarea><span v-if="errors.description" class="err">{{ errors.description }}</span></div>
        <div style="grid-column:1/-1;display:flex;align-items:center;gap:12px">
          <input id="pa" type="checkbox" v-model="f.is_active" style="width:20px;height:20px;accent-color:var(--green)">
          <label for="pa" style="font-size:14px;font-weight:600">Active (visible in the store)</label>
        </div>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin:28px 0 12px;gap:12px;flex-wrap:wrap">
        <h2 style="margin:0;font-size:19px;font-weight:800">Variants</h2>
        <button class="ghost" type="button" @click="addVariant">+ Add variant</button>
      </div>
      <span v-if="errors.variants" class="err" style="display:block;margin-bottom:8px">{{ errors.variants }}</span>
      <div style="display:flex;flex-direction:column;gap:12px">
        <div v-for="(v, i) in f.variants" :key="v.key" class="vrow" :data-testid="'variant-' + i">
          <div><label class="lbl" :for="'vs' + i">SIZE</label><input :id="'vs' + i" class="in" v-model="v.size_label" placeholder="e.g. 100g"><span v-if="errors['variants.' + i + '.size_label']" class="err">{{ errors['variants.' + i + '.size_label'] }}</span></div>
          <div><label class="lbl" :for="'vk' + i">SKU</label><input :id="'vk' + i" class="in" v-model="v.sku"><span v-if="errors['variants.' + i + '.sku']" class="err">{{ errors['variants.' + i + '.sku'] }}</span></div>
          <div><label class="lbl" :for="'vp' + i">PRICE (&#8377;)</label><input :id="'vp' + i" class="in" type="number" min="0" step="0.01" v-model="v.price"><span v-if="errors['variants.' + i + '.price_paise']" class="err">{{ errors['variants.' + i + '.price_paise'] }}</span></div>
          <div><label class="lbl" :for="'vm' + i">MRP (&#8377;)</label><input :id="'vm' + i" class="in" type="number" min="0" step="0.01" v-model="v.mrp"><span v-if="errors['variants.' + i + '.mrp_paise']" class="err">{{ errors['variants.' + i + '.mrp_paise'] }}</span></div>
          <div><label class="lbl" :for="'vt' + i">STOCK</label><input :id="'vt' + i" class="in" type="number" min="0" step="1" v-model="v.stock"><span v-if="errors['variants.' + i + '.stock']" class="err">{{ errors['variants.' + i + '.stock'] }}</span></div>
          <div class="vend">
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600"><input type="checkbox" v-model="v.is_active" style="width:18px;height:18px;accent-color:var(--green)">Active</label>
            <button class="ghost danger" type="button" :disabled="f.variants.length < 2" :aria-label="'Remove variant ' + (i + 1)" @click="removeVariant(i)">Remove</button>
          </div>
          <span v-if="errors['variants.' + i + '.id']" class="err" style="grid-column:1/-1">{{ errors['variants.' + i + '.id'] }}</span>
        </div>
      </div>
      <div v-if="!isNew" style="font-size:12px;color:var(--muted);margin-top:10px">Removing a saved variant deletes it, or deactivates it if it already has orders.</div>

      <div style="display:flex;gap:12px;margin-top:24px;flex-wrap:wrap;align-items:center">
        <button class="btn" type="submit" :disabled="saving">{{ saving ? 'Saving…' : (isNew ? 'Add product' : 'Save changes') }}</button>
        <RouterLink class="ghost" to="/admin/products">Cancel</RouterLink>
      </div>
    </section>

    <aside class="card" style="flex:1 1 300px;border-radius:30px;padding:28px;box-sizing:border-box">
      <div class="lbl">IMAGE</div>
      <div style="height:200px;border-radius:20px;background:var(--tint);display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:16px"><img v-if="preview" :src="preview" alt="Product preview" style="height:100%;width:auto;max-width:100%;object-fit:contain"><span v-else style="color:var(--muted);font-size:13px">No image</span></div>
      <label class="lbl" for="pf">UPLOAD IMAGE</label>
      <input id="pf" ref="fileEl" type="file" accept="image/png,image/jpeg,image/webp" class="in" style="height:auto;padding:10px 12px;font-weight:500" @change="onFile">
      <div style="font-size:12px;color:var(--muted);margin:6px 0 0">JPG, PNG or WebP, up to 2 MB. Uploaded when you save.</div>
      <span v-if="errors.image" class="err" style="display:block;margin-top:6px">{{ errors.image }}</span>
      <button v-if="file" class="ghost" type="button" style="margin-top:10px" @click="clearFile">Remove selected file</button>
      <label class="lbl" for="pi" style="margin-top:18px">OR CHOOSE EXISTING IMAGE</label>
      <select id="pi" :value="f.image_url" @change="pickUrl($event.target.value)"><option value="">None</option><option v-if="f.image_url && !images.includes(f.image_url)" :value="f.image_url">Current image</option><option v-for="i in images" :key="i" :value="i">{{ i.replace('/images/', '') }}</option></select>
    </aside>
  </form>
</AdminLayout>
</template>

<script setup>
import { reactive, ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { apiError } from '@/services/api'
import { adminProducts, adminCategories } from '@/services/adminService'
import { rupeesToPaise } from '@/utils/format'
import { useToastStore } from '@/stores/toast'

const route = useRoute()
const router = useRouter()
const toast = useToastStore()
const isNew = computed(() => !route.params.id)
const images = ['jackfruit-chips.png', 'jamun-vadi.png', 'moringa-powder.png', 'curry-leaf-powder.png', 'tulsi-powder.png', 'neem-powder.png', 'beetroot-powder.png'].map((i) => '/images/' + i)

let keySeq = 0
const blankVariant = () => ({ key: ++keySeq, id: null, size_label: '', sku: '', price: '', mrp: '', stock: 0, is_active: true })
const f = reactive({ name: '', hindi_name: '', category_id: null, description: '', badge: '', is_active: true, image_url: '', variants: [blankVariant()] })
const categories = ref([])
const loading = ref(true)
const notFound = ref(false)
const loadError = ref('')
const err = ref('')
const errors = ref({})
const saving = ref(false)
const file = ref(null)
const filePreview = ref('')
const fileEl = ref(null)
const preview = computed(() => filePreview.value || f.image_url)

const rupees = (paise) => (paise === null || paise === undefined ? '' : String(paise / 100))

function setFile(fl) {
  if (filePreview.value) URL.revokeObjectURL(filePreview.value)
  file.value = fl || null
  filePreview.value = fl ? URL.createObjectURL(fl) : ''
}
function onFile(e) { setFile(e.target.files?.[0]) }
function clearFile() { setFile(null); if (fileEl.value) fileEl.value.value = '' }
function pickUrl(v) { f.image_url = v; clearFile() }
function addVariant() { f.variants.push(blankVariant()) }
function removeVariant(i) { if (f.variants.length > 1) f.variants.splice(i, 1) }

async function init() {
  loading.value = true; notFound.value = false; err.value = ''; errors.value = {}
  clearFile()
  try {
    categories.value = await adminCategories.list()
    if (isNew.value) {
      Object.assign(f, { name: '', hindi_name: '', category_id: categories.value[0]?.id ?? null, description: '', badge: '', is_active: true, image_url: '', variants: [blankVariant()] })
    } else {
      const p = await adminProducts.get(route.params.id)
      Object.assign(f, {
        name: p.name, hindi_name: p.hindi_name || '', category_id: p.category?.id ?? categories.value[0]?.id ?? null, description: p.description || '', badge: p.badge || '', is_active: !!p.is_active, image_url: p.image_url || '',
        variants: p.variants.length ? p.variants.map((v) => ({ key: ++keySeq, id: v.id, size_label: v.size_label, sku: v.sku, price: rupees(v.price_paise), mrp: rupees(v.mrp_paise), stock: v.stock, is_active: !!v.is_active })) : [blankVariant()],
      })
    }
  } catch (e) {
    const ae = apiError(e, 'Could not load the product.')
    loadError.value = ae.status === 404 ? 'Product not found.' : ae.message
    notFound.value = true
  } finally { loading.value = false }
  if (pendingError) { err.value = pendingError; pendingError = '' }
}
let pendingError = ''

function validate() {
  const e = {}
  if (!f.name.trim()) e.name = 'Name is required.'
  if (!f.category_id) e.category_id = 'Choose a category.'
  if (!f.variants.length) e.variants = 'Add at least one variant.'
  f.variants.forEach((v, i) => {
    const k = `variants.${i}.`
    if (!String(v.size_label).trim()) e[k + 'size_label'] = 'Size is required.'
    if (!String(v.sku).trim()) e[k + 'sku'] = 'SKU is required.'
    if (v.price === '' || !(Number(v.price) >= 0)) e[k + 'price_paise'] = 'Enter a valid price.'
    if (v.mrp !== '' && v.mrp !== null && !(Number(v.mrp) >= 0)) e[k + 'mrp_paise'] = 'Enter a valid MRP.'
    if (!Number.isInteger(Number(v.stock)) || Number(v.stock) < 0 || v.stock === '') e[k + 'stock'] = 'Stock must be a whole number.'
  })
  return e
}

function payload() {
  return {
    name: f.name.trim(), hindi_name: f.hindi_name.trim() || null, category_id: f.category_id, description: f.description.trim() || null, badge: f.badge.trim() || null, is_active: f.is_active, image_url: f.image_url || null,
    variants: f.variants.map((v) => ({
      ...(v.id ? { id: v.id } : {}), sku: v.sku.trim(), size_label: v.size_label.trim(), price_paise: rupeesToPaise(v.price),
      mrp_paise: v.mrp === '' || v.mrp === null ? null : rupeesToPaise(v.mrp), stock: Number(v.stock), is_active: v.is_active,
    })),
  }
}

async function save() {
  err.value = ''; errors.value = validate()
  if (Object.keys(errors.value).length) { err.value = 'Please fix the highlighted fields.'; return }
  saving.value = true
  let saved
  try {
    saved = isNew.value ? await adminProducts.create(payload()) : await adminProducts.update(route.params.id, payload())
  } catch (e) {
    const ae = apiError(e, 'Could not save the product.')
    errors.value = ae.errors
    err.value = ae.message
    saving.value = false
    return
  }
  if (file.value) {
    try { await adminProducts.uploadImage(saved.id, file.value) } catch (e) {
      const ae = apiError(e, 'Could not upload the image.')
      saving.value = false
      pendingError = `Product saved, but the image was not uploaded: ${ae.errors.image || ae.message}`
      if (isNew.value) await router.replace(`/admin/products/${saved.id}`)
      else { err.value = pendingError; pendingError = ''; errors.value = { image: ae.errors.image } }
      return
    }
  }
  saving.value = false
  toast.show(isNew.value ? 'Product added' : 'Product saved')
  router.push('/admin/products')
}

watch(f, () => { if (Object.keys(errors.value).length) { errors.value = validate(); if (!Object.keys(errors.value).length) err.value = '' } }, { deep: true })
watch(() => route.params.id, () => { if (/^\/admin\/products\/[^/]+$/.test(route.path)) init() })
onMounted(init)
onBeforeUnmount(() => setFile(null))
</script>

<style scoped>
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
.grid .err{display:block;margin-top:4px}
.vrow{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:10px;background:var(--bg);border:1px solid var(--line);border-radius:20px;padding:14px;align-items:start}
.vrow .err{display:block;margin-top:4px}
.vrow :deep(input.in){background:#fff}
.vend{grid-column:1/-1;display:flex;gap:12px;justify-content:space-between;align-items:center}
@media (min-width:1100px){.vrow{grid-template-columns:1fr 1.6fr 1fr 1fr .8fr}}
</style>
