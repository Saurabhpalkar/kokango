import api from '@/services/api'

/** Paginated: returns { items, meta } */
export async function list(params = {}) {
  const res = await api.get('/orders', { params })
  return { items: res.data.data, meta: res.data.meta || {} }
}
export const show = async (orderNo, token) =>
  (await api.get(`/orders/${encodeURIComponent(orderNo)}`, { params: token ? { token } : {} })).data.data
export const cancel = async (orderNo) => (await api.post(`/orders/${encodeURIComponent(orderNo)}/cancel`)).data.data
export const track = async (orderNo, email) => (await api.post('/orders/track', { order_no: orderNo, email })).data.data
export const shipmentTrack = async (orderNo, token) =>
  (await api.get(`/shipments/${encodeURIComponent(orderNo)}/track`, { params: token ? { token } : {} })).data.data

// Where the confirmation page finds a just-placed order (the guest token is not kept anywhere else).
const LAST = 'kokango_last_order'
export function saveLastOrder(order_no, token) { try { sessionStorage.setItem(LAST, JSON.stringify({ order_no, token })) } catch (e) { /* ignore */ } }
export function readLastOrder() { try { return JSON.parse(sessionStorage.getItem(LAST) || 'null') } catch (e) { return null } }
