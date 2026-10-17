<script setup>
import AnnouncementBar from '@/components/AnnouncementBar.vue'
import SiteHeader from '@/components/SiteHeader.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import WhatsAppFab from '@/components/WhatsAppFab.vue'
import ProductCard from '@/components/ProductCard.vue'
import StoryVideo from '@/components/StoryVideo.vue'
import PalmTree from '@/components/PalmTree.vue'
import { STATS, STORY, PROMISES, ABOUT, STEPS, TESTIMONIALS, FAQ_COUNT } from '@/content/home'
import { getFaqData } from '@/content/faq'
import { WHATSAPP_URL } from '@/utils/links'
import { useHomePage } from '@/composables/useHomePage'

const {
  loading, error, reload, hasProducts,
  slides, current, go, next, prev, playing, autoplaying, togglePlay,
  onHeroEnter, onHeroLeave, onTouchStart, onTouchEnd,
  categoryTiles, tabs, tab, bestSellers,
} = useHomePage()

const faqs = getFaqData().faqs.slice(0, FAQ_COUNT)
// line icons used by the trust strip and the steps
const ICON = {
  leaf: '<path d="M5 19c0-8 5-14 15-14 0 10-6 15-14 15"/><path d="M5 19c3-4 6-6 10-8"/>',
  truck: '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7.5" cy="17.5" r="1.7"/><circle cx="17.5" cy="17.5" r="1.7"/>',
  cash: '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6.5 9.5v5M17.5 9.5v5"/>',
  shield: '<path d="M12 3l8 3v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
  sprout: '<path d="M12 21v-9"/><path d="M12 12c0-4-3-6-7-6 0 4 3 6 7 6zM12 10c0-3.5 2.5-6 7-6 0 4-3 6-7 6z"/>',
  pot: '<path d="M4 10h16l-1.5 9a2 2 0 01-2 1.7h-9a2 2 0 01-2-1.7z"/><path d="M8 10V8a4 4 0 018 0v2M9 4c0-1 1-1 1-2M14 4c0-1 1-1 1-2"/>',
  pack: '<path d="M6 4h12l1 16H5z"/><path d="M6 8h12M9 12c1.5 1 4.5 1 6 0"/>',
}
</script>

<template>
<div class="dcroot">
  <AnnouncementBar />
  <SiteHeader active="home" />

  <main>
    <!-- BANNER SLIDER -->
    <section v-season-scene class="hero" role="region" aria-roledescription="carousel" aria-label="Featured"
      @mouseenter="onHeroEnter" @mouseleave="onHeroLeave" @touchstart.passive="onTouchStart" @touchend.passive="onTouchEnd">
      <div class="track" :style="{ transform: `translateX(-${current * 100}%)` }" :aria-live="autoplaying ? 'off' : 'polite'">
        <article v-for="(s, i) in slides" :key="s.id" class="slide" :class="{ live: i === current }" role="group" aria-roledescription="slide"
          :aria-label="`${i + 1} of ${slides.length}`" :aria-hidden="i !== current ? 'true' : null" :inert="i !== current ? '' : null">
          <picture class="slide-bg">
            <source media="(max-width: 700px)" :srcset="s.imageMobile">
            <img :src="s.image" alt="" :loading="i === 0 ? 'eager' : 'lazy'" :fetchpriority="i === 0 ? 'high' : null" decoding="async" width="1800" height="900">
          </picture>
          <div class="slide-in">
            <div class="stext">
              <span class="kicker">{{ s.kicker }}</span>
              <h1 v-if="i === 0" class="stitle">{{ s.title }}</h1>
              <h2 v-else class="stitle">{{ s.title }}</h2>
              <p class="sp">{{ s.text }}</p>
              <a class="btn btn-sun scta" :href="s.cta.to">{{ s.cta.label }} <span aria-hidden="true">&rarr;</span></a>
            </div>
            <div v-if="s.packImages.length" class="packs" aria-hidden="true">
              <img v-for="(src, k) in s.packImages" :key="k" :class="'p' + k + ' n' + s.packImages.length" :src="src" alt="" width="300" height="380" loading="lazy">
            </div>
          </div>
        </article>
      </div>

      <button type="button" class="harrow l" aria-label="Previous slide" @click="prev"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></button>
      <button type="button" class="harrow r" aria-label="Next slide" @click="next"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>
      <div class="hctrl">
        <button type="button" class="hplay" :aria-label="playing ? 'Pause slideshow' : 'Play slideshow'" @click="togglePlay">
          <svg v-if="playing" width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h4v14H7zM13 5h4v14h-4z"/></svg>
          <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5l12 7-12 7z"/></svg>
        </button>
        <button v-for="(s, i) in slides" :key="s.id" type="button" class="dot" :class="{ on: i === current }" :aria-label="`Go to slide ${i + 1}`" :aria-current="i === current ? 'true' : null" @click="go(i)"></button>
      </div>
    </section>

    <!-- TRUST STRIP -->
    <section class="promise" aria-label="Why shop with Kokango">
      <ul class="wrap promise-in">
        <li v-for="(p, k) in PROMISES" :key="p.title" v-reveal="{ delay: k * 80 }">
          <span class="pi" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" v-html="ICON[p.icon]"></svg></span>
          <div><strong>{{ p.title }}</strong><span>{{ p.text }}</span></div>
        </li>
      </ul>
    </section>

    <!-- everything between the banner and the story walks past two swaying coconut palms (wide screens only) -->
    <div class="palm-zone">
      <div class="palm-side l" aria-hidden="true"><div class="palm-stick"><PalmTree /></div></div>
      <div class="palm-side r" aria-hidden="true"><div class="palm-stick"><PalmTree flip /></div></div>

    <!-- SHOP BY CATEGORY -->
    <section v-if="categoryTiles.length" class="sec" aria-labelledby="cat-h"><div class="wrap">
      <div v-reveal class="sec-c"><div class="eyebrow">Explore</div><h2 id="cat-h" class="sec-title">Shop by category</h2></div>
      <ul class="cats">
        <li v-for="(c, k) in categoryTiles" :key="c.slug" v-reveal="{ delay: k * 90 }">
          <a class="cat" :href="`/products?category=${encodeURIComponent(c.slug)}`">
            <span class="cat-ring"><img :src="c.photo" alt="" loading="lazy" decoding="async" width="300" height="300"></span>
            <span class="cat-name">{{ c.name }}</span>
          </a>
        </li>
      </ul>
    </div></section>

    <!-- BEST SELLERS -->
    <section class="sec band-white" aria-labelledby="best-h">
      <div class="wrap">
        <div v-reveal class="sec-c"><div class="eyebrow">Loved by our customers</div><h2 id="best-h" class="sec-title">Our best sellers</h2></div>
        <div v-if="tabs.length > 2" class="tabs" role="tablist" aria-label="Filter best sellers">
          <button v-for="t in tabs" :key="t.slug" type="button" role="tab" class="tabb" :class="{ on: tab === t.slug }" :aria-selected="tab === t.slug ? 'true' : 'false'" @click="tab = t.slug">{{ t.name }}</button>
        </div>
        <div v-if="error" class="state card" role="alert">
          <div class="state-t">We could not load the products</div>
          <p>{{ error }}</p>
          <button type="button" class="btn btn-sm" @click="reload">Try again</button>
        </div>
        <div v-else class="pgrid best">
          <template v-if="loading && !hasProducts">
            <div v-for="n in 4" :key="n" class="sk-card" aria-hidden="true"><div class="skeleton sk-img"></div><div class="skeleton sk-line w70"></div><div class="skeleton sk-line w40"></div></div>
          </template>
          <ProductCard v-for="(p, k) in bestSellers" :key="tab + '-' + p.id" class="swap-in" :style="{ animationDelay: (k % 4) * 70 + 'ms' }" :product="p" />
        </div>
        <div class="more"><a class="btn btn-line" :href="tab ? '/products?category=' + encodeURIComponent(tab) : '/products'">{{ tab ? 'View all ' + (tabs.find((t) => t.slug === tab)?.name || '') : 'View all products' }} <span aria-hidden="true">&rarr;</span></a></div>
      </div>
    </section>

    <!-- ABOUT -->
    <section class="sec" aria-labelledby="about-h">
      <div class="wrap about">
        <div v-reveal class="about-img">
          <img :src="ABOUT.image" alt="Women working in a green field" loading="lazy" decoding="async" width="1100" height="900">
          <span class="about-badge"><strong>100%</strong> Konkan made</span>
        </div>
        <div class="about-t">
          <div v-reveal class="eyebrow">{{ ABOUT.kicker }}</div>
          <h2 id="about-h" v-reveal="{ delay: 80 }" class="sec-title">{{ ABOUT.title }}</h2>
          <p v-for="(para, k) in ABOUT.text" :key="k" v-reveal="{ delay: 140 + k * 60 }" class="about-p">{{ para }}</p>
          <ul v-reveal="{ delay: 260 }" class="ticks"><li v-for="pt in ABOUT.points" :key="pt">{{ pt }}</li></ul>
          <a v-reveal="{ delay: 320 }" class="btn" :href="ABOUT.cta.to">{{ ABOUT.cta.label }} <span aria-hidden="true">&rarr;</span></a>
        </div>
      </div>
    </section>

    <!-- FARM TO DOOR -->
    <section class="sec band-white" aria-labelledby="steps-h">
      <div class="wrap">
        <div v-reveal class="sec-c"><div class="eyebrow">How it reaches you</div><h2 id="steps-h" class="sec-title">From Konkan farms to your door</h2></div>
        <ol class="steps">
          <li v-for="(st, k) in STEPS" :key="st.title" v-reveal="{ delay: k * 110 }" class="step">
            <span class="step-i" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" v-html="ICON[st.icon]"></svg><b>{{ k + 1 }}</b></span>
            <h3>{{ st.title }}</h3>
            <p>{{ st.text }}</p>
          </li>
        </ol>
      </div>
    </section>

    <!-- FIGURES -->
    <section class="stats" aria-label="Kokango in numbers">
      <ul class="wrap stats-in">
        <li v-for="(s, k) in STATS" :key="s.label" v-reveal="{ delay: k * 120 }"><strong v-count>{{ s.value }}</strong><span>{{ s.label }}</span></li>
      </ul>
    </section>

    </div>

    <!-- STORY: full-width film -->
    <section id="story" class="story" aria-labelledby="story-h">
      <StoryVideo v-season-scene class="story-film" :src="STORY.video.mp4" :poster="STORY.video.poster" label="Kokango story film" mobile-autoplay>
        <div class="wrap story-over on-dark">
          <div v-reveal class="eyebrow gold">Our story</div>
          <h2 id="story-h" v-reveal="{ split: true }" class="story-title">{{ STORY.title }}</h2>
          <p v-reveal="{ delay: 250 }" class="story-text">{{ STORY.text }}</p>
          <a v-reveal="{ delay: 400 }" class="btn btn-sun story-cta" :href="STORY.cta.to">{{ STORY.cta.label }} <span aria-hidden="true">&rarr;</span></a>
        </div>
      </StoryVideo>
      <div v-season-scene class="story-points">
        <ul class="wrap points">
          <li v-for="(p, k) in STORY.points" :key="p.title" v-reveal="{ delay: k * 110 }">
            <div class="point">
              <div class="point-img"><img :src="p.image" alt="" loading="lazy" decoding="async" width="1000" height="700"></div>
              <div class="point-t"><h3>{{ p.title }}</h3><p>{{ p.text }}</p></div>
            </div>
          </li>
        </ul>
      </div>
    </section>

    <!-- REVIEWS -->
    <section v-if="TESTIMONIALS.length" class="sec band-white" aria-labelledby="rev-h">
      <div class="wrap">
        <div v-reveal class="sec-c"><div class="eyebrow">Happy customers</div><h2 id="rev-h" class="sec-title">What our customers say</h2></div>
        <ul class="reviews">
          <li v-for="(r, k) in TESTIMONIALS" :key="r.name" v-reveal="{ delay: k * 110 }" class="review">
            <div class="stars" role="img" :aria-label="r.rating + ' out of 5 stars'"><span v-for="n in 5" :key="n" :class="{ off: n > r.rating }" aria-hidden="true">&#9733;</span></div>
            <blockquote>&ldquo;{{ r.text }}&rdquo;</blockquote>
            <div class="who"><span class="av" aria-hidden="true">{{ r.name[0] }}</span><div><strong>{{ r.name }}</strong><span>{{ r.city }}</span></div></div>
          </li>
        </ul>
      </div>
    </section>

    <!-- FAQ + HELP -->
    <section class="sec" aria-labelledby="faq-h">
      <div class="wrap faqwrap">
        <div class="faq">
          <div v-reveal class="eyebrow">Questions</div>
          <h2 id="faq-h" v-reveal="{ delay: 60 }" class="sec-title">Good to know</h2>
          <details v-for="(q, k) in faqs" :key="q.q" class="qa" :open="k === 0">
            <summary>{{ q.q }}</summary>
            <p>{{ q.a }}</p>
          </details>
          <a class="link-arrow" href="/faq">All questions <span aria-hidden="true">&rarr;</span></a>
        </div>
        <aside v-reveal="{ delay: 150 }" class="help">
          <span class="help-i" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3a9 9 0 00-7.8 13.5L3 21l4.7-1.2A9 9 0 1012 3zm0 2a7 7 0 11-3.6 13l-.3-.2-2.2.6.6-2.1-.2-.3A7 7 0 0112 5z"/></svg></span>
          <h3>Not sure what to pick?</h3>
          <p>Chat with us on WhatsApp. We will help you choose, share recipes and answer questions about your order.</p>
          <a class="btn btn-sun" :href="WHATSAPP_URL" target="_blank" rel="noopener">Chat on WhatsApp</a>
          <a class="help-l" href="/track-order">Track an order <span aria-hidden="true">&rarr;</span></a>
        </aside>
      </div>
    </section>
  </main>

  <SiteFooter variant="full" flush />
  <WhatsAppFab />
</div>
</template>

<style scoped>
/* ---------- banner slider ---------- */
.hero { position: relative; overflow: hidden; background: var(--forest-deep); color: #fff; }
.track { display: flex; transition: transform .7s var(--ease); will-change: transform; }
.slide { position: relative; isolation: isolate; flex: 0 0 100%; min-width: 0; height: clamp(440px, 46vw, 600px); display: flex; align-items: center; }
.slide-bg { position: absolute; inset: 0; z-index: -2; }
.slide-bg img { width: 100%; height: 100%; object-fit: cover; transform: scale(1.08); transition: transform 7s ease-out; }
.slide.live .slide-bg img { transform: scale(1); }
.stext > * { opacity: 0; transform: translateY(22px); transition: opacity .7s var(--ease), transform .7s var(--ease); }
.slide.live .stext > * { opacity: 1; transform: none; }
.slide.live .stext > :nth-child(2) { transition-delay: .12s; }
.slide.live .stext > :nth-child(3) { transition-delay: .24s; }
.slide.live .stext > :nth-child(4) { transition-delay: .36s; }
.packs img { transition: transform .9s var(--ease), opacity .9s var(--ease); }
.slide:not(.live) .packs img { opacity: 0; transform: translateY(40px) !important; }
.slide::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(90deg, rgba(6, 26, 18, .82) 0%, rgba(6, 26, 18, .55) 45%, rgba(6, 26, 18, .1) 100%); }
.slide-in { width: 100%; max-width: var(--wrap); margin: 0 auto; padding: 0 var(--gutter); display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, .9fr); align-items: center; gap: 32px; }
.stext { max-width: 560px; }
.kicker { display: inline-block; padding: 6px 14px; border-radius: 999px; font-size: 12px; font-weight: 800; letter-spacing: .8px; text-transform: uppercase; background: var(--mango); color: var(--ink); }
.stitle { margin: 16px 0 0; font-size: clamp(32px, 4.6vw, 58px); line-height: 1.05; font-weight: 800; letter-spacing: -1.4px; text-wrap: balance; }
.sp { margin: 14px 0 0; font-size: 17px; line-height: 1.6; color: #E6EFE8; max-width: 460px; }
.scta { margin-top: 24px; min-height: 50px; padding: 0 30px; font-size: 16px; }
.packs { display: flex; align-items: flex-end; justify-content: center; height: 380px; }
.packs img { height: 300px; width: auto; object-fit: contain; border-radius: 16px; background: #fff; box-shadow: 0 24px 40px rgba(0, 0, 0, .4); }
.packs .n3.p0 { height: 240px; transform: rotate(-8deg) translateX(30px); }
.packs .n3.p1 { height: 320px; position: relative; z-index: 1; }
.packs .n3.p2 { height: 240px; transform: rotate(8deg) translateX(-30px); }
.packs .n2.p0 { transform: rotate(-6deg) translateX(14px); }
.packs .n2.p1 { transform: rotate(6deg) translateX(-14px); }

.harrow { position: absolute; top: 50%; z-index: 2; width: 46px; height: 46px; margin-top: -23px; border-radius: 50%; border: 0; background: rgba(255, 255, 255, .16); color: #fff; display: inline-flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); transition: background .2s; }
.harrow:hover { background: rgba(255, 255, 255, .3); }
.harrow.l { left: 16px; } .harrow.r { right: 16px; }
.hctrl { position: absolute; left: 50%; bottom: 14px; transform: translateX(-50%); z-index: 2; display: flex; align-items: center; gap: 2px; padding: 2px 8px; border-radius: 999px; background: rgba(6, 26, 18, .45); }
.hplay { width: 32px; height: 32px; border: 0; background: none; color: #fff; display: inline-flex; align-items: center; justify-content: center; }
.dot { position: relative; width: 26px; height: 32px; padding: 0; border: 0; background: none; }
.dot::after { content: ''; position: absolute; left: 50%; top: 50%; width: 8px; height: 8px; margin: -4px 0 0 -4px; border-radius: 6px; background: rgba(255, 255, 255, .6); transition: width .3s var(--ease), margin .3s var(--ease), background .3s; }
.dot.on::after { width: 22px; margin-left: -11px; background: var(--mango); }

/* ---------- sections ---------- */
.sec { padding: 64px 0; }
.band-white { background: var(--surface); }
.gold { color: var(--mango); }
.sec-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
.sec-row .sec-title { margin: 0; }
.more { text-align: center; margin-top: 30px; }
.state { padding: 36px 24px; text-align: center; }
.state-t { font-size: 18px; font-weight: 800; }
.state p { margin: 8px 0 16px; font-size: 14px; color: var(--muted); }
.sk-card { background: var(--surface); border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; gap: 10px; padding-bottom: 16px; }
.sk-img { aspect-ratio: 1; border-radius: 0; }
.sk-line { height: 14px; margin: 0 14px; }
.w70 { width: 70%; } .w40 { width: 40%; }

/* categories: round photos */
.cats { list-style: none; margin: 0 auto; padding: 0; display: flex; justify-content: center; gap: 48px; flex-wrap: wrap; }
.cat { display: flex; flex-direction: column; align-items: center; gap: 14px; color: var(--ink); }
.cat-ring { width: 170px; height: 170px; border-radius: 50%; padding: 6px; background: linear-gradient(135deg, var(--mango), var(--leaf)); transition: transform .3s var(--ease); }
.cat-ring img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 4px solid var(--bg); display: block; }
.cat:hover .cat-ring { transform: translateY(-6px) scale(1.04); box-shadow: 0 14px 30px rgba(16, 61, 44, .2); }
.cat-ring { transition: transform .35s var(--ease), box-shadow .35s; }
.cat-name { font-size: 17px; font-weight: 800; letter-spacing: -.2px; }
.cat:hover { color: var(--forest); }

/* figures band */
.stats { background: var(--terra); color: #FBEFE6; }
.stats-in { list-style: none; margin: 0 auto; padding-top: 44px; padding-bottom: 44px; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; text-align: center; }
.stats strong { display: block; font-size: clamp(30px, 4vw, 48px); font-weight: 800; letter-spacing: -1px; color: #fff; line-height: 1.1; }
.stats span { display: block; margin-top: 6px; font-size: 15px; font-weight: 600; }

/* story: full-width film band, then the four points */
.story { background: var(--forest); color: #fff; }
.story-film { width: 100%; height: clamp(520px, 82vh, 760px); }
.story-over { height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: flex-start; padding-top: 40px; padding-bottom: 80px; }
.story-title { margin: 12px 0 0; max-width: 760px; font-size: clamp(34px, 5.4vw, 68px); font-weight: 800; line-height: 1.04; letter-spacing: -1.6px; text-wrap: balance; text-shadow: 0 4px 30px rgba(0, 0, 0, .35); }
.story-text { margin: 18px 0 0; max-width: 560px; font-size: 18px; line-height: 1.65; color: #E9F1EA; }
.story-cta { margin-top: 28px; min-height: 52px; padding: 0 30px; font-size: 16px; }
/* film play/sound buttons: bottom-left, in line with the text (the WhatsApp button lives bottom-right) */
.story-film :deep(.sv-ctrl) { right: auto; left: max(var(--gutter), calc((100% - var(--wrap)) / 2 + var(--gutter))); bottom: 28px; }
.story-points { background: radial-gradient(circle at 90% 0%, rgba(55, 146, 55, .28), transparent 45%), var(--forest); padding: 56px 0 72px; }
.points { list-style: none; margin: 0 auto; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 20px; }
.points > li { min-width: 0; }
.story-points .points { position: relative; z-index: 2; }   /* seasonal effects pass behind the cards */
.point { height: 100%; background: rgba(255, 255, 255, .06); border: 1px solid rgba(255, 255, 255, .12); border-radius: 18px; overflow: hidden; transition: transform .35s var(--ease), background .35s, border-color .35s; }
.point:hover { transform: translateY(-6px); background: rgba(255, 255, 255, .1); border-color: rgba(254, 188, 4, .55); }
.point-img { overflow: hidden; }
.point img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; display: block; transition: transform .8s var(--ease); }
.point:hover img { transform: scale(1.08); }
.point-t { padding: 16px 18px 20px; }
.point h3 { margin: 0; font-size: 18px; font-weight: 800; color: var(--mango); }
.point p { margin: 6px 0 0; font-size: 14px; line-height: 1.55; color: #DCE9DF; }

/* ---------- trust strip ---------- */
.promise { background: var(--surface); border-bottom: 1px solid var(--line); }
.promise-in { list-style: none; margin: 0 auto; padding-top: 20px; padding-bottom: 20px; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px 24px; }
.promise-in li { display: flex; align-items: center; gap: 12px; min-width: 0; }
.pi { flex: none; width: 46px; height: 46px; border-radius: 50%; background: var(--tint); color: var(--forest); display: inline-flex; align-items: center; justify-content: center; transition: transform .3s var(--ease), background .3s; }
.promise-in li:hover .pi { transform: rotate(-8deg) scale(1.08); background: var(--sun); }
.promise-in strong { display: block; font-size: 14px; }
.promise-in span:not(.pi) { display: block; font-size: 12.5px; color: var(--muted); }

/* ---------- best seller tabs ---------- */
.tabs { display: flex; justify-content: center; flex-wrap: wrap; gap: 8px; margin: -6px 0 26px; }
.tabb { min-height: 42px; padding: 0 20px; border-radius: 999px; border: 1.5px solid var(--line); background: #fff; color: var(--ink); font-size: 14px; font-weight: 600; transition: background .2s, border-color .2s, color .2s, transform .2s; }
.tabb:hover { border-color: var(--forest); transform: translateY(-1px); }
.tabb.on { background: var(--forest); border-color: var(--forest); color: #fff; }
.swap-in { animation: swapin .5s var(--ease) both; }
@keyframes swapin { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }

/* ---------- about ---------- */
.about { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 56px; align-items: center; }
.about-img { position: relative; }
.about-img img { width: 100%; aspect-ratio: 11 / 9; object-fit: cover; border-radius: 22px; display: block; box-shadow: 0 24px 50px rgba(16, 61, 44, .18); }
.about-img::before { content: ''; position: absolute; inset: 18px -18px -18px 18px; z-index: -1; border-radius: 22px; background: var(--sun); }
.about-badge { position: absolute; left: -16px; bottom: 26px; padding: 14px 18px; border-radius: 16px; background: var(--forest); color: #fff; font-size: 13px; font-weight: 600; box-shadow: 0 14px 30px rgba(16, 61, 44, .3); animation: bob 4s ease-in-out infinite; }
.about-badge strong { display: block; font-size: 24px; color: var(--mango); line-height: 1.1; }
@keyframes bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
.about-t .sec-title { margin: 6px 0 0; }
.about-p { margin: 16px 0 0; font-size: 16px; line-height: 1.75; color: var(--muted); }
.ticks { list-style: none; margin: 18px 0 26px; padding: 0; display: grid; gap: 10px; }
.ticks li { position: relative; padding-left: 32px; font-weight: 600; }
.ticks li::before { content: ''; position: absolute; left: 0; top: 1px; width: 22px; height: 22px; border-radius: 50%; background: var(--tint) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23103D2C' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 12.5l4 4 8-9'/%3E%3C/svg%3E") center / 13px no-repeat; }

/* ---------- farm to door steps ---------- */
.steps { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 24px; counter-reset: s; }
.step { position: relative; text-align: center; padding: 8px 10px 0; }
.step:not(:last-child)::after { content: ''; position: absolute; top: 44px; left: calc(50% + 52px); right: calc(-50% + 52px); border-top: 2px dashed #CFD9C8; }
.step-i { position: relative; width: 88px; height: 88px; margin: 0 auto 16px; border-radius: 50%; background: var(--tint); color: var(--forest); display: flex; align-items: center; justify-content: center; transition: transform .35s var(--ease), background .35s; }
.step:hover .step-i { transform: translateY(-6px); background: var(--sun); }
.step-i b { position: absolute; top: -2px; right: -2px; width: 28px; height: 28px; border-radius: 50%; background: var(--mango); color: var(--ink); font-size: 13px; display: flex; align-items: center; justify-content: center; border: 3px solid var(--surface); }
.step h3 { margin: 0; font-size: 18px; font-weight: 800; }
.step p { margin: 8px 0 0; font-size: 14px; line-height: 1.6; color: var(--muted); }

/* ---------- reviews ---------- */
.reviews { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; }
.review { display: flex; flex-direction: column; padding: 26px; border-radius: 20px; background: var(--bg); border: 1px solid var(--line); transition: transform .3s var(--ease), box-shadow .3s; }
.review:hover { transform: translateY(-5px); box-shadow: 0 18px 40px rgba(16, 61, 44, .12); }
.stars { color: #F2A900; font-size: 18px; letter-spacing: 2px; }
.stars .off { color: #D9D4BF; }
.review blockquote { flex: 1; margin: 12px 0 18px; font-size: 15.5px; line-height: 1.7; color: var(--ink); }
.who { display: flex; align-items: center; gap: 12px; }
.av { width: 42px; height: 42px; border-radius: 50%; background: var(--forest); color: var(--mango); font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
.who strong { display: block; font-size: 15px; }
.who span:not(.av) { display: block; font-size: 13px; color: var(--muted); }

/* ---------- faq + help ---------- */
.faqwrap { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr); gap: 48px; align-items: start; }
.faq .sec-title { margin: 6px 0 20px; }
.qa { border-bottom: 1px solid var(--line); }
.qa summary { list-style: none; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 16px; min-height: 56px; padding: 6px 0; font-size: 16px; font-weight: 700; }
.qa summary::-webkit-details-marker { display: none; }
.qa summary::after { content: '+'; flex: none; width: 30px; height: 30px; border-radius: 50%; background: var(--tint); color: var(--forest); font-size: 20px; display: inline-flex; align-items: center; justify-content: center; transition: transform .3s var(--ease); }
.qa[open] summary::after { transform: rotate(45deg); }
.qa p { margin: 0 0 16px; font-size: 15px; line-height: 1.7; color: var(--muted); animation: swapin .35s var(--ease); }
.faq .link-arrow { margin-top: 12px; }
.help { position: sticky; top: 140px; padding: 30px; border-radius: 22px; background: radial-gradient(circle at 100% 0%, rgba(254, 188, 4, .25), transparent 50%), var(--forest); color: #fff; }
.help-i { width: 56px; height: 56px; border-radius: 50%; background: #25D366; color: #fff; display: inline-flex; align-items: center; justify-content: center; animation: bob 3.5s ease-in-out infinite; }
.help h3 { margin: 16px 0 0; font-size: 22px; font-weight: 800; }
.help p { margin: 10px 0 20px; font-size: 15px; line-height: 1.65; color: #D3E2D6; }
.help .btn { width: 100%; }
.help-l { display: inline-flex; gap: 6px; margin-top: 14px; font-size: 14px; font-weight: 700; color: #fff; min-height: 32px; align-items: center; }
.help-l:hover { color: var(--mango); }

/* ---------- coconut palms beside the content ---------- */
.palm-zone { position: relative; overflow-x: clip; }   /* the trees may lean past the screen edge without causing sideways scrolling (clip keeps them sticky) */
.palm-side { display: none; }
/* only where the margin beside the 1280px content is wide enough to hold a tree without covering products */
@media (min-width: 1500px) {
  .palm-side { display: block; position: absolute; z-index: 3; top: 0; bottom: 0; width: min(calc((100vw - var(--wrap)) / 2 * 1.15 + 20px), 330px, 40vh); pointer-events: none; }
  .palm-side.l { left: -18px; }
  .palm-side.r { right: -18px; }
  /* the tree stands at the bottom of the screen and stays there while this part of the page scrolls by */
  .palm-stick { position: sticky; top: calc(100vh - min(calc((100vw - var(--wrap)) / 2 * 1.15 + 20px), 330px, 40vh) * 2); }
}

/* ---------- responsive ---------- */
@media (max-width: 900px) {
  .promise-in { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .about { grid-template-columns: 1fr; gap: 40px; }
  .steps { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 32px; }
  .step::after { display: none; }
  .reviews { grid-template-columns: 1fr; gap: 14px; }
  .faqwrap { grid-template-columns: 1fr; gap: 28px; }
  .help { position: static; }
  .slide-in { grid-template-columns: 1fr; }
  .packs { display: none; }
  .points { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
}
@media (max-width: 700px) {
  .promise-in { gap: 12px; padding-top: 14px; padding-bottom: 14px; }
  .promise-in li { gap: 8px; }
  .pi { width: 38px; height: 38px; }
  .pi svg { width: 20px; height: 20px; }
  .promise-in strong { font-size: 12.5px; }
  .promise-in span:not(.pi) { font-size: 11.5px; }
  .tabs { flex-wrap: nowrap; justify-content: flex-start; overflow-x: auto; scrollbar-width: none; margin: -4px calc(var(--gutter) * -1) 18px; padding: 2px var(--gutter); }
  .tabs::-webkit-scrollbar { display: none; }
  .tabb { flex: none; min-height: 38px; padding: 0 16px; font-size: 13px; }
  .about-img::before { inset: 10px -10px -10px 10px; }
  .about-badge { left: 10px; bottom: 14px; padding: 10px 14px; }
  .about-badge strong { font-size: 20px; }
  .about-p { font-size: 15px; }
  .steps { gap: 24px 12px; }
  .step-i { width: 72px; height: 72px; }
  .step h3 { font-size: 15px; }
  .step p { font-size: 13px; }
  .review { padding: 20px; }
  .help { padding: 24px; }
  .slide { height: min(78vh, 520px); align-items: flex-end; }
  .slide::after { background: linear-gradient(0deg, rgba(6, 26, 18, .9) 0%, rgba(6, 26, 18, .55) 45%, rgba(6, 26, 18, .05) 80%); }
  .slide-in { padding-bottom: 64px; }
  .kicker { font-size: 11px; padding: 5px 12px; }
  .stitle { font-size: clamp(28px, 8.4vw, 36px); letter-spacing: -.8px; }
  .sp { font-size: 15px; margin-top: 10px; }
  .scta { margin-top: 18px; width: 100%; min-height: 48px; }
  .harrow { display: none; }
  .sec { padding: 40px 0; }
  .cats { display: grid; grid-template-columns: repeat(auto-fit, minmax(96px, 1fr)); gap: 16px 8px; }
  .cats > li { min-width: 0; }
  .cat { gap: 8px; }
  .cat-ring { width: min(100px, 26vw); height: min(100px, 26vw); padding: 4px; }
  .cat-ring img { border-width: 3px; }
  .cat-name { font-size: 13px; text-align: center; }
  .best > :nth-child(n + 7) { display: none; }   /* phones: 6 best sellers, then "View all" */
  .more .btn { width: 100%; }
  .sec-row { margin-bottom: 14px; }
  .stats-in { gap: 8px; padding-top: 26px; padding-bottom: 26px; }
  .stats strong { font-size: 20px; letter-spacing: -.4px; }
  .stats span { font-size: 11.5px; line-height: 1.35; }
  .story-film { height: min(86vh, 620px); }
  .story-over { justify-content: flex-end; padding-bottom: 84px; }
  .story-text { font-size: 15px; }
  .story-cta { width: 100%; }
  .story-points { padding: 28px 0 44px; }
  .points { gap: 10px; }
  .point { border-radius: 14px; }
  .point-t { padding: 10px 12px 14px; }
  .point h3 { font-size: 15px; }
  .point p { font-size: 12.5px; }
}
@media (prefers-reduced-motion: reduce) {
  .stext > *, .slide-bg img { opacity: 1 !important; transform: none !important; }
}
</style>
