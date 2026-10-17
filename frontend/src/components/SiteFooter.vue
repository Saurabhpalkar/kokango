<script setup>
import { computed } from 'vue'
import { WHATSAPP_URL, FACEBOOK_URL, INSTAGRAM_URL } from '@/utils/links'
import { useCategories } from '@/composables/useCategories'
import BananaLeaves from '@/components/BananaLeaves.vue'
import BrandLogo from '@/components/BrandLogo.vue'

const props = defineProps({
  // Kept for backwards compatibility: both values render the full footer when `links` is on.
  variant: { type: String, default: 'simple' },
  // false = slim copyright bar only (checkout, account and order pages)
  links: { type: Boolean, default: true },
  // true when the section above is already dark (no gap before the footer)
  flush: { type: Boolean, default: false },
})

const { categories } = useCategories()
const year = new Date().getFullYear()
const full = computed(() => props.links !== false)

// Social links come from .env; links.js falls back to generic site roots, which are not worth showing.
const socials = computed(() => [
  { key: 'facebook', label: 'Kokango on Facebook', url: FACEBOOK_URL, generic: 'https://www.facebook.com/' },
  { key: 'instagram', label: 'Kokango on Instagram', url: INSTAGRAM_URL, generic: 'https://www.instagram.com/' },
  { key: 'whatsapp', label: 'Chat with Kokango on WhatsApp', url: WHATSAPP_URL, generic: 'https://wa.me/' },
].filter((s) => s.url && s.url !== s.generic))
</script>

<template>
  <footer v-if="full" class="foot on-dark" :class="{ flush }">
    <BananaLeaves />
    <div class="foot-in">
      <div class="top">
        <div class="col brandcol">
          <BrandLogo size="lg" />
          <p class="blurb">Authentic Konkan flavours, packed fresh and shipped across India.</p>
          <div v-if="socials.length" class="social">
            <a v-for="s in socials" :key="s.key" :href="s.url" target="_blank" rel="noopener" :aria-label="s.label">
              <svg v-if="s.key === 'facebook'" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 21v-7.5h2.6l.4-3h-3V8.6c0-.9.3-1.5 1.5-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H7.8v3h2.6V21h3.1z"/></svg>
              <svg v-else-if="s.key === 'instagram'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4.5"/><circle cx="12" cy="12" r="3.6"/><circle cx="16.8" cy="7.2" r="1" fill="currentColor" stroke="none"/></svg>
              <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3a9 9 0 00-7.8 13.5L3 21l4.7-1.2A9 9 0 1012 3zm0 2a7 7 0 11-3.6 13l-.3-.2-2.2.6.6-2.1-.2-.3A7 7 0 0112 5zm-2.6 3.3c-.2 0-.5.1-.7.4-.3.3-.9.9-.9 2.200s.9 2.500 1 2.700c.1.2 1.800 2.900 4.500 3.900 2.200.8 2.600.700 3.100.600.5-.1 1.500-.6 1.700-1.200.2-.6.2-1.100.2-1.200-.1-.1-.2-.2-.5-.3l-1.600-.8c-.2-.1-.4-.1-.6.1l-.7.900c-.1.200-.3.200-.5.100-.9-.4-2-1-2.800-2.400-.1-.2 0-.3.100-.4l.4-.5c.1-.1.100-.3.200-.4 0-.2 0-.3-.1-.4l-.7-1.800c-.2-.5-.4-.4-.6-.4z"/></svg>
            </a>
          </div>
        </div>

        <nav class="col" aria-label="Shop">
          <h2 class="h">Shop</h2>
          <ul>
            <li><a href="/products">All products</a></li>
            <li v-for="c in categories" :key="c.slug"><a :href="`/products?category=${encodeURIComponent(c.slug)}`">{{ c.name }}</a></li>
          </ul>
        </nav>

        <nav class="col" aria-label="Help">
          <h2 class="h">Help</h2>
          <ul>
            <li><a href="/track-order">Track order</a></li>
            <li><a href="/faq">FAQ</a></li>
            <li><a href="/about">About Kokango</a></li>
            <li><a href="/account">My account</a></li>
            <li><a href="/vendor/login">Sell on Kokango</a></li>
          </ul>
        </nav>

        <nav class="col" aria-label="Policies">
          <h2 class="h">Policies</h2>
          <ul>
            <li><a href="/policy/shipping">Shipping Policy</a></li>
            <li><a href="/policy/refund">Refund Policy</a></li>
            <li><a href="/policy/terms">Terms &amp; Conditions</a></li>
            <li><a href="/policy/privacy">Privacy Policy</a></li>
          </ul>
        </nav>
      </div>

      <div class="bottom">
        <span>&copy; {{ year }} Kokango. All rights reserved.</span>
        <span class="pay">Secure payments with Razorpay</span>
      </div>
    </div>
  </footer>

  <footer v-else class="foot slim on-dark">
    <div class="foot-in"><div class="bottom only">&copy; {{ year }} Kokango. All rights reserved.</div></div>
  </footer>
</template>

<style scoped>
.foot { width: 100vw; margin-left: calc(50% - 50vw); margin-top: 80px; background: var(--forest); color: #D9E6DB; }
.foot.slim { margin-top: 64px; }
.foot.flush { margin-top: 0; border-top: 1px solid rgba(255, 255, 255, .1); }
.foot:not(.slim) { position: relative; overflow: hidden; }
.foot-in { position: relative; z-index: 1; max-width: var(--wrap); margin: 0 auto; padding: 0 var(--gutter); }
.top { display: grid; grid-template-columns: 1.4fr repeat(3, 1fr); gap: 40px; padding: 56px 0 40px; }
.col { min-width: 0; }
.brand { display: inline-flex; align-items: center; gap: 10px; color: #fff; font-weight: 800; font-size: 22px; letter-spacing: -.4px; }
.brand:hover { color: #fff; }
.brand-mark { width: 40px; height: 40px; border-radius: 10px; background: #fff; display: flex; align-items: center; justify-content: center; }
.brand-mark img { height: 28px; width: auto; }
.blurb { margin: 16px 0 18px; font-size: 14px; line-height: 1.7; color: #BFD0C4; max-width: 340px; }
.social { display: flex; gap: 10px; }
.social a { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; border: 1px solid rgba(255, 255, 255, .28); color: #fff; transition: background .2s, border-color .2s, color .2s; }
.social a:hover { background: var(--mango); border-color: var(--mango); color: var(--ink); }
.h { margin: 0 0 14px; font-size: 13px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; color: var(--mango); }
ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 2px; }
li a { display: inline-flex; align-items: center; min-height: 36px; font-size: 14px; color: #D9E6DB; }
li a:hover { color: #fff; text-decoration: underline; text-underline-offset: 4px; text-decoration-color: var(--mango); }
.bottom { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px 24px; padding: 20px 0 28px; border-top: 1px solid rgba(255, 255, 255, .14); font-size: 13px; color: #A9BFB0; }
.bottom.only { border-top: 0; padding: 22px 0 28px; justify-content: center; }

@media (max-width: 899px) {
  .foot { margin-top: 64px; }
  .top { grid-template-columns: 1fr 1fr; gap: 32px 24px; padding: 44px 0 28px; }
  .brandcol { grid-column: 1 / -1; }
}
@media (max-width: 480px) {
  .foot { margin-top: 48px; }
  .top { grid-template-columns: 1fr 1fr; }
  li a { min-height: 40px; }
  .bottom { flex-direction: column; }
}
</style>
