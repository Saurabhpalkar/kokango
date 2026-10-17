<script setup>
// Renders its content only when it is about to scroll into view. Keeps long, below-the-fold sections out of the first
// paint (less DOM, less JS to run at start) while a reserved height stops the page from jumping when the content appears.
import { ref, onMounted, onBeforeUnmount } from 'vue'
const props = defineProps({
  minHeight: { type: String, default: '600px' },
  rootMargin: { type: String, default: '900px 0px' },
  idleAfter: { type: Number, default: 6000 },   // ms: mount anyway once the browser is idle, so it is ready before the visitor gets there
})
const shown = ref(false)
const el = ref(null)
let io = null; let idle = null
const show = () => { shown.value = true; io?.disconnect(); if (idle != null) { (window.cancelIdleCallback || clearTimeout)(idle) } }
onMounted(() => {
  if (!('IntersectionObserver' in window)) { show(); return }
  io = new IntersectionObserver((e) => { if (e.some((x) => x.isIntersecting)) show() }, { rootMargin: props.rootMargin })
  io.observe(el.value)
  idle = window.requestIdleCallback ? window.requestIdleCallback(show, { timeout: props.idleAfter }) : setTimeout(show, props.idleAfter)
})
onBeforeUnmount(() => io?.disconnect())
</script>

<template>
  <div ref="el" :style="shown ? undefined : { minHeight }"><slot v-if="shown" /></div>
</template>
