<script setup>
// Green announcement strip shown above the site header.
defineProps({
  text: { type: String, default: '' },
})
</script>

<template>
  <div class="announce" role="note">
    <template v-if="text">{{ text }}</template>
    <template v-else>
      <span class="part">Delivered fresh across India in 3 to 5 days</span><span class="sep"> &nbsp;&middot;&nbsp; </span><span class="part">Secure payments with Razorpay</span>
    </template>
  </div>
</template>

<style scoped>
.announce {
  background: var(--green);
  color: #fff;
  text-align: center;
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 600;
  position: relative;
  z-index: 2;
}
@media (max-width: 640px) {
  .announce { padding: 8px 14px; font-size: 12px; line-height: 1.45; }
  .part { display: block; }
  .sep { display: none; }
}
</style>
