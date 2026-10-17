<script setup>
import { WHATSAPP_URL } from '@/utils/links'

defineProps({
  // lift the button above a fixed bottom bar on phones (product page purchase bar)
  raised: { type: Boolean, default: false },
})
</script>

<template>
  <a class="wa-fab" :class="{ raised }" :href="WHATSAPP_URL" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
    <svg class="wa-ico" width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3a9 9 0 00-7.8 13.5L3 21l4.7-1.2A9 9 0 1012 3zm0 2a7 7 0 11-3.6 13l-.3-.2-2.2.6.6-2.1-.2-.3A7 7 0 0112 5zm-2.6 3.3c-.2 0-.5.1-.7.4-.3.3-.9.9-.9 2.200s.9 2.500 1 2.700c.1.2 1.800 2.900 4.500 3.900 2.200.8 2.600.700 3.100.600.5-.1 1.500-.6 1.700-1.200.2-.6.2-1.100.2-1.200-.1-.1-.2-.2-.5-.3l-1.600-.8c-.2-.1-.4-.1-.6.1l-.7.900c-.1.200-.3.200-.5.100-.9-.4-2-1-2.800-2.400-.1-.2 0-.3.100-.4l.4-.5c.1-.1.100-.3.200-.4 0-.2 0-.3-.1-.4l-.7-1.800c-.2-.5-.4-.4-.6-.4z"/></svg>
  </a>
</template>

<style scoped>
.wa-fab {
  position: fixed;
  right: 20px;
  bottom: 20px;
  z-index: 50;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #1A8F4A;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 20px rgba(16, 61, 44, .3);
  transition: transform .2s var(--ease), box-shadow .2s;
}
.wa-fab:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 12px 26px rgba(16, 61, 44, .38); }
@media (max-width: 640px) {
  .wa-fab {
    right: 14px;
    bottom: 14px;
    bottom: calc(14px + env(safe-area-inset-bottom, 0px));
    width: 50px;
    height: 50px;
  }
  .wa-ico { width: 25px; height: 25px; }
}
@media (max-width: 900px) {
  .wa-fab.raised { bottom: calc(78px + env(safe-area-inset-bottom, 0px)); }
}
</style>
