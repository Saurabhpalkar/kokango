// Shared, cached list of shop categories (header, footer, home and shop all use it).
// Reads the same /categories endpoint the pages already used; only fetched once per page load.
import { ref, computed } from 'vue'
import * as catalogue from '@/services/catalogueService'

const all = ref([])
const loaded = ref(false)
let inflight = null

function load(force = false) {
  if (!force && (loaded.value || inflight)) return inflight
  inflight = catalogue.categories()
    .then((list) => { all.value = Array.isArray(list) ? list : []; loaded.value = true })
    .catch(() => { /* the header and footer simply show no category links */ })
    .finally(() => { inflight = null })
  return inflight
}

export function useCategories() {
  load()
  // Hide categories that have no products (the API sends products_count when it knows it).
  const categories = computed(() => all.value.filter((c) => c.products_count == null || c.products_count > 0))
  return { categories, all, loaded, reload: () => load(true) }
}
