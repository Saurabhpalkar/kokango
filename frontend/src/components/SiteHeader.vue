<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useWishlistStore } from '@/stores/wishlist'
import { useCategories } from '@/composables/useCategories'
import BrandLogo from '@/components/BrandLogo.vue'

const props = defineProps({
  // Which nav link is highlighted: 'home' | 'products' | 'about' | 'faq' | 'orders' | ''
  active: { type: String, default: '' },
  // 'full' = brand + search + nav + icons; 'minimal' = brand + optional right-side slot (checkout / auth pages)
  variant: { type: String, default: 'full' },
  // accepted for backwards compatibility: search is now always shown in the full header
  search: { type: Boolean, default: false },
  // accepted for backwards compatibility: the account button is now shown on every page
  loginButton: { type: Boolean, default: false },
  // highlight an icon: 'cart' | 'account' | ''
  activeIcon: { type: String, default: '' },
  // replaces the "About" link with "My Orders" (order tracking page)
  ordersNav: { type: Boolean, default: false },
})

const router = useRouter()
const route = useRoute()
const cart = useCartStore()
const auth = useAuthStore()
const wishlist = useWishlistStore()
const { categories } = useCategories()

const open = ref(false)          // mobile menu
const searchOpen = ref(false)    // mobile search row
const scrolled = ref(false)
const root = ref(null)
const burger = ref(null)
const searchInput = ref(null)
const q = ref(typeof route.query.search === 'string' ? route.query.search : '')

const catLinks = computed(() => categories.value.slice(0, 6).map((c) => ({
  key: 'cat:' + c.slug, slug: c.slug, label: c.name, to: `/products?category=${encodeURIComponent(c.slug)}`,
})))
const navItems = computed(() => [
  { key: 'home', label: 'Home', to: '/' },
  { key: 'products', label: 'Shop', to: '/products' },
  ...catLinks.value,
  props.ordersNav ? { key: 'orders', label: 'My Orders', to: '/account' } : { key: 'about', label: 'About', to: '/about' },
  { key: 'faq', label: 'FAQ', to: '/faq' },
])
function isOn(n) {
  if (n.slug) return route.path === '/products' && route.query.category === n.slug
  if (n.key === 'products') return props.active === 'products' && !route.query.category
  return props.active === n.key
}

function close(focusBurger = false) {
  if (!open.value) return
  open.value = false
  if (focusBurger && burger.value) burger.value.focus()
}
function toggle() { open.value = !open.value; if (open.value) searchOpen.value = false }
async function toggleSearch() {
  searchOpen.value = !searchOpen.value
  if (searchOpen.value) { open.value = false; await nextTick(); searchInput.value && searchInput.value.focus() }
}
function doSearch() {
  const term = q.value.trim()
  searchOpen.value = false
  router.push(term ? { path: '/products', query: { search: term } } : { path: '/products' })
}
function onDocClick(e) { if (open.value && root.value && !e.composedPath().includes(root.value)) close() }
function onKey(e) { if (e.key === 'Escape') { close(true); searchOpen.value = false } }
function onResize() { if (window.innerWidth >= 900) { close(); searchOpen.value = false } }
let ticking = false
function onScroll() {
  if (ticking) return
  ticking = true
  requestAnimationFrame(() => { scrolled.value = window.scrollY > 8; ticking = false })
}
async function logout() {
  close()
  await auth.logout()
  router.push('/')
}

watch(() => route.fullPath, () => { close(); searchOpen.value = false })
watch(() => route.query.search, (v) => { q.value = typeof v === 'string' ? v : '' })
onMounted(() => {
  document.addEventListener('click', onDocClick)
  document.addEventListener('keydown', onKey)
  window.addEventListener('resize', onResize)
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocClick)
  document.removeEventListener('keydown', onKey)
  window.removeEventListener('resize', onResize)
  window.removeEventListener('scroll', onScroll)
})
</script>

<template>
  <header ref="root" class="site-header on-dark" :class="{ minimal: variant === 'minimal', scrolled }">
    <div class="hdr-top">
      <div class="hdr-in">
        <button v-if="variant !== 'minimal'" ref="burger" class="hbtn burger" type="button" :aria-expanded="open ? 'true' : 'false'" aria-controls="site-nav-panel" :aria-label="open ? 'Close menu' : 'Open menu'" @click="toggle">
          <svg v-if="!open" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
          <svg v-else width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
        <BrandLogo class="brand" />

        <template v-if="variant === 'minimal'">
          <div class="slot"><slot /></div>
        </template>

        <template v-else>
          <form class="search" :class="{ open: searchOpen }" role="search" @submit.prevent="doSearch">
            <label for="hdr-search" class="sr-only">Search products</label>
            <input id="hdr-search" ref="searchInput" v-model="q" type="search" placeholder="Search for products" autocomplete="off" enterkeyhint="search">
            <button type="submit" aria-label="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg></button>
          </form>

          <div class="actions">
            <button class="hbtn msearch" type="button" :aria-expanded="searchOpen ? 'true' : 'false'" aria-label="Search" @click="toggleSearch"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg></button>
            <a class="hlogin" :class="{ on: activeIcon === 'account' }" :href="auth.isLoggedIn ? '/account' : '/login'"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/></svg><span>{{ auth.isLoggedIn ? 'Account' : 'Login' }}</span></a>
            <a class="hbtn" :class="{ on: activeIcon === 'wishlist' }" href="/wishlist" :aria-label="`Wishlist, ${wishlist.count} saved`"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.4 1.1 5 3 1.6-1.9 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg><span v-if="wishlist.count" :key="'w' + wishlist.count" class="badge-n pop" aria-hidden="true">{{ wishlist.count }}</span></a>
            <a class="hbtn" :class="{ on: activeIcon === 'cart' }" href="/cart" :aria-label="`Cart, ${cart.count} items`"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8V6a3 3 0 016 0v2"/></svg><span v-if="cart.count" :key="'c' + cart.count" class="badge-n pop" aria-hidden="true">{{ cart.count }}</span></a>
          </div>
        </template>
      </div>
    </div>

    <template v-if="variant !== 'minimal'">
      <div class="hdr-nav">
        <div class="hdr-in nav-in">
          <nav class="nav" aria-label="Main">
            <a v-for="n in navItems" :key="n.key" class="navlink" :class="{ on: isOn(n) }" :aria-current="isOn(n) ? 'page' : null" :href="n.to">{{ n.label }}</a>
          </nav>
          <a class="nav-aside" href="/track-order">Track order</a>
        </div>
      </div>

      <transition name="drop">
        <div v-if="open" id="site-nav-panel" class="panel" @click="close()">
          <a v-for="n in navItems" :key="n.key" class="plink" :class="{ on: isOn(n) }" :href="n.to">{{ n.label }}</a>
          <a class="plink" href="/wishlist">My wishlist<span v-if="wishlist.count" class="pcount">{{ wishlist.count }}</span></a>
          <a class="plink" href="/track-order">Track order</a>
          <template v-if="auth.isLoggedIn">
            <a class="plink" :class="{ on: activeIcon === 'account' || active === 'orders' }" href="/account">My account</a>
            <a class="plink" href="/orders">My orders</a>
            <button class="plink out" type="button" @click.stop="logout">Logout</button>
          </template>
          <a v-else class="plink cta" href="/login">Login or register</a>
        </div>
      </transition>
    </template>
  </header>
</template>

<style scoped>
.site-header { position: sticky; top: 0; z-index: 60; width: 100vw; margin-left: calc(50% - 50vw); color: #fff; }
.hdr-top { background: var(--forest); transition: box-shadow .25s; }
.site-header.scrolled .hdr-top { box-shadow: 0 6px 18px rgba(10, 42, 30, .28); }
.hdr-in { max-width: var(--wrap); margin: 0 auto; padding: 10px var(--gutter); display: flex; align-items: center; gap: 12px 28px; }

.brand { display: flex; align-items: center; gap: 10px; flex: none; color: #fff; font-weight: 800; font-size: 22px; letter-spacing: -.4px; }
.brand:hover { color: #fff; }
.brand-mark { width: 40px; height: 40px; border-radius: 10px; background: #fff; display: flex; align-items: center; justify-content: center; flex: none; }
.brand-mark img { height: 28px; width: auto; }
.slot { margin-left: auto; min-width: 0; }

/* search */
.search { flex: 1 1 auto; max-width: 560px; margin: 0 auto; display: flex; background: #fff; border-radius: 999px; overflow: hidden; transition: box-shadow .2s; }
.search:focus-within { box-shadow: 0 0 0 3px rgba(254, 188, 4, .75); }
.search input { flex: 1; min-width: 0; height: 44px; border: 0; background: transparent; padding: 0 6px 0 20px; font-size: 14px; font-weight: 500; color: var(--ink); outline: none; -webkit-appearance: none; appearance: none; }
.search input::-webkit-search-cancel-button { -webkit-appearance: none; }
.search button { width: 54px; flex: none; border: 0; background: var(--mango); color: var(--ink); display: flex; align-items: center; justify-content: center; transition: background .2s; }
.search button:hover { background: var(--mango-hover); }

/* header actions */
.actions { display: flex; align-items: center; gap: 10px; margin-left: auto; flex: none; }
.hbtn, .hlogin {
  position: relative; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  height: 44px; color: #fff; background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .25); border-radius: 999px;
  transition: background .2s, border-color .2s, color .2s;
}
.hbtn { width: 44px; padding: 0; }
.hlogin { padding: 0 18px 0 14px; font-size: 14px; font-weight: 600; }
.hbtn:hover, .hlogin:hover, .hbtn.on, .hlogin.on { background: rgba(255, 255, 255, .16); border-color: var(--mango); color: #fff; }
.badge-n { position: absolute; top: -5px; right: -5px; min-width: 20px; height: 20px; padding: 0 5px; border-radius: 10px; background: var(--mango); color: var(--ink); font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; border: 2px solid var(--forest); }
.msearch, .burger { display: none; }
/* the count pops when something is added */
.pop { animation: pop .45s var(--ease); }
@keyframes pop { 0% { transform: scale(.4); } 60% { transform: scale(1.25); } 100% { transform: scale(1); } }

/* category nav row */
.hdr-nav { background: var(--surface); color: var(--ink); border-bottom: 1px solid var(--line); }
.nav-in { padding-top: 0; padding-bottom: 0; justify-content: space-between; }
.nav { display: flex; align-items: center; gap: 0 28px; overflow-x: auto; scrollbar-width: none; min-width: 0; }
.nav::-webkit-scrollbar { display: none; }
.navlink { position: relative; display: inline-flex; align-items: center; min-height: 46px; font-size: 14px; font-weight: 600; color: var(--ink); white-space: nowrap; }
.navlink::after { content: ''; position: absolute; left: 0; right: 0; bottom: 6px; height: 3px; border-radius: 2px; background: var(--mango); transform: scaleX(0); transform-origin: left; transition: transform .25s var(--ease); }
.navlink:hover { color: var(--forest); }
.navlink:hover::after, .navlink.on::after { transform: scaleX(1); }
.navlink.on { color: var(--forest); font-weight: 700; }
.nav-aside { flex: none; font-size: 13px; font-weight: 600; color: var(--muted); min-height: 46px; display: inline-flex; align-items: center; }
.nav-aside:hover { color: var(--forest); }
.hdr-nav :focus-visible { outline-color: var(--leaf); }

/* mobile menu panel */
.panel { position: absolute; left: 0; right: 0; top: 100%; background: #fff; color: var(--ink); border-bottom: 1px solid var(--line); box-shadow: var(--shadow-md); padding: 8px var(--gutter) 16px; max-height: calc(100vh - 110px); overflow-y: auto; display: flex; flex-direction: column; }
.plink { display: flex; align-items: center; width: 100%; min-height: 48px; padding: 0 14px; border-radius: var(--r-sm); font-size: 16px; font-weight: 600; color: var(--ink); background: none; border: 0; text-align: left; }
.plink:hover, .plink:focus-visible { background: var(--tint); color: var(--forest); }
.plink.on { background: var(--tint); color: var(--forest); font-weight: 700; }
.plink.cta { margin-top: 8px; justify-content: center; background: var(--forest); color: #fff; }
.plink.cta:hover { background: var(--forest-deep); color: #fff; }
.plink.out { color: var(--danger); }
.pcount { margin-left: 8px; min-width: 22px; height: 22px; padding: 0 6px; border-radius: 11px; background: var(--mango); color: var(--ink); font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
.drop-enter-active, .drop-leave-active { transition: opacity .2s ease, transform .2s var(--ease); }
.drop-enter-from, .drop-leave-to { opacity: 0; transform: translateY(-8px); }

.minimal .hdr-in { padding-top: 12px; padding-bottom: 12px; }

@media (max-width: 899px) {
  .hdr-nav { display: none; }
  /* one compact row: menu | logo (centred) | search + cart; the search field opens below */
  .hdr-in { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; padding-top: 6px; padding-bottom: 6px; gap: 6px 8px; }
  .minimal .hdr-in { display: flex; }
  .burger { justify-self: start; }
  .brand { justify-self: center; font-size: 20px; gap: 8px; }
  .brand-mark { width: 34px; height: 34px; border-radius: 9px; }
  .brand-mark img { height: 24px; }
  .actions { justify-self: end; gap: 2px; margin-left: 0; }
  .hlogin { display: none; }
  .msearch, .burger { display: inline-flex; }
  .hbtn { width: 40px; height: 40px; background: transparent; border-color: transparent; }
  .hbtn:hover, .hbtn.on { background: rgba(255, 255, 255, .12); border-color: transparent; }
  .search { display: none; grid-column: 1 / -1; max-width: none; margin: 2px 0 4px; }
  .search.open { display: flex; }
}
@media (max-width: 359px) { .brand-name { display: none; } }
@media (min-width: 900px) {
  .panel { display: none; }
}
.brand { min-height: 44px; }
</style>
