// Home page state: hero slider (auto-plays), top-selling carousel, collection slider, story video.
// Products and categories come from the API; the marketing copy (reviews, ticker, stats text) is static.
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import * as catalogue from '@/services/catalogueService'
import { useCartStore } from '@/stores/cart'
import { useToastStore } from '@/stores/toast'
import { inrp } from '@/utils/format'
import { apiError } from '@/services/api'

export function useHomePage() {
  const heroIdx = ref(0)
  const topIdx = ref(0)
  const colIdx = ref(0)
  const playing = ref(false)
  const vw = ref(typeof window !== 'undefined' ? window.innerWidth : 1280)
  const onResize = () => { vw.value = window.innerWidth }
  let timer = null
  const router = useRouter()
  const cart = useCartStore()
  const toast = useToastStore()

  const products = ref([])
  const total = ref(0)
  const categories = ref([])
  const loading = ref(true)
  const error = ref('')
  const bySlug = (slug) => products.value.find((p) => p.slug === slug)

  async function load() {
    loading.value = true; error.value = ''
    try {
      const [list, cats] = await Promise.all([catalogue.products({ per_page: 100, sort: 'newest' }), catalogue.categories()])
      products.value = list.items; total.value = list.meta.total ?? list.items.length; categories.value = cats
    } catch (e) { error.value = apiError(e).message }
    finally { loading.value = false }
  }

  // add the product's default (lowest priced, in stock) variant
  async function addVariant(slug) {
    const p = bySlug(slug)
    if (!p) return null
    const v = catalogue.defaultVariant(p)
    if (!v || !v.in_stock) { toast.show(`${p.name} is out of stock`); return null }
    return (await cart.add(v.id, 1)) ? p : null
  }
  const addToCart = async (slug) => { const p = await addVariant(slug); if (p) toast.show(`${p.name} added to cart`) }
  const buyNow = async (slug) => { if (await addVariant(slug)) router.push('/checkout') }
  const subscribe = (email) => {
    if (!email || !String(email).trim()) { toast.show('Please enter your email address'); return false }
    toast.show('Thanks for subscribing!')
    return true
  }

  onMounted(() => {
    window.addEventListener('resize', onResize)
    onResize()
    load()
    timer = setInterval(() => { const n = Math.min(3, products.value.length); if (n) heroIdx.value = (heroIdx.value + 1) % n }, 4500)
  })
  onBeforeUnmount(() => { if (timer) clearInterval(timer); window.removeEventListener('resize', onResize) })

  return computed(() => {
    const st = { heroIdx: heroIdx.value, topIdx: topIdx.value, colIdx: colIdx.value, playing: playing.value }
const pick = (prefer, n) => {
      const first = prefer.map(bySlug).filter(Boolean);
      return first.concat(products.value.filter((p) => !first.includes(p))).slice(0, n);
    };
    const heroBgs = ['linear-gradient(160deg,#FFF1CC,#fff)', 'linear-gradient(160deg,#EAF3DD,#fff)', 'linear-gradient(160deg,#FFF1CC,#fff)'];
    const hero = pick(['moringa-powder', 'jackfruit-chips', 'jamun-vadi'], 3).map((p, i) => ({ name: p.name, price: inrp(p.min_price_paise), img: p.image_url, bg: heroBgs[i] }));
    const heroN = hero.length;
    st.heroIdx = heroN ? st.heroIdx % heroN : 0;
    const heroSlides = hero.map((h, i) => ({
      name: h.name, img: h.img, bg: h.bg,
      op: i === st.heroIdx ? 1 : 0, pe: i === st.heroIdx ? 'auto' : 'none',
      scale: i === st.heroIdx ? 1 : 0.7, shift: i === st.heroIdx ? 0 : 30
    }));
    const dots = (n, cur, setter) => Array.from({ length: n }, (_, i) => ({
      n: i + 1, w: i === cur ? '28px' : '8px', bg: i === cur ? '#0B5D1E' : '#cfcab4', go: () => setter(i)
    }));
    const topList = pick(['moringa-powder', 'curry-leaf-powder', 'tulsi-powder', 'neem-powder', 'beetroot-powder', 'jackfruit-chips', 'jamun-vadi'], 8);
    // how many 284px cards (24px gap) fit in the container: 4 on desktop, 1 on phones
    const gutter = vw.value <= 600 ? 16 : 32;
    const perView = Math.max(1, Math.floor((Math.min(1280, vw.value) - 2 * gutter + 24) / 308));
    const maxTop = Math.max(0, topList.length - perView);
    st.topIdx = Math.min(st.topIdx, maxTop);
    const col = [
      { title: 'Herbal powders for everyday wellness', text: 'Hand-picked herbal powders made from carefully dried leaves and roots, ground fresh and sealed in resealable pouches.',
        i1: '/images/moringa-powder.png', i2: '/images/tulsi-powder.png', i3: '/images/beetroot-powder.png', d3: 'block' },
      { title: 'Crunchy snacks and sweet treats', text: 'Crispy jackfruit chips and tangy jamun vadi, made the traditional way for a taste that feels like home.',
        i1: '/images/jackfruit-chips.png', i2: '/images/jamun-vadi.png', i3: '/images/jamun-vadi.png', d3: 'none' },
      { title: 'Cooling leaf powders', text: 'Curry leaf, neem and tulsi powders that blend easily into your daily recipes and routines.',
        i1: '/images/curry-leaf-powder.png', i2: '/images/neem-powder.png', i3: '/images/tulsi-powder.png', d3: 'block' }
    ];
    const colSlides = col.map((c, i) => Object.assign({}, c, { op: i === st.colIdx ? 1 : 0, pe: i === st.colIdx ? 'auto' : 'none' }));
    const tone = '#EAF3DD', tone2 = '#FFF1CC';
    const rv = [
      { initial: 'R', name: 'Rohan Patil', text: 'The jackfruit chips were crisp and fresh. Ordered again for the whole family.' },
      { initial: 'S', name: 'Sneha Joshi', text: 'Lovely quality powders and quick delivery. The packaging kept everything fresh.' },
      { initial: 'A', name: 'Amit Deshmukh', text: 'Authentic Konkan taste that reminds me of home. Will order the jamun vadi again.' },
      { initial: 'N', name: 'Neha Sawant', text: 'The moringa powder blends smoothly into my morning smoothie. Great value.' },
      { initial: 'V', name: 'Vikram Naik', text: 'Packed neatly and delivered on time. The jamun vadi is a family favourite now.' }
    ];
    const tk = ['Small-batch fresh', 'Straight from the Konkan', 'Herbal powders', 'Crispy snacks', 'Delivery across India', 'Secure Razorpay payments'];
    return {
      heroSlides: heroSlides,
      heroName: hero[st.heroIdx]?.name || '',
      heroPrice: hero[st.heroIdx]?.price || '',
      heroDots: dots(heroN, st.heroIdx, (i) => heroIdx.value = i),
      heroNext: () => { if (heroN) heroIdx.value = (heroIdx.value + 1) % heroN },
      heroPrev: () => { if (heroN) heroIdx.value = (heroIdx.value + heroN - 1) % heroN },
      ticker: tk.concat(tk).concat(tk).concat(tk).map((t) => ({ text: t })),
      cats: categories.value.map((c, i) => {
        const inCat = products.value.filter((p) => p.category?.slug === c.slug);
        const n = c.products_count ?? inCat.length;
        return { slug: c.slug, name: c.name, count: n + (n === 1 ? ' product' : ' products'), bg: i % 2 ? '#FFF1CC' : '#EAF3DD', img: inCat[0]?.image_url || '/images/logo.png' };
      }),
      stats: [
        { value: '5,000+', label: 'Happy customers', color: '#0B5D1E' },
        { value: String(total.value), label: 'Fresh products', color: '#14301C' },
        { value: '4.8', label: 'Average rating', color: '#9A5B00' },
        { value: '3 to 5', label: 'Days delivery', color: '#0B5D1E' }
      ],
      trendy: pick(['jackfruit-chips', 'jamun-vadi'], 2).map((p, i) => {
        const v = catalogue.defaultVariant(p);
        return {
          slug: p.slug, tag: i === 0 ? 'BESTSELLER' : 'NEW', dir: i === 0 ? 'row' : 'row-reverse', bg: i === 0 ? '#FFF1CC' : '#EAF3DD',
          name: p.name, text: `${p.hindi_name || ''} · ${p.description || ''}${v ? ' ' + v.size_label + ' pack.' : ''}`.trim(), price: inrp(p.min_price_paise), img: p.image_url,
        };
      }),
      top: topList.map((p, i) => {
        const v = catalogue.defaultVariant(p);
        return { slug: p.slug, name: p.name, text: `${p.hindi_name || ''} · ${v ? v.size_label : ''} pack`, price: inrp(p.min_price_paise), badge: p.badge || ['TOP', 'FRESH', 'POPULAR'][i % 3], tone: i % 2 ? tone2 : tone, img: p.image_url };
      }),
      loading: loading.value, error: error.value, reload: load, hasProducts: products.value.length > 0,
      addToCart, buyNow, subscribe,
      topShift: -st.topIdx * 308,
      topPrevOp: st.topIdx === 0 ? 0.35 : 1,
      topNextOp: st.topIdx === maxTop ? 0.35 : 1,
      topPrev: () => topIdx.value = Math.max(0, topIdx.value - 1),
      topNext: () => topIdx.value = Math.min(maxTop, topIdx.value + 1),
      notPlaying: !st.playing,
      playing: st.playing,
      play: () => playing.value = true,
      reviews: rv.concat(rv),
      colSlides: colSlides,
      colDots: dots(3, st.colIdx, (i) => colIdx.value = i),
      colNext: () => colIdx.value = (colIdx.value + 1) % 3,
      colPrev: () => colIdx.value = (colIdx.value + 2) % 3
    };
  })
}
