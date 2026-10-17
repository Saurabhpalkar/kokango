<script setup>
// Large banana leaves behind the footer: pale, low opacity, swaying slowly from their stems. Decorative only.
// One leaf shape (fill + its split veins in the footer colour) is drawn once and reused four times.
const veins = []
for (let y = 34; y <= 372; y += 13) {
  const t = y / 400
  const half = 50 * Math.sin(Math.PI * Math.min(1, Math.pow(t, .9) * 1.02)) // leaf half-width at this height
  const rise = 16 + half * .35
  veins.push(`M60 ${y} Q ${(60 - half * .55).toFixed(1)} ${(y - rise * .45).toFixed(1)} ${(60 - half - 2).toFixed(1)} ${(y - rise).toFixed(1)}`)
  veins.push(`M60 ${y} Q ${(60 + half * .55).toFixed(1)} ${(y - rise * .45).toFixed(1)} ${(60 + half + 2).toFixed(1)} ${(y - rise).toFixed(1)}`)
}
// a few deeper tears, like real banana leaves split by the wind
const tears = [[96, -1], [150, 1], [210, -1], [262, 1], [300, -1]].map(([y, s]) => {
  const half = 50 * Math.sin(Math.PI * Math.pow(y / 400, .9) * 1.02)
  return `M${(60 + s * 6).toFixed(1)} ${y} L ${(60 + s * (half + 3)).toFixed(1)} ${(y - 20 - half * .3).toFixed(1)}`
})
const LEAVES = [
  { cls: 'l1', dur: 11, delay: -2 },
  { cls: 'l2', dur: 13, delay: -6 },
  { cls: 'l3', dur: 9.5, delay: -4 },
  { cls: 'l4', dur: 12, delay: -8 },
]
</script>

<template>
  <div class="leaves" aria-hidden="true">
    <svg v-for="l in LEAVES" :key="l.cls" :class="['leaf', l.cls]" viewBox="0 0 120 420" :style="{ animationDuration: l.dur + 's', animationDelay: l.delay + 's' }">
      <path d="M60 400 C 38 360, 6 250, 8 150 C 10 72, 38 22, 60 2 C 82 22, 110 72, 112 150 C 114 250, 82 360, 60 400 Z" fill="currentColor" />
      <path d="M60 420 L60 4" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" />
      <path :d="veins.join(' ')" fill="none" class="cut" stroke-width="1" />
      <path :d="tears.join(' ')" fill="none" class="cut" stroke-width="2.6" stroke-linecap="round" />
    </svg>
  </div>
</template>

<style scoped>
.leaves { position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }
.leaf { position: absolute; color: #9BD27E; opacity: .13; transform-box: fill-box; animation: wave ease-in-out infinite alternate; }
.cut { stroke: var(--forest); }   /* the veins and tears are cut out in the footer colour */
/* each leaf grows from the edge of the footer and sways around its stem */
.l1 { left: -70px; bottom: -150px; width: 260px; transform-origin: 50% 100%; --r: 38deg; }
.l2 { left: 120px; bottom: -190px; width: 200px; transform-origin: 50% 100%; --r: 12deg; opacity: .09; }
.l3 { right: -60px; bottom: -140px; width: 250px; transform-origin: 50% 100%; --r: -36deg; }
.l4 { right: 170px; top: -150px; width: 210px; transform-origin: 50% 0%; --r: 168deg; opacity: .08; }
@keyframes wave { from { transform: rotate(calc(var(--r) - 3deg)) skewX(-1deg); } to { transform: rotate(calc(var(--r) + 3deg)) skewX(1.5deg); } }
@media (max-width: 700px) {
  .l1 { width: 180px; left: -60px; bottom: -110px; }
  .l2 { display: none; }
  .l3 { width: 170px; right: -50px; bottom: -100px; }
  .l4 { width: 150px; right: 30px; top: -110px; }
}
@media (prefers-reduced-motion: reduce) { .leaf { animation: none; transform: rotate(var(--r)); } }
</style>
