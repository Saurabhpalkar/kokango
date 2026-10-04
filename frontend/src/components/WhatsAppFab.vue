<script setup>
import { WHATSAPP_URL } from '@/utils/links'
</script>

<template>
  <a class="wa-fab" :href="WHATSAPP_URL" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
    <svg class="wa-ico" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3a9 9 0 00-7.8 13.5L3 21l4.7-1.2A9 9 0 1012 3zm0 2a7 7 0 11-3.6 13l-.3-.2-2.2.6.6-2.1-.2-.3A7 7 0 0112 5zm-2.6 3.3c-.2 0-.5.1-.7.4-.3.3-.9.9-.9 2.200s.9 2.500 1 2.700c.1.2 1.800 2.900 4.500 3.900 2.200.8 2.600.700 3.100.600.5-.1 1.500-.6 1.700-1.200.2-.6.2-1.100.2-1.200-.1-.1-.2-.2-.5-.3l-1.600-.8c-.2-.1-.4-.1-.6.1l-.7.900c-.1.200-.3.200-.5.100-.9-.4-2-1-2.800-2.400-.1-.2 0-.3.100-.4l.4-.5c.1-.1.100-.3.200-.4 0-.2 0-.3-.1-.4l-.7-1.800c-.2-.5-.4-.4-.6-.4z"/></svg>
  </a>
</template>

<style scoped>
.wa-fab {
  position: fixed;
  right: 24px;
  bottom: 24px;
  z-index: 50;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #1FA855;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 24px rgba(31, 168, 85, 0.4);
  text-decoration: none;
}
.wa-fab:hover { color: #fff; }
@media (max-width: 640px) {
  .wa-fab {
    right: 16px;
    bottom: 16px;
    bottom: calc(16px + env(safe-area-inset-bottom, 0px));
    width: 52px;
    height: 52px;
    box-shadow: 0 6px 16px rgba(31, 168, 85, 0.4);
  }
  .wa-ico { width: 26px; height: 26px; }
}
</style>
