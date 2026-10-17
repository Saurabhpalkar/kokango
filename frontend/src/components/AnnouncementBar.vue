<script setup>
// Thin promotional strip above the site header. Phones show one message at a time (it rotates).
import { ref, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  text: { type: String, default: '' },
})
const MESSAGES = ['Delivered fresh across India in 3 to 5 days', 'Cash on Delivery available', 'Secure payments with Razorpay']
const i = ref(0)
let timer = null
onMounted(() => {
  if (props.text || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) return
  timer = setInterval(() => { i.value = (i.value + 1) % MESSAGES.length }, 4000)
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <div class="announce" role="note">
    <template v-if="text">{{ text }}</template>
    <template v-else>
      <span class="all"><template v-for="(m, k) in MESSAGES" :key="k"><span v-if="k" class="sep" aria-hidden="true"></span>{{ m }}</template></span>
      <transition name="msg" mode="out-in"><span :key="i" class="one">{{ MESSAGES[i] }}</span></transition>
    </template>
  </div>
</template>

<style scoped>
.announce {
  background: var(--gold);
  color: #2A1F06;
  text-align: center;
  padding: 7px 16px;
  font-size: 12.5px;
  font-weight: 700;
  letter-spacing: .3px;
  line-height: 1.4;
  position: relative;
  z-index: 61;
  overflow: hidden;
}
.sep { display: inline-block; width: 4px; height: 4px; margin: 0 14px; border-radius: 50%; background: #2A1F06; opacity: .55; vertical-align: middle; }
.one { display: none; }
@media (max-width: 760px) {
  .announce { padding: 6px 12px; font-size: 12px; }
  .all { display: none; }
  .one { display: inline-block; }
}
.msg-enter-active, .msg-leave-active { transition: opacity .3s ease, transform .3s ease; }
.msg-enter-from { opacity: 0; transform: translateY(6px); }
.msg-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
