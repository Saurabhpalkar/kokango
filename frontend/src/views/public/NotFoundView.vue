<template>
<div class="dcroot">
<div style="width:100%;position:relative;overflow:hidden;background:var(--bg)">
  <AnnouncementBar />
  <div style="position:absolute;top:-200px;right:-140px;width:520px;height:520px;border-radius:50%;background:var(--sun)"></div>
  <div style="position:absolute;top:480px;left:-240px;width:460px;height:460px;border-radius:50%;background:var(--tint)"></div>
  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader />

    <section class="nf" style="text-align:center;padding:64px 0 24px">
      <div style="font-size:clamp(96px,28vw,160px);font-weight:800;line-height:1;letter-spacing:-6px;color:var(--green)">404</div>
      <h1 style="margin:16px 0 12px;font-size:clamp(26px,7vw,40px);font-weight:800;letter-spacing:-1px">This page could not be found</h1>
      <p style="margin:0 auto 28px;font-size:17px;line-height:1.8;color:var(--muted);max-width:520px">The link may be broken or the page may have moved. Let us get you back to something tasty.</p>
      <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap"><a class="btn" href="/">Back to home</a><a class="btn-line" href="/products">Browse products</a></div>
      <div class="nf-art" style="display:flex;align-items:center;justify-content:center;margin-top:48px">
        <img class="nf-s" src="/images/jackfruit-chips.png" alt="Jackfruit chips pack" style="height:190px;width:auto;object-fit:contain;margin-right:-14px">
        <img class="nf-l" src="/images/moringa-powder.png" alt="Moringa powder pack" style="height:230px;width:auto;object-fit:contain;position:relative">
        <img class="nf-s" src="/images/jamun-vadi.png" alt="Jamun vadi pack" style="height:190px;width:auto;object-fit:contain;margin-left:-14px">
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
  .nf{padding-top:36px!important}
  .nf-art{margin-top:32px!important}
  .nf-s{height:min(190px,30vw)!important}
  .nf-l{height:min(230px,37vw)!important}
}
</style>
