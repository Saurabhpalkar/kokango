<template>
<div class="dcroot">
<div style="width:100%;position:relative;overflow:hidden;background:var(--bg)">
  <AnnouncementBar />
  <div style="position:absolute;top:-200px;right:-140px;width:520px;height:520px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;top:480px;left:-240px;width:460px;height:460px;border-radius:50%;background:var(--tint)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader active="faq" />

    <section class="fq-hero" style="text-align:center;padding:56px 0 40px">
      <h1 style="margin:0 0 12px;font-size:clamp(32px,8.5vw,56px);font-weight:800;letter-spacing:-1.5px">Frequently Asked <span style="color:var(--green)">Questions</span></h1>
      <p style="margin:0;font-size:17px;color:var(--muted)">Quick answers about orders, delivery and payments.</p>
    </section>

    <section style="max-width:820px;margin:0 auto">
      <template v-for="(f, f_i) in faqs" :key="f_i">
        <details class="card" :open="f.open">
          <summary>{{ f.q }}</summary>
          <div class="fq-a" style="padding:0 30px 26px;font-size:15px;line-height:1.8;color:var(--muted)">{{ f.a }}</div>
        </details>
      </template>

      <div class="fq-help" style="margin-top:40px;border-radius:36px;padding:36px;text-align:center;box-sizing:border-box;background:var(--sun)">
        <h2 style="margin:0 0 8px;font-size:24px;font-weight:800">Still need help?</h2>
        <p style="margin:0 0 20px;font-size:14px;color:#4d4a35">Message us and we will get back to you soon.</p>
        <a class="btn" :href="WHATSAPP_URL" target="_blank" rel="noopener">Chat on WhatsApp</a>
      </div>
    </section>

    <SiteFooter />
  </div>
</div>
<WhatsAppFab />
</div>
</template>

<script setup>
import AnnouncementBar from '@/components/AnnouncementBar.vue'
import SiteHeader from '@/components/SiteHeader.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import WhatsAppFab from '@/components/WhatsAppFab.vue'
import { WHATSAPP_URL } from '@/utils/links'

import { getFaqData } from '@/content/faq'

const { faqs } = getFaqData()
</script>

<style scoped>
.dcroot{--bg:#F8F7EF;--surface:#fff;--ink:#14301C;--muted:#566659;--line:#E6E2D0;--green:#0B5D1E;--leaf:#9ACD32;--mango:#FFB400;--amber:#9A5B00;--tint:#EAF3DD;--sun:#FFF1CC}
.dcroot{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans','Noto Sans Devanagari',system-ui,sans-serif}
a{color:var(--ink);text-decoration:none}a:hover{color:var(--green)}
button{font-family:inherit;cursor:pointer}
.card{background:var(--surface);border:1px solid var(--line);box-shadow:0 10px 30px rgba(20,48,28,0.06)}
.btn{background:var(--green);color:#fff;border:none;border-radius:999px;padding:12px 28px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.btn:hover{background:#08481a;color:#fff}
details{border-radius:28px;margin-bottom:16px}
summary{list-style:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:22px 30px;font-size:16px;font-weight:700;min-height:44px}
summary::-webkit-details-marker{display:none}
summary::after{content:'+';font-size:24px;font-weight:500;color:var(--green)}
details[open] summary::after{content:'\2212'}

/* ---- responsive ---- */
img{max-width:100%}
@media (max-width:600px){.wrap{padding-left:16px!important;padding-right:16px!important}}

summary{gap:16px}
@media (max-width:600px){
  .fq-hero{padding:32px 0 28px!important}
  summary{padding:16px 18px!important;font-size:15px!important}
  .fq-a{padding:0 18px 20px!important}
  details{border-radius:22px!important}
  .fq-help{padding:26px 18px!important;border-radius:28px!important}
}
</style>
