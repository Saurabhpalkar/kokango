<template>
<div class="dcroot">
<div style="width:100%;position:relative;overflow:hidden;background:var(--bg)">
  <AnnouncementBar />
  <div style="position:absolute;top:-200px;right:-140px;width:520px;height:520px;border-radius:50%;background:var(--sun)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader />

    <section class="pol" style="display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start;margin-top:32px">
      <aside class="card pol-side" style="flex:0 0 270px;border-radius:32px;padding:20px;box-sizing:border-box">
        <div style="font-size:12px;font-weight:700;color:var(--muted);padding:4px 18px 10px;letter-spacing:1.5px">POLICIES</div>
        <a v-for="p in policies" :key="p.slug" :class="['side', { on: p.slug === slug }]" :href="`/policy/${p.slug}`">{{ p.nav }}</a>
      </aside>

      <article v-if="policy" class="card pol-art" style="flex:1 1 640px;min-width:0;border-radius:44px;padding:52px;box-sizing:border-box">
        <h1 style="margin:0 0 8px;font-size:clamp(28px,7vw,42px);font-weight:800;letter-spacing:-1px">{{ policy.title }}</h1>
        <div style="font-size:13px;color:var(--muted);margin-bottom:24px">Last updated {{ policy.updated }}</div>
        <p class="body">{{ policy.intro }}</p>
        <template v-for="sec in policy.sections" :key="sec.h">
          <h2 class="sec">{{ sec.h }}</h2>
          <p v-for="(para, i) in sec.p" :key="i" class="body">{{ para }}</p>
        </template>
      </article>
      <article v-else class="card pol-art" style="flex:1 1 640px;min-width:0;border-radius:44px;padding:52px;box-sizing:border-box;text-align:center">
        <div style="font-size:clamp(64px,20vw,96px);font-weight:800;line-height:1;letter-spacing:-4px;color:var(--green)">404</div>
        <h1 style="margin:12px 0 10px;font-size:32px;font-weight:800;letter-spacing:-0.5px">This page could not be found</h1>
        <p class="body" style="max-width:460px;margin:0 auto 26px">The policy you are looking for does not exist. Pick one from the list or head back to the shop.</p>
        <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap"><a class="btn" href="/">Back to home</a><a class="btn-line" href="/products">Browse products</a></div>
      </article>
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

import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { getPolicyData, getPolicy } from '@/content/policies'

const route = useRoute()
const { policies } = getPolicyData()
const slug = computed(() => String(route.params.slug || ''))
const policy = computed(() => getPolicy(slug.value))
</script>

<style scoped>
.dcroot{--bg:#F8F7EF;--surface:#fff;--ink:#14301C;--muted:#566659;--line:#E6E2D0;--green:#0B5D1E;--leaf:#9ACD32;--mango:#FFB400;--amber:#9A5B00;--tint:#EAF3DD;--sun:#FFF1CC}
.dcroot{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans','Noto Sans Devanagari',system-ui,sans-serif}
a{color:var(--ink);text-decoration:none}a:hover{color:var(--green)}
.card{background:var(--surface);border:1px solid var(--line);box-shadow:0 10px 30px rgba(20,48,28,0.06)}
.btn{background:var(--green);color:#fff;border:none;border-radius:999px;padding:12px 28px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.btn:hover{background:#08481a;color:#fff}
.btn-line{background:transparent;color:var(--ink);border:1.5px solid var(--ink);border-radius:999px;padding:11px 26px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center}
.btn-line:hover{background:var(--ink);color:#fff}
.side{display:block;padding:12px 18px;border-radius:999px;font-size:14px;font-weight:600;color:var(--muted)}
.side.on{background:var(--tint);color:var(--green)}
h2.sec{margin:32px 0 10px;font-size:20px;font-weight:800}
p.body{margin:0 0 12px;font-size:15px;line-height:1.8;color:var(--muted)}

/* ---- responsive ---- */
img{max-width:100%}
@media (max-width:600px){.wrap{padding-left:16px!important;padding-right:16px!important}}

.side{min-height:44px;box-sizing:border-box;display:flex;align-items:center}
p.body,h2.sec{overflow-wrap:anywhere}
@media (max-width:900px){.pol-side{flex:1 1 100%!important}}
@media (max-width:600px){
  .pol{gap:20px!important;margin-top:20px!important}
  .pol-side{padding:14px!important;border-radius:28px!important;display:flex;flex-wrap:wrap;gap:6px}
  .pol-side>div{flex:1 1 100%}
  .side{padding:10px 16px!important}
  .pol-art{padding:26px 20px!important;border-radius:32px!important}
}
</style>
