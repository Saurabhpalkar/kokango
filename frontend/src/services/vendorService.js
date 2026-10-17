// Vendor panel data layer.
//
// The vendor backend does not exist yet, so by default this file runs on a DEMO adapter (see marketplaceDb.js) that keeps sample
// data in the browser. The admin marketplace screens share the same demo data. Nothing here touches the real store catalogue, orders or admin API.
//
// To switch to the real API later: set VITE_VENDOR_MOCK=false and implement these endpoints under /api/v1
// (all responses use the same { data: ... } envelope as the rest of the API, lists add { meta }):
//   POST   /vendor/auth/login        { login, password }            -> { token, vendor }
//   POST   /vendor/auth/register     { store_name, owner_name, email, phone, password, city, gst_no?, description? } -> { token, vendor }
//   GET    /vendor/auth/me           -> vendor           POST /vendor/auth/logout
//   GET    /vendor/dashboard         -> { stats, sales_by_day, recent_orders, low_stock }
//   GET    /vendor/products?search=&status=&page=        POST /vendor/products      GET|PUT|DELETE /vendor/products/:id
//   GET    /vendor/orders?status=&search=&page=          GET /vendor/orders/:order_no
//   PUT    /vendor/orders/:order_no/status               { status }  (confirmed | packed | shipped | cancelled)
//   GET    /vendor/inventory?search=                      POST /vendor/inventory/:variant_id/adjust  { stock }
//   GET    /vendor/payouts           -> { summary, payouts }
//   GET    /vendor/store             PUT /vendor/store
// Money is always paise, like the rest of the API.
import api, { VENDOR_TOKEN_KEY } from '@/services/api'
import { MOCK as VENDOR_MOCK, DEMO_VENDOR, db, save, wait, fail, page, pub, withTotals, vendorStats, rateOf } from '@/services/marketplaceDb'

export { VENDOR_MOCK, DEMO_VENDOR, VENDOR_TOKEN_KEY }

const session = () => { try { return localStorage.getItem(VENDOR_TOKEN_KEY) } catch (e) { return null } }
const setSession = (id) => { try { id ? localStorage.setItem(VENDOR_TOKEN_KEY, String(id)) : localStorage.removeItem(VENDOR_TOKEN_KEY) } catch (e) { /* ignore */ } }
const me = (s) => { const v = s.vendors.find((x) => String(x.id) === String(session())); if (!v) { const e = new Error('Please sign in.'); e.response = { status: 401, data: { message: 'Please sign in.' } }; throw e } return v }
const out = (s, v) => ({ ...pub(v), commission_percent: rateOf(s, v) })
const one = (r) => r.data.data
const body = (r) => r.data
const own = (s, v) => s.products.filter((p) => p.vendor_id === v.id)

export const vendorAuth = {
  async login(login, password) {
    if (!VENDOR_MOCK) { const r = one(await api.post('/vendor/auth/login', { login, password })); setSession(r.token); return r }
    const s = db(); const v = s.vendors.find((x) => x.email.toLowerCase() === String(login).trim().toLowerCase() && x.password === password)
    if (!v) fail('Wrong email or password.')
    if (v.status === 'suspended') fail('Your store is suspended' + (v.status_reason ? `: ${v.status_reason}` : '.') + ' Please contact Kokango support.')
    if (v.status === 'rejected') fail('Your application was not approved' + (v.status_reason ? `: ${v.status_reason}` : '.') + ' Please contact Kokango support.')
    setSession(v.id); return wait({ token: String(v.id), vendor: out(s, v) })
  },
  async register(form) {
    if (!VENDOR_MOCK) { const r = one(await api.post('/vendor/auth/register', form)); setSession(r.token); return r }
    const s = db()
    if (s.vendors.some((x) => x.email.toLowerCase() === form.email.trim().toLowerCase())) fail('This email is already registered.', { email: 'This email is already registered.' })
    const v = { id: ++s.seq.vendor, store_name: form.store_name, owner_name: form.owner_name, email: form.email.trim(), password: form.password, phone: form.phone, city: form.city, pincode: '', pickup_address: '', gst_no: form.gst_no || '', pan_no: '', fssai_no: '', description: form.description || '', status: 'pending', status_reason: '', commission_percent: null, created_at: new Date().toISOString(), bank: { account_name: '', account_no: '', ifsc: '', bank_name: '' } }
    s.vendors.push(v); save(s); setSession(v.id); return wait({ token: String(v.id), vendor: out(s, v) })
  },
  async me() { if (!VENDOR_MOCK) return one(await api.get('/vendor/auth/me')); const s = db(); return wait(out(s, me(s))) },
  async logout() { if (!VENDOR_MOCK) { try { await api.post('/vendor/auth/logout') } catch (e) { /* ignore */ } } setSession(null) },
}

export const vendorDashboard = async () => {
  if (!VENDOR_MOCK) return one(await api.get('/vendor/dashboard'))
  const s = db(); const v = me(s); const st = vendorStats(s, v)
  const orders = s.orders.filter((o) => o.vendor_id === v.id).sort((a, b) => new Date(b.placed_at) - new Date(a.placed_at)); const prods = own(s, v).map(withTotals)
  const live = orders.filter((o) => o.status !== 'cancelled')
  const sales_by_day = Array.from({ length: 7 }, (_, k) => { const d = new Date(Date.now() - (6 - k) * 86400000); const key = d.toDateString(); return { label: d.toLocaleDateString('en-IN', { weekday: 'short' }), paise: live.filter((o) => new Date(o.placed_at).toDateString() === key).reduce((a, o) => a + o.subtotal_paise, 0) } })
  return wait({
    stats: { total_sales_paise: st.sales_paise, earnings_paise: st.earnings_paise, orders: st.orders, pending_orders: st.pending_orders, products: st.products, live_products: st.live_products, low_stock_count: prods.filter((p) => p.stock_total < 10).length },
    sales_by_day, recent_orders: orders.slice(0, 5),
    low_stock: prods.flatMap((p) => p.variants.filter((x) => x.stock < 10).map((x) => ({ variant_id: x.id, product_name: p.name, size_label: x.size_label, stock: x.stock, image_url: p.image_url }))).slice(0, 5),
  })
}

export const vendorProducts = {
  async list({ search = '', status = '', page: n = 1 } = {}) {
    if (!VENDOR_MOCK) return body(await api.get('/vendor/products', { params: { search, status, page: n } }))
    const s = db(); let rows = own(s, me(s)).map(withTotals)
    if (search) rows = rows.filter((p) => p.name.toLowerCase().includes(search.toLowerCase()))
    if (status && status !== 'all') rows = rows.filter((p) => p.status === status)
    return wait(page(rows, n, 6))
  },
  async get(id) { if (!VENDOR_MOCK) return one(await api.get(`/vendor/products/${id}`)); const s = db(); const p = own(s, me(s)).find((x) => String(x.id) === String(id)); if (!p) fail('Product not found.'); return wait(withTotals(p)) },
  async save(id, form) {
    if (!VENDOR_MOCK) return one(id ? await api.put(`/vendor/products/${id}`, form) : await api.post('/vendor/products', form))
    const s = db(); const v = me(s)
    const vs = form.variants.map((x) => ({ id: x.id || ++s.seq.variant, size_label: x.size_label, sku: x.sku || '', price_paise: Number(x.price_paise), mrp_paise: Number(x.mrp_paise) || Number(x.price_paise), stock: Number(x.stock) || 0 }))
    const cat = { id: form.category_id, name: form.category_name || '' }
    // a submission waits for Kokango review (unless auto-approve is on and the store is approved)
    let status = form.status
    if (status === 'pending_review' && v.status === 'approved' && s.settings.auto_approve_products) status = 'active'
    const meta = status === 'pending_review' ? { submitted_at: new Date().toISOString(), review_note: '' } : {}
    if (id) {
      const p = own(s, v).find((x) => String(x.id) === String(id)); if (!p) fail('Product not found.')
      Object.assign(p, { name: form.name, description: form.description, category: cat, category_id: form.category_id, image_url: form.image_url || p.image_url, status, variants: vs, ...meta })
    } else {
      s.products.push({ id: ++s.seq.product, vendor_id: v.id, name: form.name, slug: form.name.toLowerCase().replace(/\s+/g, '-'), description: form.description, category: cat, category_id: form.category_id, image_url: form.image_url || '/images/moringa-powder.png', status, variants: vs, submitted_at: null, review_note: '', ...meta })
    }
    save(s); return wait({ ok: true })
  },
  async remove(id) { if (!VENDOR_MOCK) return body(await api.delete(`/vendor/products/${id}`)); const s = db(); const v = me(s); s.products = s.products.filter((p) => !(String(p.id) === String(id) && p.vendor_id === v.id)); save(s); return wait({ ok: true }) },
}

export const vendorOrders = {
  async list({ status = '', search = '', page: n = 1 } = {}) {
    if (!VENDOR_MOCK) return body(await api.get('/vendor/orders', { params: { status, search, page: n } }))
    const s = db(); const v = me(s); let rows = s.orders.filter((o) => o.vendor_id === v.id).sort((a, b) => new Date(b.placed_at) - new Date(a.placed_at))
    if (status && status !== 'all') rows = rows.filter((o) => o.status === status)
    if (search) rows = rows.filter((o) => (o.order_no + o.customer_name).toLowerCase().includes(search.toLowerCase()))
    return wait(page(rows, n, 8))
  },
  async updateStatus(orderNo, status) {
    if (!VENDOR_MOCK) return one(await api.put(`/vendor/orders/${encodeURIComponent(orderNo)}/status`, { status }))
    const s = db(); const v = me(s); const o = s.orders.find((x) => x.order_no === orderNo && x.vendor_id === v.id); if (!o) fail('Order not found.'); o.status = status; save(s); return wait(o)
  },
}

export const vendorInventory = {
  async list({ search = '' } = {}) {
    if (!VENDOR_MOCK) return one(await api.get('/vendor/inventory', { params: { search } }))
    const s = db(); const rows = own(s, me(s)).flatMap((p) => p.variants.map((x) => ({ variant_id: x.id, product_name: p.name, size_label: x.size_label, sku: x.sku, stock: x.stock, image_url: p.image_url, low: x.stock < 10 })))
    return wait(search ? rows.filter((r) => (r.product_name + r.sku).toLowerCase().includes(search.toLowerCase())) : rows)
  },
  async adjust(variantId, stock) {
    if (!VENDOR_MOCK) return one(await api.post(`/vendor/inventory/${variantId}/adjust`, { stock }))
    const s = db(); own(s, me(s)).forEach((p) => p.variants.forEach((x) => { if (x.id === variantId) x.stock = Number(stock) })); save(s); return wait({ ok: true })
  },
}

export const vendorPayouts = async () => {
  if (!VENDOR_MOCK) return one(await api.get('/vendor/payouts'))
  const s = db(); const v = me(s); const st = vendorStats(s, v)
  const list = s.payouts.filter((p) => p.vendor_id === v.id).sort((a, b) => b.id - a.id)
  const sum = (...sts) => list.filter((p) => sts.includes(p.status)).reduce((a, p) => a + p.amount_paise, 0)
  return wait({ summary: { available_paise: st.payable_paise, processing_paise: sum('scheduled', 'processing'), paid_paise: sum('paid'), commission_percent: st.commission_percent }, payouts: list })
}

export const vendorStore = {
  async get() { if (!VENDOR_MOCK) return one(await api.get('/vendor/store')); const s = db(); return wait(out(s, me(s))) },
  async save(form) {
    if (!VENDOR_MOCK) return one(await api.put('/vendor/store', form))
    const s = db(); const v = me(s)
    const { store_name, owner_name, email, phone, city, pincode, pickup_address, gst_no, pan_no, fssai_no, description, bank } = form
    Object.assign(v, { store_name, owner_name, email, phone, city, pincode, pickup_address, gst_no, pan_no, fssai_no, description, bank }); save(s); return wait(out(s, v))
  },
}
