<script setup>
// Saved products (kept in this browser). Fresh prices and stock come from the shop API.
import { computed, onMounted, ref } from 'vue'
import AnnouncementBar from '@/components/AnnouncementBar.vue'
import SiteHeader from '@/components/SiteHeader.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import WhatsAppFab from '@/components/WhatsAppFab.vue'
import ProductCard from '@/components/ProductCard.vue'
import { useWishlistStore } from '@/stores/wishlist'
import * as catalogue from '@/services/catalogueService'

const wishlist = useWishlistStore()
const live = ref({})            // slug -> full product from the API
const loading = ref(false)

onMounted(async () => {
  if (!wishlist.count) return
  loading.value = true
  try {
    const { items } = await catalogue.products({ per_page: 100 })
    live.value = Object.fromEntries(items.map((p) => [p.slug, p]))
  } catch (e) { /* show the saved copies */ }
  finally { loading.value = false }
})
// A product that is no longer sold disappears from the shop list: show it as unavailable.
const rows = computed(() => wishlist.items.map((x) => ({ saved: x, product: live.value[x.slug] || null })))
</script>

<template>
<div class="dcroot">
  <AnnouncementBar />
  <SiteHeader active="" active-icon="wishlist" />

  <main class="wrap page">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a><span aria-hidden="true">/</span><span aria-current="page">Wishlist</span></nav>
    <h1>My wishlist</h1>
    <p class="sub">{{ wishlist.count ? `${wishlist.count} saved ${wishlist.count === 1 ? 'product' : 'products'}` : 'Tap the heart on any product to save it here.' }}</p>

    <div v-if="!wishlist.count" class="card empty">
      <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.4 1.1 5 3 1.6-1.9 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg>
      <div class="empty-t">Your wishlist is empty</div>
      <a class="btn" href="/products">Browse products</a>
    </div>

    <div v-else class="pgrid">
      <template v-for="r in rows" :key="r.saved.slug">
        <ProductCard v-if="r.product" :product="r.product" />
        <div v-else-if="!loading" class="gone card">
          <img :src="r.saved.image_url" alt="" width="300" height="300">
          <div class="gone-b"><strong>{{ r.saved.name }}</strong><span>No longer available</span>
            <button type="button" class="btn btn-line btn-sm" @click="wishlist.remove(r.saved.slug)">Remove</button></div>
        </div>
      </template>
    </div>
  </main>

  <SiteFooter />
  <WhatsAppFab />
</div>
</template>

<style scoped>
.page { padding-top: 22px; padding-bottom: 20px; }
.crumbs { display: flex; gap: 8px; font-size: 13px; color: var(--muted); }
.crumbs a { color: var(--muted); }
.crumbs [aria-current] { color: var(--ink); font-weight: 600; }
h1 { margin: 6px 0 0; font-size: clamp(26px, 3.6vw, 38px); font-weight: 800; letter-spacing: -.8px; }
.sub { margin: 6px 0 24px; color: var(--muted); font-size: 15px; }
.empty { padding: 48px 24px; display: flex; flex-direction: column; align-items: center; gap: 14px; text-align: center; color: var(--leaf); }
.empty-t { font-size: 18px; font-weight: 800; color: var(--ink); }
.gone { overflow: hidden; display: flex; flex-direction: column; }
.gone img { width: 100%; aspect-ratio: 1; object-fit: cover; filter: grayscale(1); opacity: .6; }
.gone-b { padding: 12px; display: flex; flex-direction: column; gap: 6px; font-size: 14px; }
.gone-b span { color: var(--muted); font-size: 13px; }
</style>
