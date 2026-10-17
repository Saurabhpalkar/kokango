<script setup>
import { useRouter } from 'vue-router'
import { onMounted } from 'vue'
import ToastHost from '@/components/ToastHost.vue'
import NavProgress from '@/components/NavProgress.vue'
import QuickView from '@/components/QuickView.vue'
const router = useRouter()

// Fetch a page's code ahead of the tap (hover / touch start / idle) so navigation feels instant. Skipped on data-saver and slow links.
const saving = () => { const c = navigator.connection; return !!(c && (c.saveData || /(^|-)2g$/.test(c.effectiveType || ''))) }
const warmed = new Set()
function prefetch(path) {
  if (!path || warmed.has(path) || saving()) return
  warmed.add(path)
  try { router.resolve(path).matched.forEach((m) => { const c = m.components && m.components.default; if (typeof c === 'function') c().catch(() => warmed.delete(path)) }) } catch (e) { warmed.delete(path) }
}
function onWarm(e) {
  const a = e.target.closest && e.target.closest('a[href]')
  const href = a && a.getAttribute('href')
  if (href && href.startsWith('/') && !a.target) prefetch(href.split('#')[0])
}
onMounted(() => {
  const idle = window.requestIdleCallback || ((f) => setTimeout(f, 2500))
  idle(() => ['/products', '/cart', '/login'].forEach(prefetch), { timeout: 6000 })
})

// The page templates use plain <a href="/path"> links; route them without a page reload.
function onClick(e) {
  if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey) return
  const a = e.target.closest && e.target.closest('a[href]')
  if (!a) return
  const href = a.getAttribute('href')
  if (href && href.startsWith('/') && !a.target) {
    e.preventDefault()
    router.push(href)
  }
}
</script>

<template>
  <div @click="onClick" @mouseover="onWarm" @touchstart.passive="onWarm" @focusin="onWarm">
    <NavProgress />
    <router-view v-slot="{ Component, route }">
      <transition name="page" mode="out-in"><component :is="Component" :key="route.path" /></transition>
    </router-view>
    <QuickView />
    <ToastHost />
  </div>
</template>
