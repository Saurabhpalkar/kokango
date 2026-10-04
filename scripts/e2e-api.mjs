#!/usr/bin/env node
/**
 * Kokango automated end-to-end API test  (no extra packages, needs Node 18+).
 *
 * It plays the whole shop: browse -> register -> cart -> checkout -> fake payment ->
 * track -> cancel, and the admin side: dashboard -> category -> product -> stock ->
 * order status -> shipment. It also checks that customers cannot reach admin APIs.
 *
 *   node scripts/e2e-api.mjs                         # tests http://127.0.0.1:8000/api/v1
 *   API_URL=https://kokango.wuaze.com/api/v1 node scripts/e2e-api.mjs     # tests a live server
 *
 * Optional env: ADMIN_LOGIN, ADMIN_PASSWORD, CUSTOMER_LOGIN, CUSTOMER_PASSWORD, WEB_ORIGIN
 * (WEB_ORIGIN=https://kokango.netlify.app also checks that CORS allows your website).
 *
 * It creates test data (a "E2E ..." category/product and a few orders). Use a local/test database.
 */

const API = (process.env.API_URL || 'http://127.0.0.1:8000/api/v1').replace(/\/$/, '');
const ADMIN = { login: process.env.ADMIN_LOGIN || 'admin@kokango.test', password: process.env.ADMIN_PASSWORD || 'Admin@12345' };
const CUSTOMER = { login: process.env.CUSTOMER_LOGIN || 'rohan@example.com', password: process.env.CUSTOMER_PASSWORD || 'Password@123' };
const WEB_ORIGIN = process.env.WEB_ORIGIN || '';
const RUN = Date.now().toString(36);

// ---------- tiny helpers ----------
const results = [];
let section = '';
const c = { g: (s) => `\x1b[32m${s}\x1b[0m`, r: (s) => `\x1b[31m${s}\x1b[0m`, b: (s) => `\x1b[1m${s}\x1b[0m`, d: (s) => `\x1b[2m${s}\x1b[0m` };

async function call(method, path, { token, cart, body, headers = {} } = {}) {
  const h = { Accept: 'application/json', ...headers };
  if (body !== undefined) h['Content-Type'] = 'application/json';
  if (token) h.Authorization = `Bearer ${token}`;
  if (cart) h['X-Cart-Token'] = cart;
  let res;
  try {
    res = await fetch(API + path, { method, headers: h, body: body !== undefined ? JSON.stringify(body) : undefined });
  } catch (e) {
    return { status: 0, json: null, text: String(e.cause?.code || e.message), headers: new Headers() };
  }
  const text = await res.text();
  let json = null;
  try { json = JSON.parse(text); } catch { /* not JSON */ }
  return { status: res.status, json, text, headers: res.headers };
}

function title(name) { section = name; console.log('\n' + c.b(name)); }

async function test(name, fn) {
  try {
    await fn();
    results.push({ section, name, ok: true });
    console.log('  ' + c.g('PASS') + '  ' + name);
  } catch (e) {
    results.push({ section, name, ok: false, error: e.message });
    console.log('  ' + c.r('FAIL') + '  ' + name + '\n        ' + c.r(e.message));
  }
}

function expect(cond, msg) { if (!cond) throw new Error(msg); }
function status(res, ...codes) {
  const hint = res.json?.message ? ` - ${res.json.message}` : res.json ? '' : ` - not JSON: ${String(res.text).slice(0, 80).replace(/\s+/g, ' ')}`;
  expect(codes.includes(res.status), `expected HTTP ${codes.join('/')} but got ${res.status}${hint}`);
}

const state = {};
const pay = { name: 'E2E Tester', phone: '9876543210', line1: '12 MG Road', city: 'Bengaluru', state: 'Karnataka', pincode: '560001' };

// ---------- 0. server reachable ----------
title('0. Server is reachable');
let reachable = false;
await test('GET /settings/public answers with JSON', async () => {
  const r = await call('GET', '/settings/public');
  status(r, 200);
  expect(r.json?.data, `reply is not the Kokango API. It was: ${String(r.text).slice(0, 200).replace(/\s+/g, ' ')}`);
  reachable = true;
});
if (!reachable) {
  console.log(c.r(`\nCannot reach the API at ${API}. Is "php artisan serve" running (or is the URL right)?\n`));
  process.exit(2);
}

if (WEB_ORIGIN) {
  await test(`CORS allows ${WEB_ORIGIN}`, async () => {
    const r = await call('GET', '/products', { headers: { Origin: WEB_ORIGIN } });
    status(r, 200);
    const allow = r.headers.get('access-control-allow-origin');
    expect(allow === WEB_ORIGIN || allow === '*', `access-control-allow-origin is "${allow}" - set FRONTEND_URL=${WEB_ORIGIN} in backend .env`);
  });
}

// ---------- 1. public shop ----------
title('1. Public shop (no login)');
await test('categories list', async () => {
  const r = await call('GET', '/categories');
  status(r, 200);
  expect(Array.isArray(r.json.data) && r.json.data.length > 0, 'no categories - did you run the seeders?');
  state.category = r.json.data[0];
});
await test('products list has items with variants and prices', async () => {
  const r = await call('GET', '/products?per_page=50');
  status(r, 200);
  expect(Array.isArray(r.json.data) && r.json.data.length > 0, 'no products - did you run the seeders?');
  // pick any product that has a variant with enough stock for the order tests
  const found = r.json.data.find((p) => p.variants?.some((v) => v.stock >= 10));
  expect(found, 'no variant has 10+ in stock (earlier test runs used it up). Reset the demo data: in backend run "php artisan migrate:fresh --seed", then test again.');
  state.product = found;
  state.variant = found.variants.find((v) => v.stock >= 10);
  expect(state.variant.price_paise > 0, 'variant has no price');
});
await test('filter by category', async () => {
  const r = await call('GET', `/products?category=${state.category.slug}`);
  status(r, 200);
  expect(Array.isArray(r.json.data), 'bad response');
});
await test('sort by price (low to high is really ascending)', async () => {
  const r = await call('GET', '/products?sort=price_asc&per_page=50');
  status(r, 200);
  const prices = r.json.data.map((p) => Math.min(...p.variants.map((v) => v.price_paise)));
  expect(prices.every((p, i) => i === 0 || prices[i - 1] <= p), `prices not ascending: ${prices.join(',')}`);
});
await test('search finds a product by part of its name', async () => {
  const word = state.product.name.split(' ')[0];
  const r = await call('GET', `/products?search=${encodeURIComponent(word)}`);
  status(r, 200);
  expect(r.json.data.length > 0, `search "${word}" found nothing`);
});
await test('product detail page by slug', async () => {
  const r = await call('GET', `/products/${state.product.slug}`);
  status(r, 200);
  expect(r.json.data.slug === state.product.slug, 'wrong product returned');
});
await test('unknown product gives 404', async () => status(await call('GET', '/products/no-such-product-xyz'), 404));

// ---------- 2. customer account ----------
title('2. Customer account');
const email = `e2e+${RUN}@example.com`;
await test('register a new customer', async () => {
  const r = await call('POST', '/auth/register', { body: { name: 'E2E Tester', email, phone: '9' + String(Math.floor(Math.random() * 1e9)).padStart(9, '0'), password: 'Passw0rd!x', password_confirmation: 'Passw0rd!x' } });
  status(r, 200, 201);
  state.newToken = r.json?.data?.token;
  expect(state.newToken, 'register did not return a token');
});
await test('register with the same email is refused (422)', async () => {
  status(await call('POST', '/auth/register', { body: { name: 'Dup', email, password: 'Passw0rd!x', password_confirmation: 'Passw0rd!x' } }), 422);
});
await test('login with a wrong password is refused', async () => {
  status(await call('POST', '/auth/login', { body: { login: CUSTOMER.login, password: 'wrong-password' } }), 401, 422);
});
await test('login as the demo customer', async () => {
  const r = await call('POST', '/auth/login', { body: CUSTOMER });
  status(r, 200);
  state.token = r.json.data.token;
  expect(state.token, 'no token');
});
await test('/auth/me returns the logged-in user', async () => {
  const r = await call('GET', '/auth/me', { token: state.token });
  status(r, 200);
  expect(JSON.stringify(r.json).includes(CUSTOMER.login), 'wrong user returned');
});
await test('/auth/me without a token is 401', async () => status(await call('GET', '/auth/me'), 401));
await test('save an address, then list it', async () => {
  const r = await call('POST', '/addresses', { token: state.token, body: pay });
  status(r, 200, 201);
  const list = await call('GET', '/addresses', { token: state.token });
  status(list, 200);
  expect(list.json.data.length > 0, 'saved address not listed');
});

// ---------- 3. cart ----------
title('3. Cart');
await test('guest cart: add, change quantity, remove', async () => {
  let r = await call('GET', '/cart');
  status(r, 200, 201);
  const cart = r.json.data.token;
  r = await call('POST', '/cart/items', { cart, body: { variant_id: state.variant.id, qty: 2 } });
  status(r, 200, 201);
  expect(r.json.data.count === 2, `cart count should be 2, got ${r.json.data.count}`);
  const line = r.json.data.items[0];
  r = await call('PATCH', `/cart/items/${line.id}`, { cart, body: { qty: 3 } });
  status(r, 200);
  expect(r.json.data.count === 3, 'quantity did not change to 3');
  expect(r.json.data.subtotal_paise === 3 * line.unit_price_paise, 'subtotal is not 3 x price');
  r = await call('DELETE', `/cart/items/${line.id}`, { cart });
  status(r, 200);
  expect(r.json.data.count === 0, 'item was not removed');
});
await test('cannot add more than the stock', async () => {
  const cart = (await call('GET', '/cart')).json.data.token;
  const r = await call('POST', '/cart/items', { cart, body: { variant_id: state.variant.id, qty: state.variant.stock + 500 } });
  status(r, 409, 422);
});

// ---------- 4. checkout & fake payment ----------
title('4. Checkout and payment (fake gateway)');
async function placeOrder(token, qty = 2) {
  const cart = token ? undefined : (await call('GET', '/cart')).json.data.token;
  const add = await call('POST', '/cart/items', { token, cart, body: { variant_id: state.variant.id, qty } });
  status(add, 200, 201);
  const body = { shipping_method: 'standard', address: pay };
  if (!token) body.customer = { name: pay.name, email, phone: pay.phone };
  const r = await call('POST', '/checkout', { token, cart, body });
  status(r, 200, 201);
  return { r, cart };
}
await test('quote shows subtotal + shipping + GST = total', async () => {
  const cart = (await call('GET', '/cart')).json.data.token;
  await call('POST', '/cart/items', { cart, body: { variant_id: state.variant.id, qty: 1 } });
  const r = await call('POST', '/checkout/quote', { cart, body: { shipping_method: 'standard' } });
  status(r, 200);
  const q = r.json.data;
  expect(q.subtotal_paise + q.shipping_paise + q.tax_paise === q.total_paise, `totals do not add up: ${JSON.stringify(q)}`);
});
await test('GUEST: checkout, pay, order becomes confirmed + paid', async () => {
  const stockBefore = (await call('GET', `/products/${state.product.slug}`)).json.data.variants.find((v) => v.id === state.variant.id).stock;
  const { r, cart } = await placeOrder(null, 2);
  const order = r.json.data.order, payment = r.json.data.payment;
  expect(/^KKG-\d{4}-\d{6}$/.test(order.order_no), `odd order number ${order.order_no}`);
  expect(order.status === 'pending', `new order status should be pending, got ${order.status}`);
  const bad = await call('POST', '/payments/verify', { cart, body: { order_no: order.order_no, token: order.token, gateway_order_id: payment.gateway_order_id, gateway_payment_id: 'pay_x', signature: 'wrong' } });
  status(bad, 422);
  const ok = await call('POST', '/payments/verify', { cart, body: { order_no: order.order_no, token: order.token, gateway_order_id: payment.gateway_order_id, gateway_payment_id: 'pay_fake_' + RUN, signature: 'fake' } });
  status(ok, 200);
  expect(ok.json.data.status === 'confirmed' && ok.json.data.payment_status === 'paid', `after payment got ${ok.json.data.status}/${ok.json.data.payment_status}`);
  const stockAfter = (await call('GET', `/products/${state.product.slug}`)).json.data.variants.find((v) => v.id === state.variant.id).stock;
  expect(stockAfter === stockBefore - 2, `stock should drop by 2 (${stockBefore} -> ${stockAfter})`);
  state.guestOrder = order;
});
await test('GUEST: order is visible only with its token; tracking by number + email works', async () => {
  const o = state.guestOrder;
  status(await call('GET', `/orders/${o.order_no}`), 404);
  status(await call('GET', `/orders/${o.order_no}?token=${o.token}`), 200);
  const t = await call('POST', '/orders/track', { body: { order_no: o.order_no, email } });
  status(t, 200);
  expect(t.json.data.order_no === o.order_no, 'tracking returned another order');
});
await test('CUSTOMER: checkout + pay, shows in My Orders', async () => {
  const { r } = await placeOrder(state.token, 1);
  const order = r.json.data.order, payment = r.json.data.payment;
  const ok = await call('POST', '/payments/verify', { token: state.token, body: { order_no: order.order_no, gateway_order_id: payment.gateway_order_id, gateway_payment_id: 'pay_fake_c_' + RUN, signature: 'fake' } });
  status(ok, 200);
  const list = await call('GET', '/orders', { token: state.token });
  status(list, 200);
  expect(JSON.stringify(list.json).includes(order.order_no), 'order missing in My Orders');
  state.customerOrder = order;
});
await test('CUSTOMER: cancel an order gives the stock back', async () => {
  const before = (await call('GET', `/products/${state.product.slug}`)).json.data.variants.find((v) => v.id === state.variant.id).stock;
  const { r } = await placeOrder(state.token, 2); // unpaid -> can be cancelled
  const mid = (await call('GET', `/products/${state.product.slug}`)).json.data.variants.find((v) => v.id === state.variant.id).stock;
  expect(mid === before - 2, 'stock was not reserved at checkout');
  const c1 = await call('POST', `/orders/${r.json.data.order.order_no}/cancel`, { token: state.token });
  status(c1, 200);
  const after = (await call('GET', `/products/${state.product.slug}`)).json.data.variants.find((v) => v.id === state.variant.id).stock;
  expect(after === before, `stock not restored (${before} -> ${after})`);
});
await test('a customer cannot see another customer\'s order', async () => {
  status(await call('GET', `/orders/${state.customerOrder.order_no}`, { token: state.newToken }), 403, 404);
});

// ---------- 5. customer must not reach admin ----------
title('5. Security: customers are kept out of admin');
await test('no token -> 401 on admin API', async () => status(await call('GET', '/admin/dashboard'), 401));
await test('customer token -> 403 on admin API', async () => status(await call('GET', '/admin/dashboard', { token: state.token }), 403));
await test('customer cannot create a product', async () => status(await call('POST', '/admin/products', { token: state.token, body: {} }), 403));

// ---------- 6. admin ----------
title('6. Admin');
await test('admin login', async () => {
  const r = await call('POST', '/auth/login', { body: ADMIN });
  status(r, 200);
  state.admin = r.json.data.token;
  expect(state.admin, 'no admin token');
});
await test('dashboard loads', async () => status(await call('GET', '/admin/dashboard', { token: state.admin }), 200));
await test('create a category', async () => {
  const r = await call('POST', '/admin/categories', { token: state.admin, body: { name: 'E2E Category ' + RUN, is_active: true } });
  status(r, 200, 201);
  state.newCat = r.json.data;
});
await test('create a product with a variant -> visible in the shop', async () => {
  const r = await call('POST', '/admin/products', { token: state.admin, body: {
    name: 'E2E Product ' + RUN, category_id: state.newCat.id, description: 'Created by the automated test', is_active: true,
    variants: [{ sku: 'E2E-' + RUN, size_label: '100g', price_paise: 12300, stock: 20, is_active: true }],
  } });
  status(r, 200, 201);
  state.newProduct = r.json.data;
  const pub = await call('GET', `/products/${state.newProduct.slug}`);
  status(pub, 200);
});
await test('edit the product, then hide it -> disappears from the shop', async () => {
  const id = state.newProduct.id;
  const full = await call('GET', `/admin/products/${id}`, { token: state.admin });
  status(full, 200);
  const p = full.json.data;
  const payload = { name: p.name + ' edited', category_id: state.newCat.id, is_active: false,
    variants: p.variants.map((v) => ({ id: v.id, sku: v.sku, size_label: v.size_label, price_paise: v.price_paise, stock: v.stock, is_active: true })) };
  status(await call('PUT', `/admin/products/${id}`, { token: state.admin, body: payload }), 200);
  status(await call('GET', `/products/${state.newProduct.slug}`), 404);
});
await test('adjust stock (+5) and see it in inventory', async () => {
  const full = (await call('GET', `/admin/products/${state.newProduct.id}`, { token: state.admin })).json.data;
  const v = full.variants[0];
  const r = await call('POST', `/admin/inventory/${v.id}/adjust`, { token: state.admin, body: { change: 5, reason: 'e2e test' } });
  status(r, 200);
  const inv = await call('GET', '/admin/inventory?per_page=100&search=E2E', { token: state.admin });
  status(inv, 200);
});
await test('admin sees the guest order and moves it through the flow', async () => {
  const no = state.guestOrder.order_no;
  const show = await call('GET', `/admin/orders/${no}`, { token: state.admin });
  status(show, 200);
  const ship = await call('POST', `/admin/orders/${no}/shipment`, { token: state.admin });
  status(ship, 200, 201);
  const after = await call('GET', `/admin/orders/${no}`, { token: state.admin });
  expect(after.json.data.status === 'processing', `after creating the shipment order should be "processing", got "${after.json.data.status}"`);
  const shipments = await call('GET', '/admin/shipments?per_page=100', { token: state.admin });
  status(shipments, 200);
  const sid = ship.json.data.id;
  for (const s of ['in_transit', 'delivered']) {
    const u = await call('PUT', `/admin/shipments/${sid}/status`, { token: state.admin, body: { status: s } });
    if (u.status !== 200) {
      // status names differ per provider - show what is allowed
      throw new Error(`could not set shipment status "${s}": HTTP ${u.status} ${u.json?.message || ''}`);
    }
  }
  const done = await call('GET', `/admin/orders/${no}`, { token: state.admin });
  expect(done.json.data.status === 'delivered', `after delivery order should be "delivered", got "${done.json.data.status}"`);
});
await test('an unpaid order cannot be moved to shipped (409)', async () => {
  const { r } = await placeOrder(state.token, 1);
  const no = r.json.data.order.order_no;
  status(await call('PUT', `/admin/orders/${no}/status`, { token: state.admin, body: { status: 'shipped' } }), 409);
  await call('POST', `/orders/${no}/cancel`, { token: state.token }); // clean up
});
await test('payments, customers, users, settings lists load', async () => {
  for (const p of ['/admin/payments', '/admin/customers', '/admin/users', '/admin/roles', '/admin/settings', '/admin/orders']) {
    status(await call('GET', p, { token: state.admin }), 200);
  }
});
await test('admin logout kills the token', async () => {
  status(await call('POST', '/auth/logout', { token: state.admin }), 200);
  status(await call('GET', '/admin/dashboard', { token: state.admin }), 401);
});

// ---------- summary ----------
const failed = results.filter((r) => !r.ok);
console.log('\n' + c.b('Summary'));
console.log(`  ${results.length - failed.length} passed, ${failed.length} failed, ${results.length} total   ${c.d('(' + API + ')')}`);
if (failed.length) {
  console.log(c.r('\nFailed tests:'));
  for (const f of failed) console.log(`  - [${f.section}] ${f.name}\n      ${f.error}`);
  process.exit(1);
}
console.log(c.g('\nAll good.\n'));
