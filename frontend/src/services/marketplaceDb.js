// Shared DEMO database for the vendor panel and the admin marketplace screens.
// Lives in localStorage (key below) so an action in one panel (e.g. admin approves a vendor) shows up in the other.
// The real API (/api/v1/vendor/*, /api/v1/admin/vendors...) is used by default; set VITE_VENDOR_MOCK=true to demo the panels without a backend.
export const MOCK = import.meta.env.VITE_VENDOR_MOCK === 'true'
export const DEMO_VENDOR = { email: 'vendor@kokango.test', password: 'Vendor@12345' }
const KEY = 'kokango_marketplace_demo_v2'

export const wait = (v) => new Promise((r) => setTimeout(() => r(JSON.parse(JSON.stringify(v))), 120))
export const fail = (message, errors = {}) => { const e = new Error(message); e.response = { status: 422, data: { message, errors } }; throw e }
export const page = (rows, n = 1, per = 8) => ({ data: rows.slice((n - 1) * per, n * per), meta: { current_page: n, last_page: Math.max(1, Math.ceil(rows.length / per)), per_page: per, total: rows.length } })
export const pub = ({ password, ...v }) => v
export const withTotals = (p) => ({ ...p, min_price_paise: Math.min(...p.variants.map((v) => v.price_paise)), stock_total: p.variants.reduce((a, v) => a + Number(v.stock || 0), 0) })
export const rateOf = (s, v) => (v.commission_percent == null || v.commission_percent === '' ? s.settings.default_commission : Number(v.commission_percent))

const day = (n) => new Date(Date.now() - n * 86400000).toISOString()
const variant = (id, size, price, mrp, stock) => ({ id, size_label: size, sku: `VND-${id}`, price_paise: price, mrp_paise: mrp, stock })
let pid = 0
const prod = (vendor_id, name, cat, img, status, vs, extra = {}) => ({
  id: ++pid, vendor_id, name, slug: name.toLowerCase().replace(/\s+/g, '-'), category: { id: cat[0], name: cat[1] }, category_id: cat[0],
  description: 'Small-batch product prepared with care.', image_url: `/images/${img}.png`, status, variants: vs, submitted_at: status === 'pending_review' ? day(1) : null, review_note: '', ...extra,
})
const CAT = { h: [1, 'Herbal Powders'], s: [2, 'Snacks'], w: [3, 'Sweets'] }
const bank = (n) => ({ account_name: n, account_no: '0001234567' + n.length, ifsc: 'HDFC0001234', bank_name: 'HDFC Bank' })
const noBank = { account_name: '', account_no: '', ifsc: '', bank_name: '' }
const ven = (id, store_name, owner_name, email, city, status, extra = {}) => ({
  id, store_name, owner_name, email, password: 'Vendor@12345', phone: '98' + String(70000000 + id * 111111), city, pincode: '4220' + (10 + id), pickup_address: `${id + 10} Market Road, ${city}`,
  gst_no: '', pan_no: 'ABCDE' + (1000 + id) + 'F', fssai_no: '1152200100' + (1000 + id), description: '', status, status_reason: '', commission_percent: null, bank: bank(store_name), created_at: day(60 - id * 6), ...extra,
})

const IMGS = ['moringa-powder', 'jackfruit-chips', 'curry-leaf-powder', 'jamun-vadi']
const NAMES = ['Asha Patil', 'Rohan Desai', 'Meera Joshi', 'Vikram More', 'Sneha Kulkarni', 'Aditya Rane', 'Kavita Shinde']
const CITIES = ['Pune', 'Mumbai', 'Nashik', 'Nagpur', 'Thane', 'Kolhapur', 'Goa']
let ono = 1039
function orders(vendorId, n, rate, products, spanDays, first = false) {
  return Array.from({ length: n }, (_, i) => {
    const p = products[i % products.length]; const v = p.variants[0]; const qty = 1 + (i % 3); const sub = v.price_paise * qty
    const st = first ? ['pending', 'confirmed', 'packed', 'shipped', 'delivered', 'delivered', 'delivered', 'cancelled', 'delivered', 'delivered'][i] : (i < 2 ? ['pending', 'confirmed', 'shipped'][i] : (i % 9 === 7 ? 'cancelled' : 'delivered'))
    const age = first ? i * 1.3 : (i * spanDays) / n
    const com = Math.round((sub * rate) / 100)
    return {
      id: ++ono, order_no: `KKG-V-${ono}`, vendor_id: vendorId, customer_name: NAMES[(i + vendorId) % NAMES.length], city: CITIES[(i + vendorId) % CITIES.length], placed_at: day(age), status: st,
      payment_status: st === 'cancelled' ? 'refunded' : 'paid', settled: st === 'delivered' && age > 10,
      items: [{ id: 1, name: p.name, size_label: v.size_label, qty, total_paise: sub, image_url: p.image_url }],
      subtotal_paise: sub, commission_paise: com, earning_paise: sub - com,
      address: { name: 'Customer', line1: '21 Rose Avenue', city: CITIES[(i + vendorId) % CITIES.length], state: 'Maharashtra', pincode: '411001', phone: '98XXXXXX10' },
    }
  })
}

function seed() {
  const vendors = [
    ven(1, 'Sahyadri Farms', 'Saurabh Patil', DEMO_VENDOR.email, 'Nashik', 'approved', { description: 'Konkan-style snacks and herbal powders.', gst_no: '27ABCDE1001F1Z5', bank: bank('Sahyadri Farms') }),
    ven(2, 'Konkan Kitchen', 'Meena Sawant', 'konkan@kokango.test', 'Ratnagiri', 'approved', { description: 'Traditional sweets and snacks from Ratnagiri.', gst_no: '27ABCDE1002F1Z5', commission_percent: 8 }),
    ven(3, 'Malabar Naturals', 'Rajesh Nair', 'malabar@kokango.test', 'Pune', 'approved', { description: 'Herbal powders ground in small batches.', gst_no: '27ABCDE1003F1Z5' }),
    ven(4, 'Coastal Sweets Co', 'Priya Gawde', 'coastal@kokango.test', 'Mumbai', 'pending', { description: 'Handmade mithai and kokum sarbat.', status_reason: '', created_at: day(2) }),
    ven(5, 'Green Roots Organics', 'Anil Kale', 'greenroots@kokango.test', 'Kolhapur', 'pending', { description: 'Organic herbal powders.', fssai_no: '', bank: { ...noBank }, pickup_address: '', created_at: day(1) }),
    ven(6, 'Old Mill Foods', 'Sunil Pawar', 'oldmill@kokango.test', 'Nagpur', 'suspended', { status_reason: 'Repeated late dispatch and customer complaints.', gst_no: '27ABCDE1006F1Z5' }),
    ven(7, 'Spice Trail', 'Farida Shaikh', 'spicetrail@kokango.test', 'Thane', 'rejected', { status_reason: 'FSSAI licence could not be verified.', fssai_no: '', created_at: day(20) }),
  ]
  const products = [
    prod(1, 'Moringa Powder', CAT.h, 'moringa-powder', 'active', [variant(11, '100g', 19900, 24900, 42), variant(12, '250g', 44900, 54900, 18)]),
    prod(1, 'Curry Leaf Powder', CAT.h, 'curry-leaf-powder', 'active', [variant(21, '100g', 17900, 17900, 7)]),
    prod(1, 'Jackfruit Chips', CAT.s, 'jackfruit-chips', 'active', [variant(31, '200g', 14900, 16900, 64)]),
    prod(1, 'Jamun Vadi', CAT.w, 'jamun-vadi', 'pending_review', [variant(41, '150g', 12900, 12900, 30)]),
    prod(1, 'Neem Powder', CAT.h, 'moringa-powder', 'draft', [variant(51, '100g', 15900, 15900, 0)]),
    prod(1, 'Kokum Sarbat', CAT.w, 'jamun-vadi', 'active', [variant(61, '500ml', 24900, 27900, 3)]),
    prod(2, 'Banana Chips', CAT.s, 'jackfruit-chips', 'active', [variant(71, '200g', 13900, 15900, 80)]),
    prod(2, 'Modak Mix', CAT.w, 'jamun-vadi', 'active', [variant(81, '250g', 21900, 21900, 25)]),
    prod(2, 'Amba Poli', CAT.w, 'jamun-vadi', 'pending_review', [variant(91, '200g', 18900, 19900, 40)]),
    prod(3, 'Turmeric Powder', CAT.h, 'curry-leaf-powder', 'active', [variant(101, '200g', 15900, 17900, 55)]),
    prod(3, 'Ashwagandha Powder', CAT.h, 'moringa-powder', 'pending_review', [variant(111, '100g', 24900, 29900, 20)]),
    prod(3, 'Amla Powder', CAT.h, 'moringa-powder', 'rejected', [variant(121, '100g', 16900, 16900, 12)], { review_note: 'Add ingredient list and a clearer product photo.' }),
    prod(3, 'Tulsi Powder', CAT.h, 'curry-leaf-powder', 'pending_review', [variant(131, '100g', 14900, 14900, 35)]),
    prod(4, 'Kokum Sarbat Concentrate', CAT.w, 'jamun-vadi', 'draft', [variant(141, '500ml', 22900, 22900, 30)]),
    prod(6, 'Banana Wafers', CAT.s, 'jackfruit-chips', 'active', [variant(151, '150g', 11900, 11900, 14)]),
  ]
  const ofV = (id) => products.filter((p) => p.vendor_id === id)
  const os = [...orders(1, 10, 10, ofV(1), 14, true), ...orders(2, 14, 8, ofV(2), 30), ...orders(3, 9, 10, ofV(3), 30), ...orders(6, 5, 10, ofV(6), 40)]
  let po = 0
  const pay = (vendor_id, ref, period, amount_paise, status, ago) => ({ id: ++po, vendor_id, ref, period, amount_paise, status, paid_at: status === 'paid' ? day(ago) : null, utr: status === 'paid' ? 'UTR' + (900000 + po * 137) : '' })
  const payouts = [
    pay(1, 'PO-2026-010', '1 – 7 Oct 2026', 128000, 'scheduled', 0), pay(1, 'PO-2026-009', '24 – 30 Sep 2026', 214500, 'processing', 0), pay(1, 'PO-2026-008', '17 – 23 Sep 2026', 189000, 'paid', 14),
    pay(1, 'PO-2026-007', '10 – 16 Sep 2026', 176500, 'paid', 21), pay(1, 'PO-2026-006', '3 – 9 Sep 2026', 152000, 'paid', 28),
    pay(2, 'PO-2026-011', '24 – 30 Sep 2026', 96400, 'processing', 0), pay(2, 'PO-2026-005', '17 – 23 Sep 2026', 88200, 'paid', 14), pay(2, 'PO-2026-004', '10 – 16 Sep 2026', 74100, 'paid', 21),
    pay(3, 'PO-2026-012', '24 – 30 Sep 2026', 51200, 'scheduled', 0), pay(3, 'PO-2026-003', '17 – 23 Sep 2026', 62300, 'paid', 14),
    pay(6, 'PO-2026-002', '3 – 9 Sep 2026', 38000, 'paid', 30),
  ]
  return { vendors, products, orders: os, payouts, settings: { default_commission: 10, min_payout_paise: 50000, auto_approve_products: false }, seq: { product: pid + 100, variant: 2000, vendor: 20, order: ono, payout: po + 20 } }
}

export function db() {
  try { const raw = localStorage.getItem(KEY); if (raw) return JSON.parse(raw) } catch (e) { /* ignore */ }
  const s = seed(); save(s); return s
}
export function save(s) { try { localStorage.setItem(KEY, JSON.stringify(s)) } catch (e) { /* ignore */ } }

// vendor figures used by both panels
export function vendorStats(s, v) {
  const rate = rateOf(s, v)
  const os = s.orders.filter((o) => o.vendor_id === v.id); const live = os.filter((o) => o.status !== 'cancelled')
  const ps = s.products.filter((p) => p.vendor_id === v.id)
  const payable = os.filter((o) => o.status === 'delivered' && !o.settled).reduce((a, o) => a + o.earning_paise, 0)
  return {
    commission_percent: rate, orders: os.length, pending_orders: os.filter((o) => ['pending', 'confirmed'].includes(o.status)).length,
    sales_paise: live.reduce((a, o) => a + o.subtotal_paise, 0), commission_paise: live.reduce((a, o) => a + o.commission_paise, 0), earnings_paise: live.reduce((a, o) => a + o.earning_paise, 0),
    payable_paise: payable, products: ps.length, live_products: ps.filter((p) => p.status === 'active').length, review_products: ps.filter((p) => p.status === 'pending_review').length,
  }
}
export const checklist = (v) => [
  { key: 'profile', label: 'Store profile & contact', ok: !!(v.store_name && v.owner_name && v.phone && v.email) },
  { key: 'pickup', label: 'Pickup address', ok: !!(v.pickup_address && v.city && v.pincode) },
  { key: 'fssai', label: 'FSSAI licence number', ok: !!v.fssai_no },
  { key: 'bank', label: 'Bank account for payouts', ok: !!(v.bank && v.bank.account_no && v.bank.ifsc) },
]
