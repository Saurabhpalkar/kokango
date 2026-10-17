<script setup>
import AnnouncementBar from '@/components/AnnouncementBar.vue'
import SiteHeader from '@/components/SiteHeader.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import WhatsAppFab from '@/components/WhatsAppFab.vue'
import PhotoDeck from '@/components/PhotoDeck.vue'
import { WHATSAPP_URL } from '@/utils/links'
import { webpOf } from '@/utils/webp'
import { useCategories } from '@/composables/useCategories'
import { STATS, CATEGORY_PHOTOS } from '@/content/home'
import { ABOUT_HERO, CHAPTERS, VALUES, QUOTE, GALLERY } from '@/content/about'

const { categories } = useCategories()
const ICONS = {
  batch: '<path d="M4 10h16l-1.5 9a2 2 0 01-2 1.7h-9a2 2 0 01-2-1.7z"/><path d="M8 10V8a4 4 0 018 0v2"/>',
  leaf: '<path d="M5 19c0-8 5-14 15-14 0 10-6 15-14 15"/><path d="M5 19c3-4 6-6 10-8"/>',
  pack: '<path d="M6 4h12l1 16H5z"/><path d="M6 8h12M9 12c1.5 1 4.5 1 6 0"/>',
  truck: '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7.5" cy="17.5" r="1.7"/><circle cx="17.5" cy="17.5" r="1.7"/>',
  hands: '<path d="M7 11V6a1.5 1.5 0 013 0v4M10 10V4.5a1.5 1.5 0 013 0V10M13 10V5.5a1.5 1.5 0 013 0V12"/><path d="M16 9.5a1.5 1.5 0 013 0V14a7 7 0 01-7 7h-1a6 6 0 01-5-2.7L4 15.5a1.6 1.6 0 012.5-2L8 15"/>',
  heart: '<path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 7.9 3.6 4.5 7 4.5c2 0 3.4 1.1 5 3 1.6-1.9 3-3 5-3 3.4 0 5.6 3.4 4.3 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/>',
}
</script>

<template>
<div class="dcroot">
  <AnnouncementBar />
  <SiteHeader active="about" />

  <main>
    <!-- HERO: text + photos fanned like playing cards -->
    <section class="hero">
      <div class="wrap hero-in">
        <div class="hero-t">
          <span v-reveal class="kicker">{{ ABOUT_HERO.kicker }}</span>
          <h1 v-reveal="{ split: true }">{{ ABOUT_HERO.title }}</h1>
          <p v-reveal="{ delay: 250 }">{{ ABOUT_HERO.text }}</p>
          <div v-reveal="{ delay: 380 }" class="ctas">
            <a class="btn btn-sun" href="/products">Shop our products <span aria-hidden="true">&rarr;</span></a>
            <a class="btn btn-line" href="#story">Read our story</a>
          </div>
        </div>
        <PhotoDeck class="hero-deck" :photos="ABOUT_HERO.photos" :spread="34" />
      </div>
    </section>

    <!-- FIGURES -->
    <section class="figs" aria-label="Kokango in numbers">
      <ul class="wrap figs-in">
        <li v-for="(s, k) in STATS" :key="s.label" v-reveal="{ delay: k * 120 }"><strong v-count>{{ s.value }}</strong><span>{{ s.label }}</span></li>
      </ul>
    </section>

    <!-- STORY IN CHAPTERS -->
    <section id="story" class="sec wrap" aria-label="Our story">
      <article v-for="(c, k) in CHAPTERS" :key="c.title" class="chap" :class="{ flip: k % 2 }">
        <div v-reveal class="chap-img">
          <picture><source v-if="webpOf(c.image)" :srcset="webpOf(c.image)" type="image/webp"><img :src="c.image" alt="" loading="lazy" decoding="async" width="1000" height="700"></picture>
          <span class="chap-n" aria-hidden="true">0{{ k + 1 }}</span>
        </div>
        <div class="chap-t">
          <div v-reveal class="eyebrow">{{ c.kicker }}</div>
          <h2 v-reveal="{ delay: 80 }">{{ c.title }}</h2>
          <p v-reveal="{ delay: 160 }">{{ c.text }}</p>
        </div>
      </article>
    </section>

    <!-- VALUES -->
    <section class="sec band" aria-labelledby="val-h">
      <div class="wrap">
        <div v-reveal class="sec-c"><div class="eyebrow">What we stand for</div><h2 id="val-h" class="sec-title">Small things we never compromise on</h2></div>
        <ul class="vals">
          <li v-for="(v, k) in VALUES" :key="v.title" v-reveal="{ delay: (k % 3) * 110 }" class="val">
            <span class="val-i" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" v-html="ICONS[v.icon]"></svg></span>
            <h3>{{ v.title }}</h3>
            <p>{{ v.text }}</p>
          </li>
        </ul>
      </div>
    </section>

    <!-- QUOTE over a landscape -->
    <section class="quote" aria-label="Our promise">
      <picture class="quote-bg"><source v-if="webpOf(QUOTE.image)" :srcset="webpOf(QUOTE.image)" type="image/webp"><img :src="QUOTE.image" alt="" loading="lazy" decoding="async" width="1800" height="900"></picture>
      <figure class="wrap quote-in">
        <blockquote v-reveal="{ split: true }">&ldquo;{{ QUOTE.text }}&rdquo;</blockquote>
        <figcaption v-reveal="{ delay: 500 }">{{ QUOTE.by }}</figcaption>
      </figure>
    </section>

    <!-- WHAT WE MAKE -->
    <section v-if="categories.length" class="sec wrap" aria-labelledby="make-h">
      <div v-reveal class="sec-c"><div class="eyebrow">What we make</div><h2 id="make-h" class="sec-title">From our kitchen to yours</h2></div>
      <ul class="makes">
        <li v-for="(c, k) in categories" :key="c.slug" v-reveal="{ delay: k * 110 }">
          <a class="make" :href="`/products?category=${encodeURIComponent(c.slug)}`">
            <picture><source v-if="webpOf(CATEGORY_PHOTOS[c.slug])" :srcset="webpOf(CATEGORY_PHOTOS[c.slug])" type="image/webp"><img :src="CATEGORY_PHOTOS[c.slug] || '/images/logo.png'" alt="" loading="lazy" decoding="async" width="700" height="700"></picture>
            <span class="make-t"><strong>{{ c.name }}</strong><span>Shop {{ c.name.toLowerCase() }} <span aria-hidden="true">&rarr;</span></span></span>
          </a>
        </li>
      </ul>
    </section>

    <!-- GALLERY -->
    <section class="sec band" aria-labelledby="gal-h">
      <div class="wrap">
        <div v-reveal class="sec-c"><div class="eyebrow">Life in the Konkan</div><h2 id="gal-h" class="sec-title">A glimpse of where it all grows</h2></div>
        <ul class="gal">
          <li v-for="(g, k) in GALLERY" :key="g.src" v-reveal="{ delay: (k % 3) * 90 }" :class="'g' + k">
            <picture><source v-if="webpOf(g.src)" :srcset="webpOf(g.src)" type="image/webp"><img :src="g.src" :alt="g.alt" loading="lazy" decoding="async" width="900" height="900"></picture>
          </li>
        </ul>
      </div>
    </section>

    <!-- CTA -->
    <section class="sec wrap">
      <div v-reveal class="cta">
        <div>
          <h2>Questions? Talk to us</h2>
          <p>We reply on WhatsApp within a few hours, and we are happy to help you choose.</p>
        </div>
        <div class="cta-b">
          <a class="btn btn-sun" :href="WHATSAPP_URL" target="_blank" rel="noopener">WhatsApp us</a>
          <a class="btn btn-line light" href="/faq">Read the FAQ</a>
        </div>
      </div>
    </section>
  </main>

  <SiteFooter />
  <WhatsAppFab />
</div>
</template>

<style scoped>
/* hero */
.hero { position: relative; overflow: hidden; background: radial-gradient(circle at 85% 15%, rgba(254, 188, 4, .22), transparent 45%), radial-gradient(circle at 10% 90%, rgba(55, 146, 55, .16), transparent 45%), var(--bg); }
.hero-in { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 40px; align-items: center; padding-top: 64px; padding-bottom: 64px; }
.kicker { display: inline-block; padding: 7px 15px; border-radius: 999px; background: var(--tint); color: var(--forest); font-size: 12.5px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
.hero h1 { margin: 18px 0 0; font-size: clamp(34px, 5vw, 60px); line-height: 1.05; font-weight: 800; letter-spacing: -1.6px; text-wrap: balance; }
.hero p { margin: 18px 0 0; max-width: 520px; font-size: 17px; line-height: 1.75; color: var(--muted); }
.ctas { margin-top: 28px; display: flex; flex-wrap: wrap; gap: 12px; }

/* figures */
.figs { background: var(--forest); color: #D9E6DB; }
.figs-in { list-style: none; margin: 0 auto; padding-top: 34px; padding-bottom: 34px; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; text-align: center; }
.figs strong { display: block; font-size: clamp(26px, 3.6vw, 42px); font-weight: 800; color: var(--mango); letter-spacing: -.8px; line-height: 1.1; }
.figs span { display: block; margin-top: 4px; font-size: 14px; font-weight: 600; }

/* sections */
.sec { padding-top: 72px; padding-bottom: 72px; }
.band { background: var(--surface); }

/* chapters */
.chap { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 56px; align-items: center; }
.chap + .chap { margin-top: 72px; }
.chap.flip .chap-img { order: 2; }
.chap-img { position: relative; }
.chap-img img { width: 100%; aspect-ratio: 10 / 7; object-fit: cover; border-radius: 24px; display: block; box-shadow: 0 24px 50px rgba(16, 61, 44, .18); transition: transform .8s var(--ease); }
.chap-img:hover img { transform: scale(1.02) rotate(-.5deg); }
.chap-n { position: absolute; top: -22px; left: -14px; font-size: 64px; font-weight: 800; line-height: 1; color: var(--mango); text-shadow: 0 6px 20px rgba(0, 0, 0, .12); font-family: 'Fraunces', Georgia, serif; }
.chap.flip .chap-n { left: auto; right: -14px; }
.chap-t h2 { margin: 8px 0 0; font-size: clamp(26px, 3.4vw, 38px); font-weight: 800; letter-spacing: -.8px; line-height: 1.1; }
.chap-t p { margin: 16px 0 0; font-size: 16.5px; line-height: 1.8; color: var(--muted); max-width: 520px; }

/* values */
.vals { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; }
.val { padding: 28px; border-radius: 20px; background: var(--bg); border: 1px solid var(--line); transition: transform .3s var(--ease), box-shadow .3s, border-color .3s; }
.val:hover { transform: translateY(-6px); box-shadow: 0 18px 40px rgba(16, 61, 44, .12); border-color: rgba(55, 146, 55, .4); }
.val-i { width: 54px; height: 54px; border-radius: 16px; background: var(--tint); color: var(--forest); display: inline-flex; align-items: center; justify-content: center; transition: transform .35s var(--ease), background .35s; }
.val:hover .val-i { transform: rotate(-8deg) scale(1.08); background: var(--sun); }
.val h3 { margin: 16px 0 0; font-size: 19px; font-weight: 800; }
.val p { margin: 8px 0 0; font-size: 14.5px; line-height: 1.7; color: var(--muted); }

/* quote */
.quote { position: relative; isolation: isolate; overflow: hidden; color: #fff; }
.quote-bg { position: absolute; inset: 0; z-index: -2; }
.quote-bg img { width: 100%; height: 100%; object-fit: cover; transform: scale(1.08); animation: drift 24s ease-in-out infinite alternate; }
.quote::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(90deg, rgba(6, 26, 18, .82), rgba(6, 26, 18, .45)); }
@keyframes drift { from { transform: scale(1.08) translateX(-2%); } to { transform: scale(1.14) translateX(2%); } }
.quote-in { margin: 0 auto; padding-top: 110px; padding-bottom: 110px; }
.quote blockquote { margin: 0; max-width: 860px; font-family: 'Fraunces', Georgia, serif; font-size: clamp(28px, 4.2vw, 50px); font-weight: 700; line-height: 1.2; letter-spacing: -.5px; }
.quote figcaption { margin-top: 20px; font-size: 14px; font-weight: 800; letter-spacing: 1.6px; text-transform: uppercase; color: var(--mango); }

/* what we make */
.makes { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 22px; }
.make { position: relative; display: block; aspect-ratio: 4 / 3; border-radius: 22px; overflow: hidden; color: #fff; }
.make img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .8s var(--ease); }
.make::after { content: ''; position: absolute; inset: 0; background: linear-gradient(0deg, rgba(6, 26, 18, .85) 0%, transparent 60%); }
.make:hover img { transform: scale(1.08); }
.make-t { position: absolute; z-index: 1; left: 22px; right: 22px; bottom: 20px; display: flex; flex-direction: column; gap: 2px; }
.make-t strong { font-size: 24px; font-weight: 800; letter-spacing: -.4px; }
.make-t > span { font-size: 14px; font-weight: 700; color: var(--mango); transition: transform .3s var(--ease); }
.make:hover .make-t > span { transform: translateX(6px); }
.make:hover { color: #fff; }

/* gallery mosaic */
.gal { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); grid-auto-rows: 220px; gap: 14px; }
.gal li { border-radius: 18px; overflow: hidden; }
.gal img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .8s var(--ease), filter .4s; }
.gal li:hover img { transform: scale(1.08); filter: saturate(1.15); }
/* a full 4 x 3 mosaic: one big, one tall, two wide and two small tiles */
.g0 { grid-column: 1 / 3; grid-row: 1 / 3; }
.g1 { grid-column: 3; grid-row: 1; }
.g2 { grid-column: 3; grid-row: 2; }
.g3 { grid-column: 4; grid-row: 1 / 3; }
.g4 { grid-column: 1 / 3; grid-row: 3; }
.g5 { grid-column: 3 / 5; grid-row: 3; }

/* cta */
.cta { display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; padding: 44px 48px; border-radius: 26px; background: radial-gradient(circle at 100% 0%, rgba(254, 188, 4, .3), transparent 50%), var(--forest); color: #fff; }
.cta h2 { margin: 0; font-size: clamp(26px, 3.4vw, 36px); font-weight: 800; letter-spacing: -.8px; }
.cta p { margin: 8px 0 0; font-size: 15.5px; color: #D3E2D6; }
.cta-b { display: flex; gap: 12px; flex-wrap: wrap; }
.light { color: #fff; border-color: rgba(255, 255, 255, .6); }
.light:hover { background: #fff; color: var(--forest); }

@media (max-width: 900px) {
  .hero-in { grid-template-columns: 1fr; padding-top: 36px; padding-bottom: 44px; gap: 18px; }
  .hero-deck { max-width: 440px; }
  .chap { grid-template-columns: 1fr; gap: 26px; }
  .chap.flip .chap-img { order: 0; }
  .chap + .chap { margin-top: 56px; }
  .vals { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .gal { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: 170px; }
  .gal li { grid-column: auto !important; grid-row: auto !important; }
  .gal .g0, .gal .g5 { grid-column: span 2 !important; }
}
@media (max-width: 600px) {
  .sec { padding-top: 48px; padding-bottom: 48px; }
  .hero p { font-size: 15.5px; }
  .ctas .btn { flex: 1 1 auto; }
  .figs-in { gap: 8px; padding-top: 26px; padding-bottom: 26px; }
  .figs strong { font-size: 21px; }
  .figs span { font-size: 11.5px; }
  .chap-n { font-size: 46px; top: -16px; left: -4px; }
  .chap.flip .chap-n { right: 0; }
  .chap-t p { font-size: 15px; }
  .vals { grid-template-columns: 1fr; gap: 12px; }
  .val { padding: 22px; }
  .quote-in { padding-top: 72px; padding-bottom: 72px; }
  .gal { grid-auto-rows: 130px; gap: 8px; }
  .cta { padding: 30px 22px; }
  .cta-b .btn { flex: 1 1 auto; }
}
@media (prefers-reduced-motion: reduce) { .quote-bg img { animation: none; } }
</style>
