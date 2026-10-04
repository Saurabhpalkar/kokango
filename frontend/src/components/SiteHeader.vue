<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  // Which nav link is coloured green: 'home' | 'products' | 'about' | 'faq' | 'orders' | ''
  active: { type: String, default: '' },
  // 'full' = logo + nav + icons; 'minimal' = logo + optional right-side slot (checkout / auth pages)
  variant: { type: String, default: 'full' },
  // show the search icon button (home, products)
  search: { type: Boolean, default: false },
  // show a "Login" button instead of the account icon on desktop (home)
  loginButton: { type: Boolean, default: false },
  // highlight an icon in green: 'cart' | 'account' | ''
  activeIcon: { type: String, default: '' },
  // replaces the "About" link with "My Orders" (order tracking page)
  ordersNav: { type: Boolean, default: false },
})

const router = useRouter()
const route = useRoute()
const cart = useCartStore()
const auth = useAuthStore()

const open = ref(false)
const root = ref(null)
const burger = ref(null)

const navItems = computed(() => [
  { key: 'home', label: 'Home', to: '/' },
  { key: 'products', label: 'Products', to: '/products' },
  props.ordersNav ? { key: 'orders', label: 'My Orders', to: '/account' } : { key: 'about', label: 'About', to: '/about' },
  { key: 'faq', label: 'FAQ', to: '/faq' },
])
const panelItems = computed(() => [
  { key: 'home', label: 'Home', to: '/' },
  { key: 'products', label: 'Products', to: '/products' },
  { key: 'about', label: 'About', to: '/about' },
  { key: 'faq', label: 'FAQ', to: '/faq' },
  { key: 'track', label: 'Track order', to: '/track-order' },
])

function close(focusBurger = false) {
  if (!open.value) return
  open.value = false
  if (focusBurger && burger.value) burger.value.focus()
}
function toggle() { open.value = !open.value }
function onDocClick(e) { if (open.value && root.value && !e.composedPath().includes(root.value)) close() }
function onKey(e) { if (e.key === 'Escape') close(true) }
function onResize() { if (window.innerWidth >= 900) close() }
async function logout() {
  close()
  await auth.logout()
  router.push('/')
}

watch(() => route.fullPath, () => close())
onMounted(() => {
  document.addEventListener('click', onDocClick)
  document.addEventListener('keydown', onKey)
  window.addEventListener('resize', onResize)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onDocClick)
  document.removeEventListener('keydown', onKey)
  window.removeEventListener('resize', onResize)
})
</script>

<template>
  <header ref="root" class="site-header" :class="{ minimal: variant === 'minimal', 'logo-only': variant === 'minimal' && !$slots.default }">
    <a href="/" class="brand"><img src="/images/logo.png" alt="Kokango logo"><span>Kokango</span></a>

    <template v-if="variant === 'minimal'">
      <slot />
    </template>

    <template v-else>
      <nav class="nav" aria-label="Main">
        <a v-for="n in navItems" :key="n.key" class="navlink" :class="{ on: active === n.key }" :href="n.to">{{ n.label }}</a>
      </nav>

      <div class="actions">
        <button v-if="search" class="icon" type="button" aria-label="Search" @click="router.push('/products?focus=search')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg></button>
        <a class="icon" :class="{ on: activeIcon === 'cart' }" href="/cart" :aria-label="`Cart, ${cart.count} items`"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8V6a3 3 0 016 0v2"/></svg><span v-if="cart.count" class="badge">{{ cart.count }}</span></a>
        <a v-if="loginButton" class="btn desk-only" :href="auth.isLoggedIn ? '/account' : '/login'">{{ auth.isLoggedIn ? 'My account' : 'Login' }}</a>
        <a class="icon acct" :class="{ on: activeIcon === 'account', 'mob-only': loginButton }" href="/account" aria-label="Account"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/></svg></a>
        <button
          ref="burger"
          class="icon burger"
          type="button"
          :aria-expanded="open ? 'true' : 'false'"
          aria-controls="site-nav-panel"
          :aria-label="open ? 'Close menu' : 'Open menu'"
          @click="toggle"
        >
          <svg v-if="!open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
          <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
      </div>

      <div id="site-nav-panel" class="panel" :class="{ open }" :hidden="!open" @click="close()">
        <a v-for="n in panelItems" :key="n.key" class="plink" :class="{ on: active === n.key }" :href="n.to">{{ n.label }}</a>
        <a class="plink psearch" href="/products?focus=search">Search products</a>
        <template v-if="auth.isLoggedIn">
          <a class="plink" :class="{ on: activeIcon === 'account' || active === 'orders' }" href="/account">My account</a>
          <a class="plink" href="/orders">My orders</a>
          <button class="plink out" type="button" @click.stop="logout">Logout</button>
        </template>
        <a v-else class="plink on-btn" href="/login">Login</a>
      </div>
    </template>
  </header>
</template>

<style scoped>
a { color: var(--ink); text-decoration: none; }
a:hover { color: var(--green); }
button { font-family: inherit; cursor: pointer; }

.site-header {
  position: relative;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 24px 0;
}
.brand { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 24px; letter-spacing: -0.5px; }
.brand img { height: 46px; width: auto; }
/* logo-only header (auth pages): keep the same 4px line-box slack the old inline markup had */
.logo-only { padding-bottom: 28px; }

.nav { display: flex; flex-wrap: wrap; gap: 36px; align-items: center; }
.navlink { font-size: 15px; font-weight: 500; padding: 10px 4px; position: relative; }
.navlink::after { content: ''; position: absolute; left: 0; bottom: 2px; width: 0; height: 2px; background: var(--mango); transition: width .3s; }
.navlink:hover::after { width: 100%; }
.navlink.on { color: var(--green); }

.actions { display: flex; gap: 10px; align-items: center; }

.icon {
  width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center;
  background: #fff; color: var(--ink); border: 1px solid var(--line); border-radius: 50%;
  padding: 0; position: relative; transition: all .25s; flex: none;
}
.icon:hover { border-color: var(--green); color: var(--green); transform: translateY(-2px); }
.icon.on { border-color: var(--green); color: var(--green); }
.badge {
  position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px; border-radius: 9px;
  background: var(--mango); color: var(--ink); font-size: 11px; font-weight: 700;
  display: inline-flex; align-items: center; justify-content: center;
}

.btn {
  background: var(--green); color: #fff; border: none; border-radius: 999px; padding: 10px 22px;
  font-size: 15px; font-weight: 600; min-height: 44px; box-sizing: border-box;
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  transition: transform .25s, background .25s, box-shadow .25s;
}
.btn:hover { background: #08481a; color: #fff; transform: translateY(-2px); box-shadow: 0 10px 22px rgba(11, 93, 30, 0.25); }

.burger, .panel, .mob-only { display: none; }

@media (max-width: 899px) {
  .site-header { flex-wrap: nowrap; gap: 12px; padding: 16px 0; }
  .site-header.minimal { flex-wrap: wrap; }
  .site-header.logo-only { padding-bottom: 16px; }
  .brand { font-size: 22px; gap: 10px; min-width: 0; }
  .brand img { height: 38px; }
  .nav, .desk-only { display: none !important; }
  .actions { gap: 8px; }
  .burger { display: inline-flex; }
  .mob-only { display: inline-flex; }

  .panel {
    position: absolute; z-index: 40; left: 0; right: 0; top: calc(100% - 6px);
    background: #fff; border: 1px solid var(--line); border-radius: 24px;
    box-shadow: 0 18px 40px rgba(20, 48, 28, 0.14); padding: 8px;
    flex-direction: column;
  }
  .panel.open { display: flex; }
  .plink {
    display: flex; align-items: center; min-height: 48px; padding: 0 16px; box-sizing: border-box;
    border-radius: 16px; font-size: 16px; font-weight: 600; width: 100%;
    background: none; border: none; text-align: left; color: var(--ink);
  }
  .plink:hover, .plink:focus-visible { background: var(--tint); color: var(--green); }
  .plink.on { color: var(--green); background: var(--tint); }
  .plink.on-btn { margin-top: 6px; justify-content: center; background: var(--green); color: #fff; }
  .plink.on-btn:hover { background: #08481a; color: #fff; }
  .plink.out { margin-top: 2px; color: var(--amber); }
}

@media (min-width: 600px) and (max-width: 899px) {
  .psearch { display: none; }
}
@media (max-width: 599px) {
  /* search lives on the products page; keep room for logo + cart + menu on small phones */
  .actions .icon[aria-label="Search"], .acct { display: none; }
}
@media (max-width: 380px) {
  .brand { font-size: 20px; gap: 8px; }
  .brand img { height: 34px; }
}
</style>
