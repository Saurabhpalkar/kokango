// Admin-side marketplace data layer (vendors, product approvals, commissions & vendor payouts).
//
// Runs on the shared DEMO database (marketplaceDb.js) until the backend exists; set VITE_VENDOR_MOCK=false and implement, under /api/v1
// (admin auth required; same { data } / { data, meta } envelope as the rest of the admin API; money in paise):
//   GET    /admin/marketplace/overview                      -> { stats, top_vendors, pending_vendors, pending_products, sales_by_day }
//   GET    /admin/vendors?status=&search=&page=             -> { data:[vendor + stats], meta, counts:{all,pending,approved,suspended,rejected} }
//   POST   /admin/vendors                                   (admin creates a vendor account)
//   GET    /admin/vendors/:id                               -> { vendor, stats, checklist, products, orders, payouts, sales_by_day }
//   PUT    /admin/vendors/:id                               (profile, bank, commission_percent override or null)
//   POST   /admin/vendors/:id/approve | reject | suspend | reactivate     { reason? }  (reject/suspend need a reason)
//   GET    /admin/product-approvals?status=pending_review|rejected|all&search=&page=
//   POST   /admin/product-approvals/:id/approve | reject   { reason }  (reject needs a reason shown to the vendor)
//   GET    /admin/vendor-payouts                            -> { summary, vendors:[balance rows], payouts, settings }
//   POST   /admin/vendor-payouts                            { vendor_id }   create a payout for the vendor's unsettled delivered orders
//   POST   /admin/vendor-payouts/:id/status                 { status: processing|paid, utr? }
//   GET|PUT /admin/marketplace/settings                     { default_commission, min_payout_paise, auto_approve_products }
import api from '@/services/api'
import { MOCK, db, save, wait, fail, page, pub, withTotals, vendorStats, checklist, rateOf } from '@/services/marketplaceDb'

const one = (r) => r.data.data
const body = (r) => r.data
const nowIso = () => new Date().toISOString()
const find = (s, id) => { const v = s.vendors.find((x) => String(x.id) === String(id)); if (!v) fail('Vendor not found.'); return v }
const row = (s, v) => ({ ...pub(v), commission_percent_override: v.commission_percent, ...vendorStats(s, v) })
const byDay = (orders) => Array.from({ length: 14 }, (_, k) => { const d = new Date(Date.now() - (13 - k) * 86400000); const key = d.toDateString(); return { label: d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' }), paise: orders.filter((o) => o.status !== 'cancelled' && new Date(o.placed_at).toDateString() === key).reduce((a, o) => a + o.subtotal_paise, 0) } })

export const adminVendors = {
  async list({ status = '', search = '', page: n = 1 } = {}) {
    if (!MOCK) return body(await api.get('/admin/vendors', { params: { status, search, page: n } }))
    const s = db(); const all = s.vendors.map((v) => row(s, v))
    const counts = { all: all.length }; ['pending', 'approved', 'suspended', 'rejected'].forEach((k) => { counts[k] = all.filter((v) => v.status === k).length })
    let rows = all.slice().sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    if (status && status !== 'all') rows = rows.filter((v) => v.status === status)
    if (search) rows = rows.filter((v) => (v.store_name + v.owner_name + v.email + v.city).toLowerCase().includes(search.toLowerCase()))
    return wait({ ...page(rows, n, 8), counts })
  },
  async get(id) {
    if (!MOCK) return one(await api.get(`/admin/vendors/${id}`))
    const s = db(); const v = find(s, id); const os = s.orders.filter((o) => o.vendor_id === v.id).sort((a, b) => new Date(b.placed_at) - new Date(a.placed_at))
    return wait({ vendor: pub(v), stats: vendorStats(s, v), checklist: checklist(v), products: s.products.filter((p) => p.vendor_id === v.id).map(withTotals), orders: os.slice(0, 6), payouts: s.payouts.filter((p) => p.vendor_id === v.id).sort((a, b) => b.id - a.id), sales_by_day: byDay(os) })
  },
  async create(form) {
    if (!MOCK) return one(await api.post('/admin/vendors', form))
    const s = db(); const errors = {}
    if (s.vendors.some((x) => x.email.toLowerCase() === String(form.email).trim().toLowerCase())) errors.email = 'This email is already registered.'
    if (Object.keys(errors).length) fail('Please fix the highlighted fields.', errors)
    const v = { id: ++s.seq.vendor, password: form.password || 'Vendor@12345', status: form.approve_now ? 'approved' : 'pending', status_reason: '', commission_percent: form.commission_percent === '' || form.commission_percent == null ? null : Number(form.commission_percent), created_at: nowIso(), pincode: '', pickup_address: '', gst_no: '', pan_no: '', fssai_no: '', description: '', ...form, email: form.email.trim(), bank: { account_name: '', account_no: '', ifsc: '', bank_name: '', ...(form.bank || {}) } }
    delete v.approve_now; s.vendors.push(v); save(s); return wait(pub(v))
  },
  async update(id, form) {
    if (!MOCK) return one(await api.put(`/admin/vendors/${id}`, form))
    const s = db(); const v = find(s, id)
    const { store_name, owner_name, email, phone, city, pincode, pickup_address, gst_no, pan_no, fssai_no, description, bank } = form
    Object.assign(v, { store_name, owner_name, email, phone, city, pincode, pickup_address, gst_no, pan_no, fssai_no, description, bank, commission_percent: form.commission_percent === '' || form.commission_percent == null ? null : Number(form.commission_percent) })
    save(s); return wait(pub(v))
  },
  async act(id, action, reason = '') {
    if (!MOCK) return one(await api.post(`/admin/vendors/${id}/${action}`, { reason }))
    const s = db(); const v = find(s, id)
    if (action === 'approve') { if (checklist(v).some((c) => !c.ok)) fail('Complete the approval checklist first.'); v.status = 'approved'; v.status_reason = '' }
    else if (action === 'reactivate') { v.status = 'approved'; v.status_reason = '' }
    else if (action === 'reject') { if (!reason.trim()) fail('Add a reason so the vendor knows what to fix.', { reason: 'Required' }); v.status = 'rejected'; v.status_reason = reason.trim() }
    else if (action === 'suspend') {
      if (!reason.trim()) fail('Add a reason for the suspension.', { reason: 'Required' })
      v.status = 'suspended'; v.status_reason = reason.trim()
      s.products.forEach((p) => { if (p.vendor_id === v.id && p.status === 'active') p.status = 'paused' }) // hidden from the store while suspended
    }
    if (action === 'reactivate') s.products.forEach((p) => { if (p.vendor_id === v.id && p.status === 'paused') p.status = 'active' })
    save(s); return wait(pub(v))
  },
}

export const adminApprovals = {
  async list({ status = 'pending_review', search = '', page: n = 1 } = {}) {
    if (!MOCK) return body(await api.get('/admin/product-approvals', { params: { status, search, page: n } }))
    const s = db(); const vn = (id) => s.vendors.find((v) => v.id === id)
    const base = s.products.map((p) => ({ ...withTotals(p), vendor_name: vn(p.vendor_id)?.store_name || '—', vendor_status: vn(p.vendor_id)?.status }))
    const counts = { pending_review: base.filter((p) => p.status === 'pending_review').length, rejected: base.filter((p) => p.status === 'rejected').length, active: base.filter((p) => p.status === 'active').length }
    let rows = base.filter((p) => ['pending_review', 'rejected', 'active', 'paused'].includes(p.status))
    if (status && status !== 'all') rows = rows.filter((p) => p.status === status)
    if (search) rows = rows.filter((p) => (p.name + p.vendor_name).toLowerCase().includes(search.toLowerCase()))
    rows.sort((a, b) => new Date(b.submitted_at || 0) - new Date(a.submitted_at || 0))
    return wait({ ...page(rows, n, 8), counts })
  },
  async act(id, action, reason = '') {
    if (!MOCK) return one(await api.post(`/admin/product-approvals/${id}/${action}`, { reason }))
    const s = db(); const p = s.products.find((x) => String(x.id) === String(id)); if (!p) fail('Product not found.')
    if (action === 'approve') { p.status = 'active'; p.review_note = '' }
    else if (action === 'reject') { if (!reason.trim()) fail('Add a reason so the vendor can fix the listing.', { reason: 'Required' }); p.status = 'rejected'; p.review_note = reason.trim() }
    else if (action === 'unpublish') { p.status = 'paused' }
    save(s); return wait({ ok: true })
  },
}

export const adminMarketplace = {
  async overview() {
    if (!MOCK) return one(await api.get('/admin/marketplace/overview'))
    const s = db(); const rows = s.vendors.map((v) => row(s, v))
    const live = s.orders.filter((o) => o.status !== 'cancelled')
    return wait({
      stats: {
        vendors: rows.length, approved_vendors: rows.filter((v) => v.status === 'approved').length, pending_vendors: rows.filter((v) => v.status === 'pending').length,
        pending_products: s.products.filter((p) => p.status === 'pending_review').length, gmv_paise: live.reduce((a, o) => a + o.subtotal_paise, 0), commission_paise: live.reduce((a, o) => a + o.commission_paise, 0),
        payable_paise: rows.reduce((a, v) => a + v.payable_paise, 0), orders: s.orders.length,
      },
      top_vendors: rows.filter((v) => v.sales_paise > 0).sort((a, b) => b.sales_paise - a.sales_paise).slice(0, 5),
      pending_vendors: rows.filter((v) => v.status === 'pending'),
      pending_products: s.products.filter((p) => p.status === 'pending_review').map((p) => ({ id: p.id, name: p.name, image_url: p.image_url, vendor_name: s.vendors.find((v) => v.id === p.vendor_id)?.store_name, submitted_at: p.submitted_at })),
      sales_by_day: byDay(s.orders),
    })
  },
  async settings() { if (!MOCK) return one(await api.get('/admin/marketplace/settings')); return wait(db().settings) },
  async saveSettings(form) {
    if (!MOCK) return one(await api.put('/admin/marketplace/settings', form))
    const c = Number(form.default_commission); if (!(c >= 0 && c <= 50)) fail('Commission must be between 0 and 50%.', { default_commission: 'Enter 0–50' })
    const s = db(); s.settings = { default_commission: c, min_payout_paise: Math.round(Number(form.min_payout_paise) || 0), auto_approve_products: !!form.auto_approve_products }; save(s); return wait(s.settings)
  },
}

export const adminVendorPayouts = {
  async list() {
    if (!MOCK) return one(await api.get('/admin/vendor-payouts'))
    const s = db(); const vendors = s.vendors.filter((v) => v.status !== 'pending' && v.status !== 'rejected').map((v) => ({ ...row(s, v), min_ok: vendorStats(s, v).payable_paise >= s.settings.min_payout_paise }))
    const name = (id) => s.vendors.find((v) => v.id === id)?.store_name
    const payouts = s.payouts.slice().sort((a, b) => b.id - a.id).map((p) => ({ ...p, vendor_name: name(p.vendor_id) }))
    const sum = (...st) => s.payouts.filter((p) => st.includes(p.status)).reduce((a, p) => a + p.amount_paise, 0)
    return wait({ summary: { payable_paise: vendors.reduce((a, v) => a + v.payable_paise, 0), in_progress_paise: sum('scheduled', 'processing'), paid_paise: sum('paid'), commission_paise: s.orders.filter((o) => o.status !== 'cancelled').reduce((a, o) => a + o.commission_paise, 0) }, vendors, payouts, settings: s.settings })
  },
  async create(vendorId) {
    if (!MOCK) return one(await api.post('/admin/vendor-payouts', { vendor_id: vendorId }))
    const s = db(); const v = find(s, vendorId); const os = s.orders.filter((o) => o.vendor_id === v.id && o.status === 'delivered' && !o.settled)
    const amt = os.reduce((a, o) => a + o.earning_paise, 0)
    if (!amt) fail('Nothing to pay out for this vendor yet.')
    if (amt < s.settings.min_payout_paise) fail('Balance is below the minimum payout amount.')
    if (!v.bank?.account_no) fail('This vendor has no bank account on file.')
    os.forEach((o) => { o.settled = true })
    const n = ++s.seq.payout; s.payouts.push({ id: n, vendor_id: v.id, ref: `PO-2026-${String(n).padStart(3, '0')}`, period: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }), amount_paise: amt, status: 'scheduled', paid_at: null, utr: '' })
    save(s); return wait({ ok: true })
  },
  async setStatus(id, status, utr = '') {
    if (!MOCK) return one(await api.post(`/admin/vendor-payouts/${id}/status`, { status, utr }))
    const s = db(); const p = s.payouts.find((x) => x.id === id); if (!p) fail('Payout not found.')
    if (status === 'paid' && !utr.trim()) fail('Enter the bank reference (UTR) for this payment.', { utr: 'Required' })
    p.status = status; if (status === 'paid') { p.paid_at = nowIso(); p.utr = utr.trim() }
    save(s); return wait({ ok: true })
  },
}
