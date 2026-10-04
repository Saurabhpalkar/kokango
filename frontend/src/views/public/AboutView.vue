<template>
<div class="dcroot">
<div style="width:100%;position:relative;overflow:hidden;background:var(--bg)">
  <AnnouncementBar />
  <div style="position:absolute;top:-200px;left:50%;margin-left:-300px;width:600px;height:600px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;top:700px;right:-240px;width:480px;height:480px;border-radius:50%;background:var(--tint)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader active="about" />

    <section class="ab-hero" style="text-align:center;padding:72px 0 56px">
      <span style="display:inline-block;background:var(--tint);color:var(--green);font-size:13px;font-weight:700;padding:8px 16px;border-radius:999px;letter-spacing:0.5px">OUR STORY</span>
      <h1 style="margin:20px auto;font-size:clamp(34px,8.5vw,62px);font-weight:800;line-height:1.08;max-width:860px;letter-spacing:-2px">Rooted in the <span style="color:var(--green)">Konkan</span>, made for your kitchen</h1>
      <p style="margin:0 auto;font-size:17px;line-height:1.8;color:var(--muted);max-width:640px">Kokango brings traditional Konkan snacks and herbal powders to homes across India, made in small batches with simple ingredients and sealed fresh.</p>
    </section>

    <section class="ab-two" style="display:flex;flex-wrap:wrap;gap:32px;align-items:stretch">
      <div class="card ab-card" style="flex:1 1 440px;min-width:0;border-radius:44px;padding:44px;box-sizing:border-box">
        <h2 style="margin:0 0 16px;font-size:28px;font-weight:800">Who we are</h2>
        <p style="margin:0 0 14px;font-size:15px;line-height:1.8;color:var(--muted)">We started Kokango to share the flavours we grew up with: crispy jackfruit chips, tangy jamun vadi and leaf and root powders ground the old-fashioned way.</p>
        <p style="margin:0;font-size:15px;line-height:1.8;color:var(--muted)">Every pack is prepared in small batches, checked by hand and sealed in a resealable pouch so it reaches you tasting fresh.</p>
      </div>
      <div class="card ab-art" style="flex:1 1 440px;min-width:0;border-radius:44px;padding:32px;box-sizing:border-box;display:flex;align-items:center;justify-content:center;background:linear-gradient(160deg,#fff,var(--tint))">
        <img class="ab-s" src="/images/jackfruit-chips.png" alt="Jackfruit chips pack" style="height:250px;width:auto;object-fit:contain;margin-right:-18px">
        <img class="ab-l" src="/images/moringa-powder.png" alt="Moringa powder pack" style="height:300px;width:auto;object-fit:contain;position:relative">
        <img class="ab-s" src="/images/jamun-vadi.png" alt="Jamun vadi pack" style="height:250px;width:auto;object-fit:contain;margin-left:-18px">
      </div>
    </section>

    <section class="ab-sec" style="padding-top:72px">
      <h2 style="margin:0 0 36px;text-align:center;font-size:clamp(26px,6.5vw,36px);font-weight:800;letter-spacing:-1px">What we stand for</h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(260px,100%),1fr));gap:24px">
        <template v-for="(v, v_i) in values" :key="v_i">
          <div class="card" style="border-radius:32px;padding:28px;box-sizing:border-box">
            <div :style="`width:48px;height:48px;border-radius:16px;background:${v.bg};color:var(--ink);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800`">{{ v.n }}</div>
            <h3 style="margin:18px 0 8px;font-size:19px;font-weight:700">{{ v.title }}</h3>
            <p style="margin:0;font-size:14px;line-height:1.7;color:var(--muted)">{{ v.text }}</p>
          </div>
        </template>
      </div>
    </section>

    <section class="ab-cta" style="margin-top:72px;border-radius:52px;padding:52px;box-sizing:border-box;text-align:center;background:var(--sun)">
      <h2 style="margin:0 0 12px;font-size:32px;font-weight:800;letter-spacing:-1px">Questions? Talk to us</h2>
      <p style="margin:0 0 26px;font-size:15px;color:#4d4a35">We reply on WhatsApp within a few hours.</p>
      <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap"><a class="btn" :href="WHATSAPP_URL" target="_blank" rel="noopener">WhatsApp us</a><a href="/faq" class="btn-line">Read FAQ</a></div>
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

import { getAboutData } from '@/content/about'

const { values } = getAboutData()
</script>

<style scoped>
.dcroot{--bg:#F8F7EF;--surface:#fff;--ink:#14301C;--muted:#566659;--line:#E6E2D0;--green:#0B5D1E;--leaf:#9ACD32;--mango:#FFB400;--amber:#9A5B00;--tint:#EAF3DD;--sun:#FFF1CC}
.dcroot{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans','Noto Sans Devanagari',system-ui,sans-serif}
a{color:var(--ink);text-decoration:none}a:hover{color:var(--green)}
button{font-family:inherit;cursor:pointer}
.card{background:var(--surface);border:1px solid var(--line);box-shadow:0 10px 30px rgba(20,48,28,0.06)}
.btn{background:var(--green);color:#fff;border:none;border-radius:999px;padding:12px 28px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.btn:hover{background:#08481a;color:#fff}
.btn-line{background:transparent;color:var(--ink);border:1.5px solid var(--ink);border-radius:999px;padding:11px 26px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.btn-line:hover{background:var(--ink);color:#fff}

/* ---- responsive ---- */
img{max-width:100%}
@media (max-width:600px){.wrap{padding-left:16px!important;padding-right:16px!important}}

@media (max-width:600px){
  .ab-hero{padding:40px 0 36px!important}
  .ab-card{padding:26px 22px!important;border-radius:32px!important}
  .ab-art{padding:24px 14px!important;border-radius:32px!important;min-height:0}
  .ab-s{height:min(250px,34vw)!important}
  .ab-l{height:min(300px,41vw)!important}
  .ab-sec{padding-top:48px!important}
  .ab-cta{margin-top:48px!important;padding:32px 20px!important;border-radius:32px!important}
}
</style>
