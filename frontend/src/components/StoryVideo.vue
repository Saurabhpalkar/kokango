<script setup>
// Cinematic media block: a looping, muted background film with a poster fallback.
// - sources load only when the block nears the viewport (and never on data-saver / slow links,
//   reduced-motion, or small screens unless `mobileAutoplay` is set); then the visitor can press Play
// - audio is never started automatically; the sound button is the visitor's choice
// - pauses when scrolled away, falls back to the poster image if the video cannot load
// - content passed in the default slot is laid over the media (overlay shade keeps text readable)
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  src: { type: String, default: '' },
  srcWebm: { type: String, default: '' },
  poster: { type: String, default: '' },
  label: { type: String, default: 'Kokango brand film' },
  autoplay: { type: Boolean, default: true },
  mobileAutoplay: { type: Boolean, default: false },
  loop: { type: Boolean, default: true },
  shade: { type: Boolean, default: true },
  controls: { type: Boolean, default: true },   // false: decorative background loop, pause it from outside via `paused`
  paused: { type: Boolean, default: false },
  priority: { type: Boolean, default: false },   // true for the first screen: poster loads before anything else
})
const emit = defineEmits(['play', 'pause', 'error'])

const root = ref(null)
const video = ref(null)
const wantLoad = ref(false)   // sources attached
const ready = ref(false)      // first frame decoded
const failed = ref(false)
const playing = ref(false)
const muted = ref(true)
const userPaused = ref(false)
const posterOk = ref(true)
const canAuto = ref(false)
let io = null
let visible = false

const hasVideo = computed(() => !!(props.src || props.srcWebm) && !failed.value)
const showPlayCta = computed(() => hasVideo.value && !wantLoad.value)
const status = computed(() => (playing.value ? 'Film playing' : 'Film paused'))

function decideAuto() {
  const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  const small = window.matchMedia?.('(max-width: 700px)').matches
  const c = navigator.connection
  const slow = !!(c && (c.saveData || /(^|-)2g$/.test(c.effectiveType || '')))
  canAuto.value = props.autoplay && !reduce && !slow && (!small || props.mobileAutoplay)
}

async function start(withSound = false) {
  if (!hasVideo.value) return
  userPaused.value = props.paused
  muted.value = !withSound
  wantLoad.value = true
  await nextTick()
  const v = video.value
  if (!v) return
  v.muted = muted.value
  if (props.paused) return
  try { await v.play() } catch { playing.value = false }
}

function toggle() {
  const v = video.value
  if (!wantLoad.value) { start(true); return }
  if (!v) return
  if (v.paused) { userPaused.value = false; v.play().catch(() => {}) } else { userPaused.value = true; v.pause() }
}

function toggleSound() {
  const v = video.value
  if (!v) return
  muted.value = !muted.value
  v.muted = muted.value
}

function onPlay() { playing.value = true; emit('play') }
function onPause() { playing.value = false; emit('pause') }
function onLoaded() { ready.value = true }
function onFail() { failed.value = true; ready.value = false; playing.value = false; emit('error') }

onMounted(() => {
  decideAuto()
  if (!('IntersectionObserver' in window)) { if (canAuto.value) start(false); return }
  io = new IntersectionObserver((entries) => {
    visible = entries.some((e) => e.isIntersecting)
    const v = video.value
    if (visible) {
      if (!wantLoad.value && canAuto.value) start(false)
      else if (v && v.paused && !userPaused.value && wantLoad.value) v.play().catch(() => {})
    } else if (v && !v.paused) {
      v.pause()
    }
  }, { rootMargin: '200px 0px', threshold: 0.2 })
  io.observe(root.value)
})
onBeforeUnmount(() => { io?.disconnect() })
watch(() => props.paused, (p) => {
  const v = video.value
  userPaused.value = p
  if (!v) return
  if (p) v.pause()
  else if (visible) v.play().catch(() => {})
})
watch(() => [props.src, props.srcWebm], () => { failed.value = false; ready.value = false; wantLoad.value = false })
</script>

<template>
  <div ref="root" class="sv" role="group" :aria-label="label">
    <div class="sv-base" aria-hidden="true"></div>
    <img v-if="poster && posterOk" class="sv-poster" :src="poster" alt="" decoding="async" :fetchpriority="priority ? 'high' : undefined" :loading="priority ? 'eager' : 'lazy'" @error="posterOk = false">
    <video
      v-if="wantLoad && hasVideo"
      ref="video"
      class="sv-video"
      :class="{ on: ready }"
      :loop="loop"
      :poster="posterOk ? poster : undefined"
      muted
      playsinline
      preload="metadata"
      disablepictureinpicture
      aria-hidden="true"
      @loadeddata="onLoaded"
      @play="onPlay"
      @pause="onPause"
      @error="onFail"
    >
      <source v-if="srcWebm" :src="srcWebm" type="video/webm">
      <source v-if="src" :src="src" type="video/mp4" @error="onFail">
    </video>
    <div v-if="shade" class="sv-shade" aria-hidden="true"></div>

    <div class="sv-content"><slot /></div>

    <div v-if="hasVideo && controls" class="sv-ctrl on-dark">
      <button v-if="showPlayCta" type="button" class="sv-btn sv-play" @click="toggle">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5l12 7-12 7z" /></svg>
        <span>Play film</span>
      </button>
      <template v-else>
        <button type="button" class="sv-btn sv-round" :aria-label="playing ? 'Pause film' : 'Play film'" :aria-pressed="playing" @click="toggle">
          <svg v-if="playing" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 5h4v14H6zM14 5h4v14h-4z" /></svg>
          <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5l12 7-12 7z" /></svg>
        </button>
        <button type="button" class="sv-btn sv-round" :aria-label="muted ? 'Turn sound on' : 'Turn sound off'" :aria-pressed="!muted" @click="toggleSound">
          <svg v-if="muted" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5L6 9H3v6h3l5 4z" /><path d="M22 9l-6 6M16 9l6 6" /></svg>
          <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5L6 9H3v6h3l5 4z" /><path d="M15.5 8.5a5 5 0 010 7M18.5 5.5a9 9 0 010 13" /></svg>
        </button>
      </template>
    </div>
    <span class="sr-only" role="status" aria-live="polite">{{ wantLoad ? status : '' }}</span>
  </div>
</template>

<style scoped>
.sv { position: relative; isolation: isolate; overflow: hidden; background: #0A2A1E; color: #fff; }
.sv-base { position: absolute; inset: 0; z-index: -3; background: linear-gradient(160deg, #103D2C 0%, #0F4F52 55%, #12456B 100%); }
.sv-poster, .sv-video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: -2; }
.sv-video { opacity: 0; transition: opacity .9s var(--ease); }
.sv-video.on { opacity: 1; }
.sv-shade { position: absolute; inset: 0; z-index: -1; background:
  linear-gradient(90deg, rgba(6, 28, 20, .84) 0%, rgba(6, 28, 20, .5) 52%, rgba(6, 28, 20, .12) 100%),
  linear-gradient(0deg, rgba(6, 28, 20, .7) 0%, transparent 45%); }
.sv-content { position: relative; height: 100%; }
.sv-ctrl { position: absolute; right: 20px; bottom: 20px; display: flex; gap: 10px; z-index: 2; }
.sv-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 44px; border: 1.5px solid rgba(255, 255, 255, .55); border-radius: 999px; background: rgba(6, 28, 20, .55); color: #fff; font-size: 14px; font-weight: 700; transition: background var(--dur), border-color var(--dur), transform var(--dur) var(--ease); }
.sv-btn:hover { background: rgba(6, 28, 20, .85); border-color: #fff; color: #fff; }
.sv-round { width: 44px; padding: 0; }
.sv-play { padding: 0 20px 0 16px; background: var(--mango); border-color: var(--mango); color: var(--ink); }
.sv-play:hover { background: var(--mango-hover); border-color: var(--mango-hover); color: var(--ink); transform: translateY(-1px); }
@media (max-width: 700px) { .sv-ctrl { right: auto; left: 16px; bottom: 16px; } }
</style>
