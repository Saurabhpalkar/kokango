<template>
<AdminLayout active="settings">
  <div class="hd"><div><h1>Settings</h1><div class="sub">Store configuration</div></div></div>

  <div v-if="loading" class="card state" style="border-radius:30px;max-width:860px">Loading settings&hellip;</div>
  <div v-else-if="loadErr" class="errbox" role="alert" style="max-width:860px"><span>{{ loadErr }}</span><button type="button" @click="load">Retry</button></div>

  <form v-else novalidate @submit.prevent="save" style="display:flex;flex-direction:column;gap:24px;max-width:860px">
    <section class="card" style="border-radius:30px;padding:28px">
      <h2 style="margin:0 0 18px;font-size:19px;font-weight:800">Store details</h2>
      <div class="grid">
        <div><label class="lbl" for="sn">STORE NAME</label><input id="sn" class="in" v-model="s.store_name"><span v-if="errors.store_name" class="err">{{ errors.store_name }}</span></div>
        <div><label class="lbl" for="sp">STORE PHONE</label><input id="sp" class="in" v-model="s.store_phone"><span v-if="errors.store_phone" class="err">{{ errors.store_phone }}</span></div>
        <div><label class="lbl" for="se">STORE EMAIL</label><input id="se" class="in" type="email" v-model="s.store_email"><span v-if="errors.store_email" class="err">{{ errors.store_email }}</span></div>
        <div><label class="lbl" for="sw">WHATSAPP NUMBER</label><input id="sw" class="in" v-model="s.whatsapp_number"><span v-if="errors.whatsapp_number" class="err">{{ errors.whatsapp_number }}</span></div>
        <div style="grid-column:1/-1"><label class="lbl" for="sa">ADDRESS</label><input id="sa" class="in" v-model="s.store_address"><span v-if="errors.store_address" class="err">{{ errors.store_address }}</span></div>
      </div>
    </section>

    <section class="card" style="border-radius:30px;padding:28px">
      <h2 style="margin:0 0 18px;font-size:19px;font-weight:800">Shipping &amp; tax</h2>
      <div class="grid">
        <div><label class="lbl" for="sc">STANDARD SHIPPING (&#8377;)</label><input id="sc" class="in" type="number" min="0" step="0.01" v-model="s.shipping_standard"><span v-if="errors.shipping_standard_paise" class="err">{{ errors.shipping_standard_paise }}</span></div>
        <div><label class="lbl" for="sx">EXPRESS SHIPPING (&#8377;)</label><input id="sx" class="in" type="number" min="0" step="0.01" v-model="s.shipping_express"><span v-if="errors.shipping_express_paise" class="err">{{ errors.shipping_express_paise }}</span></div>
        <div><label class="lbl" for="fs">FREE SHIPPING ABOVE (&#8377;)</label><input id="fs" class="in" type="number" min="0" step="0.01" v-model="s.free_above"><span v-if="errors.free_shipping_above_paise" class="err">{{ errors.free_shipping_above_paise }}</span></div>
        <div><label class="lbl" for="gst">GST (%)</label><input id="gst" class="in" type="number" min="0" max="100" step="1" v-model="s.gst_percent"><span v-if="errors.gst_percent" class="err">{{ errors.gst_percent }}</span></div>
        <div style="grid-column:1/-1"><label class="lbl" for="fn">FREE-SHIPPING NOTE</label><input id="fn" class="in" v-model="s.free_shipping_note"><span v-if="errors.free_shipping_note" class="err">{{ errors.free_shipping_note }}</span></div>
      </div>
    </section>

    <section class="card" style="border-radius:30px;padding:28px">
      <h2 style="margin:0 0 18px;font-size:19px;font-weight:800">Notifications</h2>
      <label class="lbl" for="ne">NOTIFICATION EMAIL</label><input id="ne" class="in" type="email" v-model="s.notification_email"><span v-if="errors.notification_email" class="err">{{ errors.notification_email }}</span>
    </section>

    <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap"><button class="btn" type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save settings' }}</button><span v-if="saved" class="ok">Saved</span><span v-if="err" class="err" role="alert">{{ err }}</span></div>
  </form>
</AdminLayout>
</template>

<script setup>
import { reactive, ref, onMounted, onBeforeUnmount } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { apiError } from '@/services/api'
import { adminSettings } from '@/services/adminService'
import { rupeesToPaise } from '@/utils/format'

const s = reactive({})
const loading = ref(true)
const loadErr = ref('')
const saving = ref(false)
const saved = ref(false)
const err = ref('')
const errors = ref({})
let t

const rupees = (p) => String((Number(p) || 0) / 100)
function fill(d) {
  Object.assign(s, {
    store_name: d.store_name || '', store_phone: d.store_phone || '', store_email: d.store_email || '', store_address: d.store_address || '', whatsapp_number: d.whatsapp_number || '',
    shipping_standard: rupees(d.shipping_standard_paise), shipping_express: rupees(d.shipping_express_paise), free_above: rupees(d.free_shipping_above_paise),
    free_shipping_note: d.free_shipping_note || '', gst_percent: String(d.gst_percent ?? 0), notification_email: d.notification_email || '',
  })
}
async function load() {
  loading.value = true; loadErr.value = ''
  try { fill(await adminSettings.get()) } catch (e) { loadErr.value = apiError(e, 'Could not load settings.').message } finally { loading.value = false }
}

async function save() {
  err.value = ''; saved.value = false; errors.value = {}
  const e = {}
  for (const [k, f] of [['shipping_standard_paise', 'shipping_standard'], ['shipping_express_paise', 'shipping_express'], ['free_shipping_above_paise', 'free_above']]) if (s[f] === '' || !(Number(s[f]) >= 0)) e[k] = 'Enter an amount in rupees, 0 or more.'
  if (s.gst_percent === '' || !Number.isInteger(Number(s.gst_percent)) || Number(s.gst_percent) < 0 || Number(s.gst_percent) > 100) e.gst_percent = 'GST must be a whole number from 0 to 100.'
  if (!s.store_name.trim()) e.store_name = 'Store name is required.'
  if (Object.keys(e).length) { errors.value = e; err.value = 'Please fix the highlighted fields.'; return }
  saving.value = true
  try {
    fill(await adminSettings.save({
      store_name: s.store_name.trim(), store_phone: s.store_phone.trim(), store_email: s.store_email.trim(), store_address: s.store_address.trim(), whatsapp_number: s.whatsapp_number.trim(),
      shipping_standard_paise: rupeesToPaise(s.shipping_standard), shipping_express_paise: rupeesToPaise(s.shipping_express), free_shipping_above_paise: rupeesToPaise(s.free_above),
      free_shipping_note: s.free_shipping_note.trim(), gst_percent: Number(s.gst_percent), notification_email: s.notification_email.trim(),
    }))
    saved.value = true
    clearTimeout(t); t = setTimeout(() => { saved.value = false }, 3000)
  } catch (x) { const ae = apiError(x, 'Could not save settings.'); errors.value = ae.errors; err.value = ae.message } finally { saving.value = false }
}
onMounted(load)
onBeforeUnmount(() => clearTimeout(t))
</script>

<style scoped>
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
.err{display:block;margin-top:4px}
</style>
