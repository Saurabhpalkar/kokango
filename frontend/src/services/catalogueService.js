import api from '@/services/api'

export const categories = async () => (await api.get('/categories')).data.data
/** Paginated: returns { items, meta } */
export async function products(params = {}) {
  const res = await api.get('/products', { params })
  return { items: res.data.data, meta: res.data.meta || { current_page: 1, last_page: 1, per_page: params.per_page || 20, total: res.data.data.length } }
}
export const product = async (slug) => (await api.get(`/products/${encodeURIComponent(slug)}`)).data.data
export const publicSettings = async () => (await api.get('/settings/public')).data.data

/** First active, in-stock variant (lowest price first); falls back to the first active one. */
export function defaultVariant(product) {
  const act = (product?.variants || []).filter((v) => v.is_active !== false).sort((a, b) => a.price_paise - b.price_paise)
  return act.find((v) => v.in_stock) || act[0] || null
}
