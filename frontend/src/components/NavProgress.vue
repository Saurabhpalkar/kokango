<script setup>
// Thin progress bar at the top while a page is loading, so a tap always shows an immediate response.
import { ref, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
const router = useRouter()
const w = ref(0); const on = ref(false)
let t = null; let hide = null
const stop = () => { clearInterval(t); t = null }
const off = router.beforeEach(() => {
  clearTimeout(hide); stop(); on.value = true; w.value = 12
  t = setInterval(() => { w.value = Math.min(90, w.value + (90 - w.value) * 0.12) }, 120)
})
const done = router.afterEach(() => { stop(); w.value = 100; hide = setTimeout(() => { on.value = false; w.value = 0 }, 220) })
const err = router.onError(() => { stop(); on.value = false; w.value = 0 })
onBeforeUnmount(() => { off(); done(); err(); stop(); clearTimeout(hide) })
</script>

<template>
  <div class="np" :class="{ on }" role="presentation" aria-hidden="true"><i :style="{ width: w + '%' }"></i></div>
</template>

<style scoped>
.np { position: fixed; left: 0; right: 0; top: 0; height: 3px; z-index: 9999; pointer-events: none; opacity: 0; transition: opacity .2s; }
.np.on { opacity: 1; }
.np i { display: block; height: 100%; background: linear-gradient(90deg, var(--leaf), var(--mango)); border-radius: 0 3px 3px 0; transition: width .2s ease-out; }
@media (prefers-reduced-motion: reduce) { .np i { transition: none; } }
</style>
