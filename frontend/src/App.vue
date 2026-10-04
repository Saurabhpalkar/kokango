<script setup>
import { useRouter } from 'vue-router'
import ToastHost from '@/components/ToastHost.vue'
const router = useRouter()

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
  <div @click="onClick">
    <router-view />
    <ToastHost />
  </div>
</template>
