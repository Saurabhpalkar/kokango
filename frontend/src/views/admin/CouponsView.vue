<template>
<AdminLayout active="coupons">
  <div class="hd"><div><h1>Coupons</h1><div class="sub">Discount codes customers enter at checkout</div></div></div>

  <div class="card" style="border-radius:18px;padding:22px 26px;margin-bottom:24px">
    <h2 style="margin:0 0 14px;font-size:18px;font-weight:800">{{ editingId ? 'Edit coupon' : 'New coupon' }}</h2>
    <form novalidate class="cf" @submit.prevent="save">
      <label class="fl">Code<input v-model="f.code" class="s up" placeholder="e.g. MONSOON10" maxlength="40"><span v-if="errs.code" class="err">{{ errs.code }}</span></label>
      <label class="fl">Discount type
        <select v-model="f.type" class="s"><option value="percent">Percentage (%)</option><option value="fixed">Fixed amount (&#8377;)</option></select>
      </label>
      <label class="fl">{{ f.type === 'percent' ? 'Discount (%)' : 'Discount (₹)' }}<input v-model="f.value" class="s" inputmode="decimal" :placeholder="f.type === 'percent' ? '10' : '100'"><span v-if="errs.value" class="err">{{ errs.value }}</span></label>
      <label v-if="f.type === 'percent'" class="fl">Maximum discount (₹, optional)<input v-model="f.max" class="s" inputmode="decimal" placeholder="No limit"><span v-if="errs.max_discount_paise" class="err">{{ errs.max_discount_paise }}</span></label>
      <label class="fl">Minimum order (₹)<input v-model="f.min" class="s" inputmode="decimal" placeholder="0"></label>
      <label class="fl">Valid until (optional)<input v-model="f.ends" class="s" type="date"><span v-if="errs.ends_at" class="err">{{ errs.ends_at }}</span></label>
      <label class="fl">Total uses (optional)<input v-model="f.limit" class="s" inputmode="numeric" placeholder="Unlimited"></label>
      <label class="fl wide">Note for customers (optional)<input v-model="f.description" class="s" maxlength="255" placeholder="e.g. 10% off for the monsoon season"></label>
      <label class="chk"><input v-model="f.once" type="checkbox"> One use per customer</label>
      <label class="chk"><input v-model="f.active" type="checkbox"> Active</label>
      <div class="acts">
        <button class="btn" type="submit" :disabled="busy">{{ busy ? 'Saving…' : (editingId ? 'Save changes' : 'Create coupon') }}</button>
        <button v-if="editingId" class="ghost" type="button" @click="resetForm">Cancel</button>
      </div>
    </form>
    <div v-if="err" class="err" role="alert" style="margin-top:10px">{{ err }}</div>
    <div v-if="msg" class="ok" role="status" style="margin-top:10px">{{ msg }}</div>
  </div>

  <div class="card" style="border-radius:18px;padding:16px 26px">
    <div style="overflow-x:auto">
    <table class="rt" style="width:100%;border-collapse:collapse;min-width:760px">
      <thead><tr><th>CODE</th><th>DISCOUNT</th><th>MIN ORDER</th><th>USED</th><th>VALID UNTIL</th><th>STATUS</th><th></th></tr></thead>
      <tbody>
        <tr v-for="c in list" :key="c.id">
          <td data-label="Code"><b class="mono">{{ c.code }}</b><div v-if="c.description" class="mut">{{ c.description }}</div></td>
          <td data-label="Discount" style="font-weight:700">{{ c.type === 'percent' ? c.value + '%' : inrp(c.value) }}<div v-if="c.type === 'percent' && c.max_discount_paise" class="mut">up to {{ inrp(c.max_discount_paise) }}</div></td>
          <td data-label="Min order">{{ c.min_subtotal_paise ? inrp(c.min_subtotal_paise) : '—' }}</td>
          <td data-label="Used">{{ c.used_count }}{{ c.usage_limit ? ' / ' + c.usage_limit : '' }}</td>
          <td data-label="Valid until">{{ c.ends_at ? formatDate(c.ends_at) : 'No end date' }}</td>
          <td data-label="Status"><span class="badge" :style="STATE[c.state].style">{{ STATE[c.state].label }}</span></td>
          <td data-label="">
            <div v-if="confirming === c.id" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
              <span style="font-size:13px;font-weight:600;color:var(--danger)">Delete {{ c.code }}?</span>
              <button class="ghost danger" :disabled="busy" @click="remove(c)">Yes, delete</button>
              <button class="ghost" @click="confirming = null">Keep</button>
            </div>
            <div v-else style="display:flex;gap:8px;flex-wrap:wrap"><button class="ghost" @click="edit(c)">Edit</button><button class="ghost" :disabled="busy" @click="toggle(c)">{{ c.is_active ? 'Switch off' : 'Switch on' }}</button><button class="ghost" @click="confirming = c.id">Delete</button></div>
          </td>
        </tr>
        <tr v-if="loading && !list.length"><td colspan="7" class="state">Loading coupons&hellip;</td></tr>
        <tr v-else-if="!list.length && !loadErr"><td colspan="7" style="color:var(--muted)">No coupons yet. Create your first one above.</td></tr>
      </tbody>
    </table>
    </div>
    <div v-if="loadErr" class="errbox" role="alert" style="margin:12px 0 4px"><span>{{ loadErr }}</span><button type="button" @click="load">Retry</button></div>
  </div>
</AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { apiError } from '@/services/api'
import { adminCoupons } from '@/services/adminService'
import { inrp, formatDate } from '@/utils/format'

const STATE = {
  active: { label: 'Active', style: 'background:#E8F3E4;color:#103D2C' },
  scheduled: { label: 'Starts later', style: 'background:#E1ECFB;color:#1D4E9E' },
  expired: { label: 'Expired', style: 'background:#EDEDE4;color:#3b4a3f' },
  used_up: { label: 'Fully used', style: 'background:#FFF4CC;color:#7A4A00' },
  off: { label: 'Off', style: 'background:#EDEDE4;color:#3b4a3f' },
}
const list = ref([]); const loading = ref(false); const loadErr = ref('')
const busy = ref(false); const err = ref(''); const msg = ref(''); const errs = reactive({})
const confirming = ref(null); const editingId = ref(null)
const blank = () => ({ code: '', type: 'percent', value: '', max: '', min: '', ends: '', limit: '', description: '', once: true, active: true })
const f = reactive(blank())

const paise = (rupees) => (rupees === '' || rupees === null ? null : Math.round(Number(rupees) * 100))
async function load() {
  loading.value = true; loadErr.value = ''
  try { list.value = await adminCoupons.list() } catch (e) { loadErr.value = apiError(e, 'Could not load coupons.').message } finally { loading.value = false }
}
function clearMsgs() { err.value = ''; msg.value = ''; Object.keys(errs).forEach((k) => delete errs[k]) }
function resetForm() { Object.assign(f, blank()); editingId.value = null; clearMsgs() }
function edit(c) {
  clearMsgs(); editingId.value = c.id
  Object.assign(f, {
    code: c.code, type: c.type, value: c.type === 'percent' ? String(c.value) : String(c.value / 100),
    max: c.max_discount_paise ? String(c.max_discount_paise / 100) : '', min: c.min_subtotal_paise ? String(c.min_subtotal_paise / 100) : '',
    ends: c.ends_at ? c.ends_at.slice(0, 10) : '', limit: c.usage_limit ? String(c.usage_limit) : '', description: c.description || '', once: c.once_per_customer, active: c.is_active,
  })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
const payload = () => ({
  code: f.code.trim().toUpperCase(), description: f.description.trim() || null, type: f.type,
  value: f.type === 'percent' ? Math.round(Number(f.value)) : paise(f.value),
  max_discount_paise: f.type === 'percent' ? paise(f.max) : null, min_subtotal_paise: paise(f.min) || 0,
  ends_at: f.ends ? f.ends + 'T23:59:59' : null, usage_limit: f.limit ? Math.round(Number(f.limit)) : null,
  once_per_customer: f.once, is_active: f.active,
})
async function save() {
  clearMsgs()
  if (!f.code.trim()) { errs.code = 'Enter a code.'; return }
  if (!(Number(f.value) > 0)) { errs.value = 'Enter a discount above zero.'; return }
  busy.value = true
  try {
    const c = editingId.value ? await adminCoupons.update(editingId.value, payload()) : await adminCoupons.create(payload())
    msg.value = editingId.value ? `Saved ${c.code}.` : `Created ${c.code}. Customers can use it at checkout now.`
    Object.assign(f, blank()); editingId.value = null; await load()
  } catch (e) { const ae = apiError(e, 'Could not save the coupon.'); Object.assign(errs, ae.errors); if (!Object.keys(ae.errors).length) err.value = ae.message } finally { busy.value = false }
}
async function toggle(c) {
  clearMsgs(); busy.value = true
  try {
    const p = { code: c.code, type: c.type, value: c.value, description: c.description || null, min_subtotal_paise: c.min_subtotal_paise, max_discount_paise: c.max_discount_paise, starts_at: c.starts_at, ends_at: c.ends_at, usage_limit: c.usage_limit, once_per_customer: c.once_per_customer, is_active: !c.is_active }
    await adminCoupons.update(c.id, p); msg.value = `${c.code} is now ${p.is_active ? 'on' : 'off'}.`; await load()
  } catch (e) { err.value = apiError(e).message } finally { busy.value = false }
}
async function remove(c) {
  clearMsgs(); busy.value = true
  try { const r = await adminCoupons.remove(c.id); msg.value = r.message || `Deleted ${c.code}.`; confirming.value = null; await load() }
  catch (e) { err.value = apiError(e, 'Could not delete the coupon.').message; confirming.value = null } finally { busy.value = false }
}
onMounted(load)
</script>

<style scoped>
.cf { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px 16px; align-items: start; }
.fl { display: flex; flex-direction: column; gap: 6px; font-size: 12.5px; font-weight: 700; color: var(--muted); min-width: 0; }
.fl .s { width: 100%; box-sizing: border-box; height: 42px; }
.wide { grid-column: span 2; }
.up { text-transform: uppercase; letter-spacing: .5px; font-weight: 700; }
.chk { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; min-height: 42px; align-self: end; }
.chk input { width: 18px; height: 18px; accent-color: var(--forest); }
.acts { grid-column: 1 / -1; display: flex; gap: 10px; flex-wrap: wrap; }
.mono { font-family: ui-monospace, Consolas, monospace; letter-spacing: .5px; }
.mut { font-size: 12px; color: var(--muted); font-weight: 500; }
@media (max-width: 1000px) { .cf { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 560px) { .cf { grid-template-columns: 1fr; } .wide { grid-column: auto; } }
</style>
