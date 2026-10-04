<script setup>
import { ref } from 'vue'
import { WHATSAPP_URL, FACEBOOK_URL, INSTAGRAM_URL } from '@/utils/links'
import { useToastStore } from '@/stores/toast'

defineProps({
  // 'full' = brand + quick links + newsletter (home); 'simple' = copyright row (+ policy links)
  variant: { type: String, default: 'simple' },
  // simple variant only: show the Track order + policy links next to the copyright
  links: { type: Boolean, default: true },
})

const toast = useToastStore()
const nlEmail = ref('')
function onSubscribe() {
  if (!nlEmail.value || !String(nlEmail.value).trim()) { toast.show('Please enter your email address'); return }
  toast.show('Thanks for subscribing!')
  nlEmail.value = ''
}
</script>

<template>
  <footer v-if="variant === 'full'" class="foot full">
    <div class="col brandcol">
      <div class="brand"><img src="/images/logo.png" alt="Kokango logo"><span>Kokango</span></div>
      <p class="blurb">Authentic Konkan flavours, packed fresh and shipped across India.</p>
      <div class="social"><a :href="FACEBOOK_URL" target="_blank" rel="noopener">Facebook</a><a :href="INSTAGRAM_URL" target="_blank" rel="noopener">Instagram</a><a :href="WHATSAPP_URL" target="_blank" rel="noopener">WhatsApp</a></div>
    </div>
    <div class="col linkcol">
      <div class="h">Quick Links</div>
      <div class="qlinks"><a href="/">Home</a><a href="/about">About</a><a href="/faq">FAQ</a><a href="/track-order">Track order</a><a href="/policy/shipping">Shipping Policy</a><a href="/policy/refund">Refund Policy</a><a href="/policy/terms">Terms &amp; Conditions</a><a href="/policy/privacy">Privacy Policy</a></div>
    </div>
    <div class="col newscol">
      <div class="h">Get offers and updates</div>
      <form class="news" @submit.prevent="onSubscribe"><label for="nl" class="sr">Email address</label><input id="nl" v-model="nlEmail" type="email" placeholder="Enter your email"><button type="submit" class="btn">Subscribe</button></form>
      <p class="copy">&copy; 2026 Kokango. All rights reserved.</p>
    </div>
  </footer>

  <footer v-else class="foot simple" :class="{ bare: !links }">
    <template v-if="links">
      <span>&copy; 2026 Kokango. All rights reserved.</span>
      <span class="plinks"><a href="/track-order">Track order</a><a href="/policy/shipping">Shipping Policy</a><a href="/policy/refund">Refund Policy</a><a href="/policy/terms">Terms &amp; Conditions</a><a href="/policy/privacy">Privacy Policy</a></span>
    </template>
    <template v-else>&copy; 2026 Kokango. All rights reserved.</template>
  </footer>
</template>

<style scoped>
a { color: var(--ink); text-decoration: none; }
a:hover { color: var(--green); }
button { font-family: inherit; cursor: pointer; }
.sr { position: absolute; left: -9999px; }

.foot { border-top: 1px solid var(--line); }

/* simple */
.simple { margin-top: 96px; padding: 32px 0 40px; display: flex; flex-wrap: wrap; gap: 24px; justify-content: space-between; font-size: 13px; color: var(--muted); }
.simple.bare { display: block; }
.plinks { display: flex; gap: 20px; flex-wrap: wrap; }

/* full */
.full { margin-top: 88px; padding: 56px 0 40px; display: flex; flex-wrap: wrap; gap: 40px; justify-content: space-between; }
.brandcol { flex: 1 1 300px; max-width: 360px; }
.linkcol { flex: 0 0 200px; }
.newscol { flex: 1 1 320px; max-width: 380px; }
.brand { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 22px; }
.brand img { height: 44px; width: auto; }
.blurb { margin: 16px 0; font-size: 14px; line-height: 1.7; color: var(--muted); }
.social { display: flex; gap: 18px; font-size: 14px; font-weight: 600; }
.h { font-size: 15px; font-weight: 700; margin-bottom: 14px; }
.qlinks { display: flex; flex-direction: column; gap: 10px; font-size: 14px; color: var(--muted); }
.news { display: flex; }
.news input {
  flex: 1; min-width: 0; height: 48px; box-sizing: border-box; background: #fff; border: 1px solid var(--line); border-right: none;
  border-radius: 999px 0 0 999px; padding: 0 20px; color: var(--ink); font-family: inherit; font-size: 14px;
}
.news input::placeholder { color: #7b8a7e; }
.btn {
  background: var(--green); color: #fff; border: none; border-radius: 0 999px 999px 0; padding: 0 24px; height: 48px;
  font-size: 15px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  transition: background .25s;
}
.btn:hover { background: #08481a; }
.copy { margin: 14px 0 0; font-size: 12px; color: var(--muted); }

@media (max-width: 899px) {
  .simple { margin-top: 64px; }
  .full { margin-top: 64px; padding: 40px 0 32px; gap: 32px; }
}
@media (max-width: 640px) {
  .simple { margin-top: 48px; padding: 24px 0 32px; gap: 16px; flex-direction: column; justify-content: flex-start; }
  .simple.bare { display: block; }
  .plinks { gap: 4px 20px; }
  .plinks a { display: inline-flex; align-items: center; min-height: 36px; }

  .full { margin-top: 48px; padding: 32px 0 28px; flex-direction: column; flex-wrap: nowrap; gap: 28px; }
  .brandcol, .linkcol, .newscol { flex: none; max-width: none; width: 100%; }
  .qlinks { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
  .qlinks a { display: flex; align-items: center; min-height: 40px; }
  .social { flex-wrap: wrap; gap: 6px 22px; }
  .social a { display: inline-flex; align-items: center; min-height: 40px; }
  .news input, .btn { height: 48px; }
  .btn { padding: 0 20px; }
}
</style>
