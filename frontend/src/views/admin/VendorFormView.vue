<template>
<AdminLayout active="vendors">
  <div class="hd">
    <div><h1>Add vendor</h1><div class="sub">Create a seller account on their behalf</div></div>
    <RouterLink to="/admin/vendors" class="ghost">&larr; Back to vendors</RouterLink>
  </div>
  <div v-if="error" class="errbox" role="alert"><span>{{ error }}</span></div>
  <form class="mk-form" novalidate @submit.prevent="submit">
    <section class="card mk-pad">
      <h2>Store &amp; owner</h2>
      <label class="field">Store name<input v-model="f.store_name" :class="{ bad: errs.store_name }"><span v-if="errs.store_name" class="err">{{ errs.store_name }}</span></label>
      <div class="mk-two">
        <label class="field">Owner name<input v-model="f.owner_name" :class="{ bad: errs.owner_name }"><span v-if="errs.owner_name" class="err">{{ errs.owner_name }}</span></label>
        <label class="field">Phone<input v-model="f.phone" inputmode="tel" :class="{ bad: errs.phone }"><span v-if="errs.phone" class="err">{{ errs.phone }}</span></label>
      </div>
      <div class="mk-two">
        <label class="field">Login email<input v-model="f.email" type="email" :class="{ bad: errs.email }"><span v-if="errs.email" class="err">{{ errs.email }}</span></label>
        <label class="field">Temporary password<input v-model="f.password" type="text" autocomplete="off" :class="{ bad: errs.password }"><span v-if="errs.password" class="err">{{ errs.password }}</span></label>
      </div>
      <label class="field">About the store<textarea v-model="f.description" rows="3" class="mk-ta"></textarea></label>
    </section>
    <section class="card mk-pad">
      <h2>Pickup &amp; compliance</h2>
      <label class="field">Pickup address<textarea v-model="f.pickup_address" rows="2" class="mk-ta"></textarea></label>
      <div class="mk-two">
        <label class="field">City<input v-model="f.city" :class="{ bad: errs.city }"><span v-if="errs.city" class="err">{{ errs.city }}</span></label>
        <label class="field">Pincode<input v-model="f.pincode" inputmode="numeric" maxlength="6"></label>
      </div>
      <div class="mk-two">
        <label class="field">FSSAI licence no.<input v-model="f.fssai_no" inputmode="numeric"></label>
        <label class="field">GST number <small>(optional)</small><input v-model="f.gst_no" autocapitalize="characters"></label>
      </div>
      <label class="field">PAN <small>(optional)</small><input v-model="f.pan_no" autocapitalize="characters"></label>
    </section>
    <section class="card mk-pad">
      <h2>Bank account for payouts</h2>
      <label class="field">Account holder<input v-model="f.bank.account_name"></label>
      <div class="mk-two">
        <label class="field">Account number<input v-model="f.bank.account_no" inputmode="numeric" autocomplete="off"></label>
        <label class="field">IFSC<input v-model="f.bank.ifsc" autocapitalize="characters"></label>
      </div>
      <label class="field">Bank name<input v-model="f.bank.bank_name"></label>
    </section>
    <section class="card mk-pad">
      <h2>Commission &amp; status</h2>
      <label class="field">Commission % <small>(leave blank to use the marketplace default)</small><input v-model="f.commission_percent" inputmode="decimal" placeholder="Default" :class="{ bad: errs.commission_percent }"><span v-if="errs.commission_percent" class="err">{{ errs.commission_percent }}</span></label>
      <label class="mk-toggle"><input v-model="f.approve_now" type="checkbox"><span>Approve immediately<small>Skips the review step. Only use this when you have already verified their documents.</small></span></label>
    </section>
    <div class="mk-actions"><button class="btn" :disabled="busy">{{ busy ? 'Creating…' : 'Create vendor' }}</button><RouterLink to="/admin/vendors" class="ghost">Cancel</RouterLink></div>
  </form>
</AdminLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { adminVendors } from '@/services/adminMarketplaceService'
import { useToastStore } from '@/stores/toast'
import { apiError } from '@/services/api'

const router = useRouter(); const toast = useToastStore()
const f = reactive({ store_name: '', owner_name: '', phone: '', email: '', password: '', description: '', pickup_address: '', city: '', pincode: '', fssai_no: '', gst_no: '', pan_no: '', commission_percent: '', approve_now: false, bank: { account_name: '', account_no: '', ifsc: '', bank_name: '' } })
const errs = reactive({}); const busy = ref(false); const error = ref('')
function validate() {
  Object.keys(errs).forEach((k) => delete errs[k])
  if (!f.store_name.trim()) errs.store_name = 'Enter the store name.'
  if (!f.owner_name.trim()) errs.owner_name = 'Enter the owner name.'
  if (!/^\d{10}$/.test(f.phone.replace(/\s/g, ''))) errs.phone = 'Enter a 10-digit mobile number.'
  if (!/^\S+@\S+\.\S+$/.test(f.email)) errs.email = 'Enter a valid email.'
  if (f.password.length < 8) errs.password = 'Use at least 8 characters.'
  if (!f.city.trim()) errs.city = 'Enter the city.'
  if (f.commission_percent !== '' && !(Number(f.commission_percent) >= 0 && Number(f.commission_percent) <= 50)) errs.commission_percent = 'Enter 0–50.'
  return !Object.keys(errs).length
}
async function submit() {
  error.value = ''
  if (!validate()) return
  busy.value = true
  try { const v = await adminVendors.create({ ...f }); toast.show('Vendor created'); router.push('/admin/vendors/' + v.id) }
  catch (e) { const r = apiError(e, 'Could not create the vendor.'); error.value = r.message; Object.assign(errs, r.errors || {}) } finally { busy.value = false }
}
</script>
