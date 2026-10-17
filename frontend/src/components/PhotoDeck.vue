<script setup>
// Photos fanned out like a hand of playing cards. They are "dealt" (fan out from a stack) when scrolled into view;
// hovering a card lifts it to the front; on touch screens a tap brings the next card forward.
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { webpOf } from '@/utils/webp'

const props = defineProps({
  // [{ src, alt, caption? }]
  photos: { type: Array, required: true },
  // fan width in degrees between the two outer cards
  spread: { type: Number, default: 30 },
})

const root = ref(null)
const dealt = ref(false)
const front = ref(-1)       // card lifted to the front (hover / tap), -1 = none
let io = null

const cards = computed(() => {
  const n = props.photos.length
  const mid = (n - 1) / 2
  return props.photos.map((p, i) => {
    const off = n > 1 ? (i - mid) / mid : 0                          // -1 .. 1
    // more cards: closer together, so the hand stays inside its column
    const step = n > 4 ? 34 : 44
    return { ...p, rot: off * props.spread / 2, x: off * step, y: Math.abs(off) * 14, z: i + 1, delay: i * 90 }
  })
})

function tapNext() { front.value = (front.value + 1) % props.photos.length }

onMounted(() => {
  if (!('IntersectionObserver' in window) || window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) { dealt.value = true; return }
  io = new IntersectionObserver(([e]) => { if (e.isIntersecting) { dealt.value = true; io.disconnect() } }, { threshold: 0.3 })
  io.observe(root.value)
})
onBeforeUnmount(() => io && io.disconnect())
</script>

<template>
  <div ref="root" class="deck" :class="{ dealt }" @mouseleave="front = -1">
    <figure
      v-for="(c, i) in cards" :key="c.src" class="pcard"
      :class="{ up: front === i }"
      :style="{ '--r': c.rot + 'deg', '--x': c.x + '%', '--y': c.y + 'px', '--z': front === i ? 50 : c.z, '--d': c.delay + 'ms' }"
      @mouseenter="front = i" @click="tapNext">
      <picture><source v-if="webpOf(c.src)" :srcset="webpOf(c.src)" type="image/webp"><img :src="c.src" :alt="c.alt" loading="lazy" decoding="async" width="900" height="1150"></picture>
      <figcaption v-if="c.caption">{{ c.caption }}</figcaption>
    </figure>
  </div>
</template>

<style scoped>
.deck { position: relative; width: 100%; aspect-ratio: 1.15 / 1; max-width: 560px; margin: 0 auto; touch-action: manipulation; }
.pcard {
  position: absolute; left: 50%; bottom: 6%; margin: 0; width: 44%; aspect-ratio: 3 / 4;
  border-radius: 18px; overflow: hidden; background: #fff; padding: 8px 8px 30px;
  box-shadow: 0 18px 40px rgba(16, 61, 44, .22), 0 2px 6px rgba(16, 61, 44, .12);
  transform-origin: 50% 110%; z-index: var(--z);
  /* stacked in the middle until dealt */
  transform: translateX(-50%) rotate(0deg);
  transition: transform .8s cubic-bezier(.2, .8, .2, 1.1), box-shadow .3s, z-index 0s;
  transition-delay: var(--d), 0s, 0s;
  cursor: pointer;
}
.dealt .pcard { transform: translateX(calc(-50% + var(--x))) translateY(var(--y)) rotate(var(--r)); }
.dealt .pcard.up { transform: translateX(calc(-50% + var(--x))) translateY(calc(var(--y) - 34px)) rotate(calc(var(--r) * .4)) scale(1.06); box-shadow: 0 30px 60px rgba(16, 61, 44, .3); transition-delay: 0s; }
.pcard img { width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 12px; }
.pcard figcaption { position: absolute; left: 0; right: 0; bottom: 7px; text-align: center; font-size: 12.5px; font-weight: 700; color: var(--ink); letter-spacing: .2px; }
/* a gentle sway of the whole hand once it is dealt */
.dealt { animation: hand 7s ease-in-out 1.4s infinite alternate; }
@keyframes hand { from { transform: rotate(-1deg); } to { transform: rotate(1.2deg); } }
@media (max-width: 700px) {
  .deck { width: 92%; }
  .pcard { width: 42%; border-radius: 14px; padding: 6px 6px 24px; }
  .pcard img { border-radius: 9px; }
  .pcard figcaption { font-size: 11px; bottom: 5px; }
}
@media (prefers-reduced-motion: reduce) { .pcard { transition: none; } .dealt { animation: none; } }
</style>
