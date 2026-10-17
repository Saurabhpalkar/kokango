<template>
<VendorLayout active="products">
  <div class="hd">
    <div><h1>{{ isEdit ? 'Edit product' : 'Add product' }}</h1><div class="sub">{{ isEdit ? 'Update your listing' : 'New listings are reviewed by the Kokango team before going live.' }}</div></div>
    <RouterLink to="/vendor/products" class="ghost">&larr; Back to products</RouterLink>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span></div>
  <div v-if="loading" class="state">Loading product&hellip;</div>

  <form v-else novalidate class="cols" @submit.prevent="submit('pending_review')">
    <div class="main">
      <section class="card box">
        <h2>Basic details</h2>
        <label class="field">Product name<input v-model="f.name" :class="{ bad: errs.name }" placeholder="e.g. Moringa Powder"><span v-if="errs.name" class="err">{{ errs.name }}</span></label>
        <label class="field">Category
          <select v-model="f.category_id" :class="{ bad: errs.category_id }"><option value="" disabled>Select a category</option><option v-for="c in cats" :key="c.id" :value="c.id">{{ c.name }}</option></select>
          <span v-if="errs.category_id" class="err">{{ errs.category_id }}</span>
        </label>
        <label class="field">Description<textarea v-model="f.description" rows="5" class="ta" placeholder="What is it, how is it made, how should it be used?"></textarea></label>
      </section>

      <section class="card box">
        <div class="vh"><h2>Sizes &amp; pricing</h2><button type="button" class="ghost" @click="addVariant">+ Add size</button></div>
        <p v-if="errs.variants" class="err">{{ errs.variants }}</p>
        <div v-for="(v, i) in f.variants" :key="i" class="vrow">
          <label class="field">Size<input v-model="v.size_label" placeholder="100g" :class="{ bad: v.e_size }"></label>
          <label class="field">Price (&#8377;)<input v-model="v.price" inputmode="decimal" placeholder="199" :class="{ bad: v.e_price }"></label>
          <label class="field">MRP (&#8377;)<input v-model="v.mrp" inputmode="decimal" placeholder="249"></label>
          <label class="field">Stock<input v-model="v.stock" inputmode="numeric" placeholder="0"></label>
          <label class="field">SKU<input v-model="v.sku" placeholder="Optional"></label>
          <button type="button" class="ghost danger rm" :disabled="f.variants.length === 1" :aria-label="`Remove size ${i + 1}`" @click="f.variants.splice(i, 1)">Remove</button>
        </div>
      </section>
    </div>

    <aside class="side">
      <section class="card box">
        <h2>Product image</h2>
        <div class="drop" :class="{ has: preview }">
          <img v-if="preview" :src="preview" alt="Product preview">
          <span v-else>No image yet</span>
        </div>
        <label class="ghost pick">Choose image<input type="file" accept="image/*" class="sr-only" @change="pickImage"></label>
        <p class="hint">Use a clear photo on a plain background. Square images work best.</p>
      </section>
      <section class="card box">
        <h2>Publish</h2>
        <p class="hint">{{ vendor.vendor?.status === 'approved' ? 'Submit for review to publish. Save as draft to keep working.' : 'Your store is awaiting approval, so listings are saved for review.' }}</p>
        <button type="submit" class="btn btn-block" :disabled="busy">{{ busy ? 'Saving…' : (isEdit ? 'Save and submit for review' : 'Submit for review') }}</button>
        <button type="button" class="ghost btn-block draft" :disabled="busy" @click="submit('draft')">Save as draft</button>
      </section>
    </aside>
  </form>
</VendorLayout>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import VendorLayout from '@/layouts/VendorLayout.vue'
import { useVendorStore } from '@/stores/vendor'
import { vendorProducts } from '@/services/vendorService'
import * as catalogue from '@/services/catalogueService'
import { apiError } from '@/services/api'
import { useToastStore } from '@/stores/toast'

const route = useRoute(); const router = useRouter(); const vendor = useVendorStore(); const toast = useToastStore()
const id = computed(() => route.params.id); const isEdit = computed(() => !!id.value)
const loading = ref(false); const busy = ref(false); const error = ref(''); const errs = reactive({})
const cats = ref([{ id: 1, name: 'Herbal Powders' }, { id: 2, name: 'Snacks' }, { id: 3, name: 'Sweets' }])
const f = reactive({ name: '', category_id: '', description: '', image_url: '', variants: [] })
const preview = ref('')
const blank = () => ({ id: null, size_label: '', price: '', mrp: '', stock: '', sku: '' })
const addVariant = () => f.variants.push(blank())

function pickImage(e) {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  if (file.size > 2 * 1024 * 1024) { error.value = 'Please choose an image under 2 MB.'; return }
  error.value = ''
  const r = new FileReader()
  r.onload = () => { preview.value = r.result; f.image_url = r.result }
  r.readAsDataURL(file)
}

onMounted(async () => {
  if (!isEdit.value) f.variants = [blank()] // show the first size row at once, not after the categories load
  try { const list = await catalogue.categories(); if (Array.isArray(list) && list.length) cats.value = list.map((c) => ({ id: c.id, name: c.name })) } catch (e) { /* keep defaults */ }
  if (!isEdit.value) return
  loading.value = true
  try {
    const p = await vendorProducts.get(id.value)
    Object.assign(f, { name: p.name, category_id: p.category_id, description: p.description || '', image_url: p.image_url || '' })
    preview.value = p.image_url || ''
    f.variants = p.variants.map((v) => ({ id: v.id, size_label: v.size_label, price: String(v.price_paise / 100), mrp: String(v.mrp_paise / 100), stock: String(v.stock), sku: v.sku || '' }))
  } catch (e) { error.value = apiError(e, 'Could not load the product.').message } finally { loading.value = false }
})

function validate() {
  Object.keys(errs).forEach((k) => delete errs[k])
  if (!f.name.trim()) errs.name = 'Enter a product name.'
  if (!f.category_id) errs.category_id = 'Choose a category.'
  let bad = false
  f.variants.forEach((v) => {
    v.e_size = !v.size_label.trim(); v.e_price = !(Number(v.price) > 0)
    if (v.e_size || v.e_price) bad = true
  })
  if (bad) errs.variants = 'Each size needs a name and a price above zero.'
  return !Object.keys(errs).length
}
async function submit(status) {
  error.value = ''
  if (!validate()) return
  busy.value = true
  try {
    const cat = cats.value.find((c) => c.id === f.category_id)
    await vendorProducts.save(id.value || null, {
      name: f.name.trim(), description: f.description.trim(), category_id: f.category_id, category_name: cat?.name, image_url: f.image_url, status,
      variants: f.variants.map((v) => ({ id: v.id, size_label: v.size_label.trim(), sku: v.sku.trim(), price_paise: Math.round(Number(v.price) * 100), mrp_paise: Math.round(Number(v.mrp || v.price) * 100), stock: Number(v.stock) || 0 })),
    })
    toast.show(status === 'draft' ? 'Draft saved' : 'Submitted for review')
    router.push('/vendor/products')
  } catch (e) { error.value = apiError(e, 'Could not save the product.').message } finally { busy.value = false }
}
</script>

<style scoped>
.cols{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:24px;align-items:start}
.main,.side{display:flex;flex-direction:column;gap:24px;min-width:0}
.box{padding:24px;box-sizing:border-box;display:flex;flex-direction:column;gap:14px}
.box h2{margin:0;font-size:18px;font-weight:800}
.vh{display:flex;justify-content:space-between;align-items:center}
.ta{min-height:120px;padding:12px 14px;border:1px solid #CFCBB6;border-radius:var(--r-sm);font-size:14px;font-family:inherit;resize:vertical;color:var(--ink)}
.vrow{display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1.2fr auto;gap:10px;align-items:end;background:var(--bg);border:1px solid var(--line);border-radius:var(--r-md);padding:14px}
.vrow .field input{min-height:44px;width:100%}
.rm{min-height:44px}
.drop{aspect-ratio:1;border:2px dashed #CFCBB6;border-radius:var(--r-md);display:flex;align-items:center;justify-content:center;background:var(--bg);color:var(--muted);font-size:14px;overflow:hidden}
.drop.has{border-style:solid;border-color:var(--line);background:linear-gradient(160deg,#fff,var(--tint))}
.drop img{width:100%;height:100%;object-fit:contain}
.pick{cursor:pointer;justify-content:center}
.draft{margin-top:-4px}
@media (max-width:1100px){.cols{grid-template-columns:1fr}.side{order:-1}}
@media (max-width:760px){.vrow{grid-template-columns:1fr 1fr}.rm{grid-column:1/-1}.box{padding:18px 16px}}
</style>
