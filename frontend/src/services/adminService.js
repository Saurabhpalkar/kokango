import api from '@/services/api'

// Thin wrappers around every /admin endpoint (see docs/API.md). Money is always paise.
// Single-object calls return the unwrapped `data`; list calls return { data, meta } as sent by the API.
const body = (r) => r.data
const one = (r) => r.data.data
const clean = (params = {}) => Object.fromEntries(Object.entries(params).filter(([, v]) => v !== '' && v !== null && v !== undefined && v !== 'all'))

export const adminDashboard = () => api.get('/admin/dashboard').then(one)

export const adminProducts = {
  list: (params) => api.get('/admin/products', { params: clean(params) }).then(body),
  get: (id) => api.get(`/admin/products/${id}`).then(one),
  create: (payload) => api.post('/admin/products', payload).then(one),
  update: (id, payload) => api.put(`/admin/products/${id}`, payload).then(one),
  /** Returns the API body: { message } or, when the product was only deactivated, may include { data: Product }. */
  remove: (id) => api.delete(`/admin/products/${id}`).then(body),
  uploadImage: (id, file) => {
    const fd = new FormData()
    fd.append('image', file)
    return api.post(`/admin/products/${id}/image`, fd).then(one)
  },
}

export const adminCategories = {
  list: () => api.get('/admin/categories').then(one),
  create: (payload) => api.post('/admin/categories', payload).then(one),
  update: (id, payload) => api.put(`/admin/categories/${id}`, payload).then(one),
  remove: (id) => api.delete(`/admin/categories/${id}`).then(body),
}

export const adminInventory = {
  list: (params) => api.get('/admin/inventory', { params: clean(params) }).then(one),
  /** payload: { stock } (absolute) or { change, reason? }; reason is also accepted with stock. */
  adjust: (variantId, payload) => api.post(`/admin/inventory/${variantId}/adjust`, payload).then(one),
}

export const adminOrders = {
  list: (params) => api.get('/admin/orders', { params: clean(params) }).then(body),
  get: (orderNo) => api.get(`/admin/orders/${encodeURIComponent(orderNo)}`).then(one),
  updateStatus: (orderNo, status) => api.put(`/admin/orders/${encodeURIComponent(orderNo)}/status`, { status }).then(one),
  createShipment: (orderNo) => api.post(`/admin/orders/${encodeURIComponent(orderNo)}/shipment`).then(one),
}

export const adminPayments = {
  list: (params) => api.get('/admin/payments', { params: clean(params) }).then(body),
  refund: (id) => api.post(`/admin/payments/${id}/refund`).then(one),
}

export const adminShipments = {
  list: (params) => api.get('/admin/shipments', { params: clean(params) }).then(body),
  updateStatus: (id, status) => api.put(`/admin/shipments/${id}/status`, { status }).then(one),
  sync: (id) => api.post(`/admin/shipments/${id}/sync`).then(one),
}

export const adminCustomers = {
  list: (params) => api.get('/admin/customers', { params: clean(params) }).then(body),
  update: (id, payload) => api.put(`/admin/customers/${id}`, payload).then(one),
}

export const adminSettings = {
  get: () => api.get('/admin/settings').then(one),
  save: (payload) => api.put('/admin/settings', payload).then(one),
}

export const adminUsers = {
  list: (params) => api.get('/admin/users', { params: clean(params) }).then(body),
  create: (payload) => api.post('/admin/users', payload).then(one),
  update: (id, payload) => api.put(`/admin/users/${id}`, payload).then(one),
  roles: () => api.get('/admin/roles').then(one),
}
