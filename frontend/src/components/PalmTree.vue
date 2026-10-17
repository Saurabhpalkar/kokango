<script setup>
// A decorative coconut palm (inline SVG) whose fronds sway gently. Purely decorative: aria-hidden, no pointer events.
// Each frond is an arching midrib with a comb of thin leaflets hanging from it, generated once from a few numbers;
// `flip` mirrors the tree for the right-hand side.
defineProps({ flip: { type: Boolean, default: false } })

const CX = 180, CY = 200 // crown position in the 360 x 720 drawing

// trunk: slender, curving and tapering, with ring marks
const trunk = 'M128 720 C 134 610, 152 470, 166 340 C 172 285, 176 240, 178 212 L 188 213 C 188 245, 186 290, 182 344 C 172 476, 160 612, 164 720 Z'
const rings = Array.from({ length: 22 }, (_, i) => {
  const t = i / 22
  const y = 712 - t * 495
  const x = 146 + (183 - 146) * Math.pow(t, .75)
  const w = 18 - t * 12
  return `M${(x - w).toFixed(1)} ${y.toFixed(1)} q ${w.toFixed(1)} ${(4.5 - t * 3).toFixed(1)} ${(w * 2).toFixed(1)} 0`
})

function frond(angleDeg, len, droop, leaf) {
  const a = (angleDeg * Math.PI) / 180
  const d = [Math.cos(a), Math.sin(a)]
  const P = (t) => [CX + d[0] * len * t, CY + d[1] * len * t + droop * t * t]
  const [mx, my] = P(.5); const [ex, ey] = P(1)
  const rib = `M${CX} ${CY} Q ${(2 * mx - (CX + ex) / 2).toFixed(1)} ${(2 * my - (CY + ey) / 2).toFixed(1)} ${ex.toFixed(1)} ${ey.toFixed(1)}`
  let leaves = ''
  const N = 26
  for (let i = 2; i <= N; i++) {
    const t = i / N
    const [x, y] = P(t); const [x2, y2] = P(Math.min(1, t + .01))
    let tx = x2 - x, ty = y2 - y; const tl = Math.hypot(tx, ty) || 1; tx /= tl; ty /= tl
    const nx = -ty, ny = tx
    const L = leaf * Math.sin(Math.PI * Math.pow(t, .7)) + 4
    for (const s of [1, -1]) {
      // leaflets point outwards, slightly forward along the frond, and hang with gravity
      let vx = nx * s * .75 + tx * .45, vy = ny * s * .75 + ty * .45 + .7
      const vl = Math.hypot(vx, vy); vx /= vl; vy /= vl
      const ex2 = x + vx * L, ey2 = y + vy * L
      leaves += `M${x.toFixed(1)} ${y.toFixed(1)} Q ${(x + vx * L * .55).toFixed(1)} ${(y + vy * L * .55 - L * .12).toFixed(1)} ${ex2.toFixed(1)} ${(ey2 + L * .08).toFixed(1)}`
    }
  }
  return { rib, leaves }
}

// [angle, length, droop, leaflet length, shade, sway seconds, delay]  (angles: 0 = right, -90 = up)
const FRONDS = [
  [-155, 150, 110, 30, 0, 6.2, -1.1], [-128, 140, 80, 28, 1, 5.4, -2.6], [-100, 120, 55, 24, 2, 6.8, -.4],
  [-78, 120, 55, 24, 1, 5.9, -3.2], [-52, 140, 80, 28, 2, 6.5, -1.9], [-25, 150, 110, 30, 0, 5.6, -.8],
  [-178, 150, 150, 30, 1, 6.9, -2.2], [-2, 150, 150, 30, 1, 6.1, -3.6], [200, 120, 150, 26, 2, 5.7, -1.4],
  [-20, 115, 150, 26, 0, 6.4, -2.9],
].map(([ang, len, droop, leaf, shade, dur, delay]) => ({ ...frond(ang, len, droop, leaf), shade, dur, delay }))
const SHADES = ['#2E7631', '#3C8C37', '#55A240']
</script>

<template>
  <svg class="palm" :class="{ flip }" viewBox="0 0 360 720" aria-hidden="true" focusable="false">
    <defs>
      <linearGradient :id="flip ? 'trR' : 'trL'" x1="0" x2="1">
        <stop offset="0" stop-color="#5C3F24" /><stop offset=".5" stop-color="#8E6943" /><stop offset="1" stop-color="#553A20" />
      </linearGradient>
    </defs>
    <g class="tree">
      <path :d="trunk" :fill="`url(#${flip ? 'trR' : 'trL'})`" />
      <path v-for="(r, i) in rings" :key="i" :d="r" fill="none" stroke="#3E2915" stroke-opacity=".5" stroke-width="1.5" stroke-linecap="round" />
      <g class="crown">
        <!-- upper fronds behind, then the coconuts, then the drooping front fronds -->
        <g v-for="(f, i) in FRONDS.slice(0, 6)" :key="'b' + i" class="frond" :style="{ animationDuration: f.dur + 's', animationDelay: f.delay + 's' }">
          <path class="leaves" :d="f.leaves" fill="none" :stroke="SHADES[f.shade]" stroke-width="2.6" stroke-linecap="round" />
          <path :d="f.rib" fill="none" stroke="#2A5A22" stroke-width="3" stroke-linecap="round" />
        </g>
        <g class="nuts">
          <circle cx="172" cy="218" r="11" fill="#6E5527" /><circle cx="191" cy="220" r="10.5" fill="#7D6230" /><circle cx="181" cy="233" r="10" fill="#5F4820" />
          <circle cx="169" cy="214" r="3" fill="#A88A4E" opacity=".6" /><circle cx="188" cy="216" r="3" fill="#B29452" opacity=".55" />
        </g>
        <g v-for="(f, i) in FRONDS.slice(6)" :key="'f' + i" class="frond" :style="{ animationDuration: f.dur + 's', animationDelay: f.delay + 's' }">
          <path class="leaves" :d="f.leaves" fill="none" :stroke="SHADES[f.shade]" stroke-width="2.6" stroke-linecap="round" />
          <path :d="f.rib" fill="none" stroke="#2A5A22" stroke-width="3" stroke-linecap="round" />
        </g>
      </g>
    </g>
  </svg>
</template>

<style scoped>
.palm { display: block; width: 100%; height: auto; overflow: visible; filter: drop-shadow(0 10px 16px rgba(16, 61, 44, .16)); }
.flip { transform: scaleX(-1); }
/* the whole tree leans a touch from its base, the crown rocks, each frond sways on its own rhythm and its leaflets flutter */
.tree { transform-box: view-box; transform-origin: 146px 720px; animation: lean 9s ease-in-out infinite; }
.crown { transform-box: view-box; transform-origin: 180px 200px; animation: crown 7s ease-in-out infinite alternate; }
.frond { transform-box: view-box; transform-origin: 180px 200px; animation-name: sway; animation-timing-function: ease-in-out; animation-iteration-count: infinite; animation-direction: alternate; }
.leaves { transform-box: view-box; transform-origin: 180px 200px; animation: flutter 2.4s ease-in-out infinite alternate; }
@keyframes lean { 0%, 100% { transform: rotate(-.5deg); } 50% { transform: rotate(.7deg); } }
@keyframes crown { from { transform: rotate(-1.5deg); } to { transform: rotate(2deg); } }
@keyframes sway { from { transform: rotate(-3.5deg); } to { transform: rotate(4deg); } }
@keyframes flutter { from { transform: skewX(-1.6deg); } to { transform: skewX(1.8deg); } }
@media (prefers-reduced-motion: reduce) { .tree, .crown, .frond, .leaves { animation: none; } }
</style>
