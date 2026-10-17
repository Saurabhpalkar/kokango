<script setup>
// "From the Heart of Konkan to Your Home": cinematic film + five short chapters on the homepage.
// Copy is deliberately about the Konkan region and Kokango's existing, published promises
// (small batches, simple ingredients, honest packing, tracked shipping); no people, farms or
// partnerships are claimed. Real photos/video are configured in content/story.js.
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import StoryVideo from '@/components/StoryVideo.vue'
import ProductCard from '@/components/ProductCard.vue'
import { STORY } from '@/content/story'
import { getAboutData } from '@/content/about'
import { vReveal } from '@/composables/useReveal'

const props = defineProps({
  featured: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const words = (s) => s.split(' ')
const hideImg = (e) => { e.target.style.display = 'none' }
const headline = 'Some flavours are more than food. They are memories.'
const values = getAboutData().values
const steps = computed(() => [
  { t: values[1].title, d: values[1].text, p: props.featured[0] },
  { t: values[0].title, d: values[0].text, p: props.featured[1] },
  { t: 'Sealed and shipped', d: 'Resealable pouches protect aroma and taste, with tracked shipping across India.', p: props.featured[2] },
])
const picks = computed(() => props.featured.slice(0, 4))

const CHAPTERS = [
  { id: 'chapter-land', label: 'The Land' },
  { id: 'chapter-people', label: 'The People' },
  { id: 'chapter-table', label: 'Nature to Table' },
  { id: 'chapter-memories', label: 'Memories' },
  { id: 'chapter-discover', label: 'Discover' },
]
const active = ref('')
const inJourney = ref(false)
const journey = ref(null)
let io = null
let io2 = null
onMounted(() => {
  if (!('IntersectionObserver' in window)) return
  io = new IntersectionObserver((es) => {
    es.forEach((e) => { if (e.isIntersecting) active.value = e.target.id })
  }, { rootMargin: '-40% 0px -50% 0px' })
  CHAPTERS.forEach((c) => { const el = document.getElementById(c.id); if (el) io.observe(el) })
  io2 = new IntersectionObserver(([e]) => { inJourney.value = e.isIntersecting }, { rootMargin: '-20% 0px -20% 0px' })
  if (journey.value) io2.observe(journey.value)
})
onBeforeUnmount(() => { io?.disconnect(); io2?.disconnect() })

function jump(id) {
  const el = document.getElementById(id)
  if (!el) return
  const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  el.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' })
}
</script>

<template>
  <div class="journey" ref="journey">
    <!-- FILM -->
    <StoryVideo class="film on-dark" :src="STORY.video.mp4" :src-webm="STORY.video.webm" :poster="STORY.video.poster" label="Kokango brand film">
      <div class="film-in wrap">
        <div class="film-text">
          <div class="eyebrow gold" v-reveal>Rooted in Konkan</div>
          <h2 class="film-h serif" v-reveal="{ split: true }" aria-label="Some flavours are more than food. They are memories.">
            <span v-for="(w, i) in words(headline)" :key="i" class="w" aria-hidden="true" :style="`--i:${i}`">{{ w }}&nbsp;</span>
          </h2>
          <p class="film-p" v-reveal="{ delay: 300 }">From the warmth of our coastal villages to the flavours that bring families together, discover the stories behind the goodness of Konkan.</p>
          <div class="film-cta" v-reveal="{ delay: 450 }">
            <a class="btn btn-sun" href="#chapter-land" @click.prevent="jump('chapter-land')">Discover our story</a>
            <a class="btn btn-line" href="/products">Explore our products</a>
          </div>
        </div>
      </div>
    </StoryVideo>

    <!-- CHAPTER 1: THE LAND -->
    <section id="chapter-land" class="ch land on-dark" aria-labelledby="land-h">
      <div class="land-art" aria-hidden="true">
        <!-- abstract artwork stays underneath as a fallback if the photo is missing or fails to load -->
        <div class="sky"></div><div class="sun par-slow"></div>
        <div class="hill h1 par-slow"></div><div class="hill h2 par"></div><div class="sea"></div>
        <img v-if="STORY.land.src" class="land-img par" :src="STORY.land.src" alt="" loading="lazy" decoding="async" @error="hideImg">
      </div>
      <div class="land-shade" aria-hidden="true"></div>
      <div class="wrap land-in">
        <div class="chap-no" v-reveal>Chapter one</div>
        <h2 id="land-h" class="serif big" v-reveal="{ delay: 80 }">Where the hills meet the sea</h2>
        <p class="lede" v-reveal="{ delay: 180 }">The Konkan coast is red earth, coconut palms, monsoon green and a restless sea. Its kitchens grew out of that land, and so did the flavours we grew up loving.</p>
      </div>
    </section>

    <!-- CHAPTER 2: THE PEOPLE -->
    <section id="chapter-people" class="ch people" aria-labelledby="people-h">
      <div class="wrap people-in">
        <figure class="people-fig" v-reveal>
          <div class="people-art" aria-hidden="true"><span class="pa a"></span><span class="pa b"></span><span class="pa c"></span></div>
          <img v-if="STORY.people.src" class="people-img" :src="STORY.people.src" :alt="STORY.people.alt" loading="lazy" decoding="async" @error="hideImg">
        </figure>
        <div class="people-text">
          <div class="chap-no dark" v-reveal>Chapter two</div>
          <h2 id="people-h" class="serif mid" v-reveal="{ delay: 80 }">Made the patient way</h2>
          <p class="lede ink" v-reveal="{ delay: 160 }">Konkan food has always belonged to hands and habit: recipes passed along, ingredients kept simple, nothing rushed. Kokango carries that spirit into every small batch.</p>
          <blockquote class="pull" v-reveal="{ delay: 240 }">Good food is rarely in a hurry.</blockquote>
          <ul class="vals" v-reveal="{ delay: 320 }">
            <li v-for="v in values.slice(0, 3)" :key="v.n"><b>{{ v.title }}</b><span>{{ v.text }}</span></li>
          </ul>
        </div>
      </div>
    </section>

    <!-- CHAPTER 3: NATURE TO YOUR TABLE -->
    <section id="chapter-table" class="ch table" aria-labelledby="table-h">
      <div class="wrap">
        <div class="chap-no dark" v-reveal>Chapter three</div>
        <h2 id="table-h" class="serif mid" v-reveal="{ delay: 80 }">From nature to your table</h2>
        <ol class="journey-steps">
          <li v-for="(s, i) in steps" :key="s.t" class="jstep" v-reveal="{ delay: i * 140 }">
            <a v-if="s.p" class="orb" :href="`/product/${s.p.slug}`" :aria-label="`View ${s.p.name}`">
              <img :src="s.p.image_url" alt="" loading="lazy" decoding="async" width="220" height="220">
            </a>
            <span v-else class="orb orb-empty" aria-hidden="true"></span>
            <span class="jn" aria-hidden="true">{{ i + 1 }}</span>
            <h3>{{ s.t }}</h3>
            <p>{{ s.d }}</p>
          </li>
        </ol>
      </div>
    </section>

    <!-- CHAPTER 4: MEMORIES -->
    <section id="chapter-memories" class="ch mem on-dark" aria-labelledby="mem-h">
      <img v-if="STORY.memories.src" class="mem-img" :src="STORY.memories.src" alt="" loading="lazy" decoding="async" @error="hideImg">
      <div class="mem-glow" aria-hidden="true"></div>
      <div class="wrap mem-in">
        <div class="chap-no" v-reveal>Chapter four</div>
        <h2 id="mem-h" class="serif quote" v-reveal="{ split: true }" aria-label="The taste of a festival afternoon. The smell of home the moment you open the pack.">
          <span v-for="(w, i) in words('The taste of a festival afternoon. The smell of home the moment you open the pack.')" :key="i" class="w" aria-hidden="true" :style="`--i:${i}`">{{ w }}&nbsp;</span>
        </h2>
        <ul class="moments" v-reveal="{ delay: 200 }">
          <li>Festival days</li><li>Family tables</li><li>Rainy evenings</li><li>Back home</li>
        </ul>
      </div>
    </section>

    <!-- CHAPTER 5: DISCOVER -->
    <section id="chapter-discover" class="ch disc" aria-labelledby="disc-h">
      <div class="wrap">
        <div class="disc-head">
          <div>
            <div class="chap-no dark" v-reveal>Chapter five</div>
            <h2 id="disc-h" class="serif mid" v-reveal="{ delay: 80 }">Bring a little Konkan home</h2>
          </div>
          <div class="disc-cta" v-reveal="{ delay: 160 }">
            <a class="btn" href="/products">Shop all products</a>
            <a class="btn btn-line" href="/about">About Kokango</a>
          </div>
        </div>
        <div v-if="loading" class="pgrid" aria-hidden="true"><div v-for="n in 4" :key="n" class="skeleton" style="aspect-ratio: 3 / 4"></div></div>
        <div v-else-if="picks.length" class="pgrid">
          <ProductCard v-for="p in picks" :key="p.id" :product="p" />
        </div>
      </div>
    </section>

    <!-- chapter navigation (desktop) -->
    <nav class="chnav" :class="{ show: inJourney }" aria-label="Story chapters">
      <button v-for="c in CHAPTERS" :key="c.id" type="button" :class="{ on: active === c.id }" :aria-current="active === c.id ? 'true' : undefined" @click="jump(c.id)">
        <span class="lbl">{{ c.label }}</span><span class="dot" aria-hidden="true"></span>
      </button>
    </nav>
  </div>
</template>

<style scoped>
.journey { margin-top: 88px; position: relative; }
.serif { font-family: Georgia, 'Times New Roman', 'Noto Serif Devanagari', serif; font-weight: 700; letter-spacing: -.02em; }
.gold { color: var(--mango); }
.ch { scroll-margin-top: 72px; position: relative; }
.chap-no { font-size: 12px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--mango); margin-bottom: 14px; }
.chap-no.dark { color: var(--leaf); }
.lede { margin: 0; font-size: clamp(16px, 1.6vw, 19px); line-height: 1.8; color: #E3EEE6; max-width: 540px; }
.lede.ink { color: var(--muted); }
.big { margin: 0 0 20px; font-size: clamp(36px, 5.4vw, 68px); line-height: 1.04; max-width: 14ch; }
.mid { margin: 0 0 20px; font-size: clamp(30px, 4.4vw, 54px); line-height: 1.06; }

/* film */
.film { min-height: clamp(560px, 88vh, 920px); min-height: clamp(560px, 88svh, 920px); display: flex; }
.film :deep(.sv-content) { display: flex; align-items: center; width: 100%; }
.film-in { width: 100%; padding-top: 80px; padding-bottom: 110px; }
.film-text { max-width: 760px; }
.film-h { margin: 12px 0 20px; font-size: clamp(36px, 5.2vw, 72px); line-height: 1.04; }
.film-p { margin: 0 0 30px; max-width: 520px; font-size: clamp(16px, 1.6vw, 19px); line-height: 1.75; color: #E3EEE6; }
.film-cta { display: flex; flex-wrap: wrap; gap: 14px; }

/* chapter 1 */
.land { color: #fff; min-height: clamp(520px, 80vh, 760px); display: flex; align-items: flex-end; overflow: hidden; background: #0A2A1E; }
.land::before { content: ''; position: absolute; inset: 0 0 auto; height: 160px; z-index: 2; background: linear-gradient(#08241A, transparent); pointer-events: none; }
.land-art { position: absolute; inset: -8% 0; }
.land-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.sky { position: absolute; inset: 0; background: linear-gradient(180deg, #1B5E73 0%, #E9A950 52%, #F7D58A 70%, #12456B 70.2%, #0F3A5C 100%); }
.sun { position: absolute; left: 62%; top: 34%; width: clamp(120px, 16vw, 220px); aspect-ratio: 1; border-radius: 50%; background: radial-gradient(circle, #FFF0B8 0 45%, rgba(255, 224, 140, .55) 46% 70%, transparent 71%); }
.hill { position: absolute; border-radius: 50% 50% 0 0 / 100% 100% 0 0; }
.h1 { left: -12%; bottom: 29%; width: 70%; height: 30%; background: #1E6B3A; opacity: .9; }
.h2 { right: -16%; bottom: 29%; width: 80%; height: 24%; background: #103D2C; }
.sea { position: absolute; inset: auto 0 0; height: 30%; background: repeating-linear-gradient(180deg, rgba(255, 255, 255, .06) 0 2px, transparent 2px 18px), linear-gradient(#12456B, #0B2E48); }
.land-shade { position: absolute; inset: 0; background: linear-gradient(0deg, rgba(6, 28, 20, .85), rgba(6, 28, 20, .15) 60%, rgba(6, 28, 20, .35)); }
.land-in { position: relative; padding-top: 100px; padding-bottom: 72px; width: 100%; }

/* chapter 2 */
.people { padding: 110px 0; background: var(--bg); }
.people-in { display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1fr); gap: clamp(32px, 6vw, 96px); align-items: center; }
.people-fig { margin: 0; aspect-ratio: 4 / 5; border-radius: 220px 220px var(--r-lg) var(--r-lg); overflow: hidden; background: var(--tint); }
.people-fig { position: relative; }
.people-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
.people-art { position: absolute; inset: 0; background: linear-gradient(180deg, #F7D58A, #F0B24A 60%, #8A5A30); overflow: hidden; }
.pa { position: absolute; border-radius: 50%; }
.pa.a { width: 70%; aspect-ratio: 1; left: 15%; top: 18%; background: radial-gradient(circle at 50% 40%, #FFF4CC, #FEBC04 70%); opacity: .85; }
.pa.b { width: 120%; aspect-ratio: 1; left: -10%; bottom: -70%; background: #5B3A22; }
.pa.c { width: 90%; aspect-ratio: 1; left: 20%; bottom: -62%; background: #103D2C; }
.pull { margin: 28px 0; padding-left: 20px; border-left: 4px solid var(--mango); font-family: Georgia, serif; font-size: clamp(22px, 2.6vw, 30px); line-height: 1.3; font-style: italic; color: var(--forest); }
.vals { list-style: none; margin: 0; padding: 0; display: grid; gap: 14px; }
.vals li { display: grid; gap: 2px; padding-top: 14px; border-top: 1px solid var(--line); }
.vals b { font-size: 15px; }
.vals span { font-size: 14px; line-height: 1.6; color: var(--muted); }

/* chapter 3 */
.table { padding: 100px 0; background: var(--tint); }
.journey-steps { list-style: none; margin: 40px 0 0; padding: 0; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 40px; counter-reset: s; }
.jstep { position: relative; text-align: center; }
.jstep:not(:last-child)::after { content: ''; position: absolute; top: 90px; left: calc(50% + 120px); right: calc(-50% + 120px); border-top: 2px dashed rgba(16, 61, 44, .3); }
.orb { display: flex; align-items: center; justify-content: center; width: min(210px, 100%); aspect-ratio: 1; margin: 0 auto 18px; border-radius: 50%; background: radial-gradient(circle at 35% 30%, #fff, #F7F3E2); border: 1px solid var(--line); box-shadow: var(--shadow-md); overflow: hidden; transition: transform var(--dur) var(--ease); }
.orb:hover { transform: translateY(-4px) scale(1.02); }
.orb img { width: 78%; height: 78%; object-fit: contain; }
.orb-empty { background: var(--sun); }
.jn { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: var(--forest); color: #fff; font-size: 13px; font-weight: 800; margin-bottom: 10px; }
.jstep h3 { margin: 0 0 6px; font-size: 20px; font-weight: 800; }
.jstep p { margin: 0 auto; max-width: 300px; font-size: 14.5px; line-height: 1.7; color: var(--muted); }

/* chapter 4 */
.mem { color: #fff; padding: 130px 0; overflow: hidden; background: linear-gradient(160deg, #3D2616 0%, #5B3A22 45%, #103D2C 100%); }
.mem-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .5; }
.mem::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(40, 24, 12, .55), rgba(16, 61, 44, .6)); pointer-events: none; }
.mem-in { z-index: 1; }
.mem-glow { position: absolute; width: 60vw; aspect-ratio: 1; right: -15vw; top: -25vw; border-radius: 50%; background: radial-gradient(circle, rgba(254, 188, 4, .35), transparent 65%); }
.mem-in { position: relative; text-align: center; }
.quote { margin: 0 auto 36px; max-width: 18ch; font-size: clamp(32px, 5.4vw, 68px); line-height: 1.08; font-style: italic; font-weight: 400; }
.moments { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }
.moments li { padding: 10px 20px; border: 1.5px solid rgba(255, 255, 255, .45); border-radius: 999px; font-size: 14px; font-weight: 600; }

/* chapter 5 */
.disc { padding: 100px 0 40px; background: var(--bg); }
.disc-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 36px; }
.disc-head .mid { margin-bottom: 0; }
.disc-cta { display: flex; flex-wrap: wrap; gap: 12px; }

/* chapter navigation */
.chnav { position: fixed; right: 18px; top: 50%; transform: translateY(-50%); z-index: 40; display: none; flex-direction: column; gap: 4px; opacity: 0; pointer-events: none; transition: opacity .3s; }
.chnav.show { opacity: 1; pointer-events: auto; }
.chnav button { display: flex; align-items: center; justify-content: flex-end; gap: 10px; min-height: 28px; padding: 0 2px; background: none; border: 0; color: var(--ink); }
.chnav .lbl { padding: 4px 10px; border-radius: 999px; background: var(--ink); color: #fff; font-size: 12px; font-weight: 700; white-space: nowrap; opacity: 0; transform: translateX(6px); transition: opacity .2s, transform .2s; }
.chnav button:hover .lbl, .chnav button:focus-visible .lbl, .chnav button.on .lbl { opacity: 1; transform: none; }
.chnav .dot { width: 12px; height: 12px; border-radius: 50%; border: 2px solid var(--mango); background: var(--forest); box-shadow: 0 0 0 2px rgba(255, 255, 255, .8); transition: transform .2s, background .2s; }
.chnav button.on .dot { background: var(--mango); transform: scale(1.35); }
@media (min-width: 1100px) { .chnav { display: flex; } }

/* gentle parallax: scroll-driven where supported, static elsewhere */
@keyframes par { from { transform: translateY(-5%); } to { transform: translateY(5%); } }
@keyframes par-slow { from { transform: translateY(-2.5%); } to { transform: translateY(2.5%); } }
@supports (animation-timeline: view()) {
  .par { animation: par linear both; animation-timeline: view(); }
  .par-slow { animation: par-slow linear both; animation-timeline: view(); }
}

@media (max-width: 900px) {
  .journey { margin-top: 56px; }
  .people { padding: 72px 0; }
  .people-in { grid-template-columns: 1fr; }
  .people-fig { max-width: 420px; aspect-ratio: 1; border-radius: 200px 200px var(--r-lg) var(--r-lg); }
  .table, .disc { padding-top: 72px; }
  .journey-steps { grid-template-columns: 1fr; gap: 36px; }
  .jstep:not(:last-child)::after { display: none; }
  .mem { padding: 88px 0; }
}
@media (max-width: 600px) {
  .film { min-height: 78vh; min-height: 78svh; }
  .film-in { padding-bottom: 96px; }
  .film-cta .btn { flex: 1 1 100%; }
  .journey-steps { gap: 22px; margin-top: 28px; }
  .jstep { display: grid; grid-template-columns: 92px minmax(0, 1fr); column-gap: 18px; align-items: center; text-align: left; }
  .jstep .orb { grid-row: 1 / span 3; width: 92px; margin: 0; box-shadow: var(--shadow-sm); }
  .jstep .jn { display: none; }
  .jstep h3 { margin: 0 0 4px; font-size: 18px; }
  .jstep p { margin: 0; max-width: none; font-size: 14px; }
  .land { min-height: 480px; }
  .disc-cta .btn { flex: 1 1 auto; }
}
</style>
