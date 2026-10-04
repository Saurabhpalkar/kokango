<script setup>
import { useToastStore } from '@/stores/toast'
const toast = useToastStore()
</script>

<template>
  <div role="status" aria-live="polite" class="toast" :class="{ on: toast.visible }">{{ toast.message }}</div>
</template>

<style scoped>
.toast{position:fixed;left:50%;bottom:28px;transform:translate(-50%,20px);z-index:100;background:#14301C;color:#fff;font-family:'Plus Jakarta Sans','Noto Sans Devanagari',system-ui,sans-serif;font-size:14px;font-weight:600;padding:14px 24px;border-radius:999px;box-shadow:0 12px 30px rgba(20,48,28,.28);opacity:0;pointer-events:none;transition:opacity .25s,transform .25s}
.toast.on{opacity:1;transform:translate(-50%,0)}
</style>
