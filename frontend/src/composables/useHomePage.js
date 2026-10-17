// Home page state: product data from the API, the banner slider and the per-category sections.
// Products and categories come from the shop API; banner text and photos come from content/home.js.
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import * as catalogue from '@/services/catalogueService'
import { useCategories } from '@/composables/useCategories'
import { apiError } from '@/services/api'
import { HERO_SLIDES, CATEGORY_PHOTOS } from '@/content/home'

// Merchandising order for "Best sellers"; anything else follows in API order.
const BEST_PICKS = ['moringa-powder', 'jackfruit-chips', 'jamun-vadi', 'curry-leaf-powder', 'tulsi-powder', 'beetroot-powder', 'neem-powder']
const AUTOPLAY_MS = 6000

export function useHomePage() {
  const { categories: shopCategories } = useCategories()
  const products = ref([])
  const loading = ref(true)
  const error = ref('')

  async function load() {
    loading.value = true; error.value = ''
    try { products.value = (await catalogue.products({ per_page: 100, sort: 'newest' })).items }
    catch (e) { error.value = apiError(e).message }
    finally { loading.value = false }
  }

  const bySlug = (slug) => products.value.find((p) => p.slug === slug)
  const pick = (prefer, n) => {
    const first = prefer.map(bySlug).filter(Boolean)
    return first.concat(products.value.filter((p) => !first.includes(p))).slice(0, n)
  }

  // ---- banner slider: photos from content/home.js, with the real product packs on top (desktop)
  const slides = computed(() => HERO_SLIDES.map((s) => ({
    ...s,
    packImages: s.packs.map(bySlug).filter(Boolean).map((p) => p.image_url).slice(0, 3),
  })))

  const idx = ref(0)
  const playing = ref(true)
  const hovering = ref(false)
  const reduceMotion = ref(false)
  const current = computed(() => idx.value % slides.value.length)
  const go = (i) => { const n = slides.value.length; idx.value = ((i % n) + n) % n }
  const next = () => go(current.value + 1)
  const prev = () => go(current.value - 1)
  const autoplaying = computed(() => playing.value && !hovering.value && !reduceMotion.value && slides.value.length > 1)

  let timer = null
  let startX = 0
  let startY = 0
  const onTouchStart = (e) => { const t = e.changedTouches[0]; startX = t.clientX; startY = t.clientY; hovering.value = true }
  const onTouchEnd = (e) => {
    const t = e.changedTouches[0]
    const dx = t.clientX - startX
    if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(t.clientY - startY) * 1.4) (dx < 0 ? next : prev)()
    hovering.value = false
  }

  let mq = null
  const onMq = () => { reduceMotion.value = !!(mq && mq.matches) }
  onMounted(() => {
    load()
    mq = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null
    onMq()
    mq && mq.addEventListener && mq.addEventListener('change', onMq)
    timer = setInterval(() => { if (autoplaying.value && !document.hidden) next() }, AUTOPLAY_MS)
  })
  onBeforeUnmount(() => {
    clearInterval(timer)
    mq && mq.removeEventListener && mq.removeEventListener('change', onMq)
  })

  // ---- categories (empty ones are skipped) and one product section per category
  const categoryTiles = computed(() => shopCategories.value.map((c) => {
    const first = products.value.find((p) => p.category?.slug === c.slug)
    return { slug: c.slug, name: c.name, photo: CATEGORY_PHOTOS[c.slug] || first?.image_url || '/images/logo.png' }
  }))

  // best sellers with category tabs (one product list instead of a section per category)
  const tab = ref('')
  const tabs = computed(() => [{ slug: '', name: 'All' }, ...shopCategories.value
    .filter((c) => products.value.some((p) => p.category?.slug === c.slug))
    .map((c) => ({ slug: c.slug, name: c.name }))])
  const bestSellers = computed(() => (tab.value
    ? pick(BEST_PICKS, 100).filter((p) => p.category?.slug === tab.value).slice(0, 8)
    : pick(BEST_PICKS, 8)))

  return {
    loading, error, reload: load, hasProducts: computed(() => products.value.length > 0),
    slides, current, go, next, prev, playing, autoplaying,
    togglePlay: () => { playing.value = !playing.value },
    onHeroEnter: () => { hovering.value = true }, onHeroLeave: () => { hovering.value = false },
    onTouchStart, onTouchEnd,
    categoryTiles, tabs, tab, bestSellers,
  }
}
