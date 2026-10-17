import api from '@/services/api'

// Short-lived in-memory cache for read-only catalogue calls: going back to a page or opening the same product twice is instant
// instead of showing skeletons again. Same endpoints, same responses; entries expire quickly so prices and stock stay fresh,
// and a failed request is never cached. Call clearCatalogueCache() after anything that should show fresh data (e.g. checkout).
const TTL = { categories: 5 * 60e3, products: 45e3, product: 30e3, settings: 5 * 60e3 }
const store = new Map()
function cached(key, ttl, fetcher) {
  const hit = store.get(key)
  if (hit && hit.exp > Date.now()) return hit.promise
  const promise = fetcher().catch((e) => { store.delete(key); throw e })
  store.set(key, { exp: Date.now() + ttl, promise })
  return promise
}
export const clearCatalogueCache = () => store.clear()

export const categories = () => cached('categories', TTL.categories, async () => (await api.get('/categories')).data.data)
/** Paginated: returns { items, meta } */
export const products = (params = {}) => cached('products:' + JSON.stringify(params), TTL.products, async () => {
  const res = await api.get('/products', { params })
  return { items: res.data.data, meta: res.data.meta || { current_page: 1, last_page: 1, per_page: params.per_page || 20, total: res.data.data.length } }
})
export const product = (slug) => cached('product:' + slug, TTL.product, async () => (await api.get(`/products/${encodeURIComponent(slug)}`)).data.data)
export const publicSettings = () => cached('settings', TTL.settings, async () => (await api.get('/settings/public')).data.data)

/** First active, in-stock variant (lowest price first); falls back to the first active one. */
export function defaultVariant(product) {
  const act = (product?.variants || []).filter((v) => v.is_active !== false).sort((a, b) => a.price_paise - b.price_paise)
  return act.find((v) => v.in_stock) || act[0] || null
}
