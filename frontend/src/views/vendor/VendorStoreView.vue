<template>
<VendorLayout active="store">
  <div class="hd">
    <div><h1>Store settings</h1><div class="sub">How your store appears to Kokango and where payouts go</div></div>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span><button type="button" @click="load">Retry</button></div>
  <div v-if="loading && !f" class="state">Loading store&hellip;</div>
  <form v-if="f" class="grid" novalidate @submit.prevent="save">
    <section class="card pad">
      <h2>Store profile</h2>
      <label class="field">Store name<input v-model="f.store_name" :class="{ bad: errs.store_name }"><span v-if="errs.store_name" class="err">{{ errs.store_name }}</span></label>
      <label class="field">Owner name<input v-model="f.owner_name" :class="{ bad: errs.owner_name }"><span v-if="errs.owner_name" class="err">{{ errs.owner_name }}</span></label>
      <label class="field">About your store<textarea v-model="f.description" rows="4" class="ta" placeholder="Tell customers who makes these products."></textarea></label>
      <div class="two">
        <label class="field">Email<input v-model="f.email" type="email" :class="{ bad: errs.email }"><span v-if="errs.email" class="err">{{ errs.email }}</span></label>
        <label class="field">Phone<input v-model="f.phone" inputmode="tel"></label>
      </div>
    </section>
    <section class="card pad">
      <h2>Pickup &amp; tax</h2>
      <label class="field">Pickup address<textarea v-model="f.pickup_address" rows="3" class="ta"></textarea></label>
      <div class="two">
        <label class="field">City<input v-model="f.city"></label>
        <label class="field">Pincode<input v-model="f.pincode" inputmode="numeric" maxlength="6"></label>
      </div>
      <div class="two">
        <label class="field">FSSAI licence no. <small>(needed for approval)</small><input v-model="f.fssai_no" inputmode="numeric"></label>
        <label class="field">PAN <small>(optional)</small><input v-model="f.pan_no" autocapitalize="characters"></label>
      </div>
      <label class="field">GST number <small>(optional)</small><input v-model="f.gst_no" autocapitalize="characters"></label>
    </section>
    <section class="card pad">
      <h2>Bank account for payouts</h2>
      <label class="field">Account holder<input v-model="f.bank.account_name"></label>
      <div class="two">
        <label class="field">Account number<input v-model="f.bank.account_no" inputmode="numeric" autocomplete="off"></label>
        <label class="field">IFSC<input v-model="f.bank.ifsc" autocapitalize="characters"></label>
      </div>
      <label class="field">Bank name<input v-model="f.bank.bank_name"></label>
    </section>
    <div class="bar">
      <span class="badge" :style="statusStyle">{{ statusText }}</span>
      <button class="btn" :disabled="busy">{{ busy ? 'Saving…' : 'Save changes' }}</button>
    </div>
  </form>
</VendorLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import VendorLayout from '@/layouts/VendorLayout.vue'
import { vendorStore as api } from '@/services/vendorService'
import { useVendorStore } from '@/stores/vendor'
import { useToastStore } from '@/stores/toast'
import { apiError } from '@/services/api'

const vendor = useVendorStore(); const toast = useToastStore()
const f = ref(null); const loading = ref(false); const busy = ref(false); const error = ref(''); const errs = reactive({})
const statusText = computed(() => ({ approved: 'Store approved', pending: 'Awaiting approval', rejected: 'Not approved', suspended: 'Suspended' }[f.value?.status] || 'Store status'))
const statusStyle = computed(() => f.value?.status === 'approved' ? 'background:var(--tint);color:var(--forest)' : 'background:var(--sun);color:#7A4A00')

async function load() {
  loading.value = true; error.value = ''
  try { const v = await api.get(); f.value = { ...v, bank: { account_name: '', account_no: '', ifsc: '', bank_name: '', ...(v.bank || {}) } } }
  catch (e) { error.value = apiError(e, 'Could not load store settings.').message } finally { loading.value = false }
}
function validate() {
  Object.keys(errs).forEach((k) => delete errs[k])
  if (!f.value.store_name?.trim()) errs.store_name = 'Enter a store name.'
  if (!f.value.owner_name?.trim()) errs.owner_name = 'Enter the owner name.'
  if (!/^\S+@\S+\.\S+$/.test(f.value.email || '')) errs.email = 'Enter a valid email.'
  return !Object.keys(errs).length
}
async function save() {
  if (!validate()) return
  busy.value = true; error.value = ''
  try { const v = await api.save(f.value); vendor.setVendor(v); toast.show('Store settings saved') }
  catch (e) { error.value = apiError(e, 'Could not save settings.').message } finally { busy.value = false }
}
onMounted(load)
</script>

<style scoped>
.pad{padding:22px 24px;box-sizing:border-box}
.grid{display:grid;gap:24px;max-width:760px}
h2{margin:0 0 14px;font-size:18px;font-weight:800}
.two{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media (max-width:600px){.two{grid-template-columns:1fr}}
.bar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.ta{width:100%;box-sizing:border-box;min-height:96px;padding:12px 14px;border:1px solid #CFCBB6;border-radius:var(--r-sm);font-size:14px;font-family:inherit;font-weight:500;resize:vertical;color:var(--ink)}
</style>
