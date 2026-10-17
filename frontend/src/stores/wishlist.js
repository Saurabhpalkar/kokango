import { defineStore } from 'pinia'
import { useToastStore } from '@/stores/toast'

// Wishlist kept in this browser (there is no wishlist API yet). Stores a small copy of each product so the
// wishlist page can show it straight away; prices are refreshed from the API when the page opens.
const KEY = 'kokango_wishlist'
const read = () => { try { const v = JSON.parse(localStorage.getItem(KEY) || '[]'); return Array.isArray(v) ? v : [] } catch (e) { return [] } }
const pick = (p) => ({ id: p.id, slug: p.slug, name: p.name, image_url: p.image_url, min_price_paise: p.min_price_paise, category: p.category ? { name: p.category.name, slug: p.category.slug } : null })

export const useWishlistStore = defineStore('wishlist', {
  state: () => ({ items: read() }),
  getters: {
    count: (s) => s.items.length,
    has: (s) => (slug) => s.items.some((x) => x.slug === slug),
  },
  actions: {
    _save() { try { localStorage.setItem(KEY, JSON.stringify(this.items)) } catch (e) { /* storage blocked */ } },
    toggle(product) {
      const toast = useToastStore()
      if (this.has(product.slug)) {
        this.items = this.items.filter((x) => x.slug !== product.slug)
        toast.show(`${product.name} removed from your wishlist`)
      } else {
        this.items = [pick(product), ...this.items]
        toast.show(`${product.name} saved to your wishlist`)
      }
      this._save()
    },
    remove(slug) { this.items = this.items.filter((x) => x.slug !== slug); this._save() },
  },
})
