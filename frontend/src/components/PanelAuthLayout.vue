<script setup>
// Two-panel sign-in layout used by the vendor login and register pages.
defineProps({ badge: { type: String, default: 'VENDOR' }, headline: { type: String, default: '' }, text: { type: String, default: '' } })
</script>
<template>
<div class="dcroot alogin">
  <aside class="apanel on-dark">
    <a href="/" class="abrand"><img src="/images/logo.png" alt="Kokango logo"><span>Kokango</span><span class="abadge">{{ badge }}</span></a>
    <div class="apanel-text"><h2>{{ headline }}</h2><p>{{ text }}</p></div>
    <div class="apanel-foot">&copy; Kokango</div>
  </aside>
  <main class="aform">
    <div class="acard card">
      <a href="/" class="abrand dark"><img src="/images/logo.png" alt="Kokango logo"><span>Kokango</span><span class="abadge">{{ badge }}</span></a>
      <slot />
    </div>
  </main>
</div>
</template>
<style scoped>
.alogin{min-height:100vh;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.1fr)}
.apanel{display:flex;flex-direction:column;justify-content:space-between;gap:32px;padding:36px 48px;color:#fff;background:radial-gradient(circle at 85% 12%,rgba(55,146,55,.4),transparent 45%),linear-gradient(160deg,#103D2C,#0A2A1E)}
.abrand{display:inline-flex;align-items:center;gap:12px;font-weight:800;font-size:22px;letter-spacing:-.3px;color:#fff}
.abrand:hover{color:#fff}
.abrand img{height:40px;width:40px;object-fit:contain;background:#fff;border-radius:10px;padding:3px}
.abrand.dark{color:var(--ink);display:none;margin-bottom:22px}
.abrand.dark:hover{color:var(--ink)}
.abadge{font-size:10px;background:var(--mango);color:var(--ink);padding:3px 8px;border-radius:999px;font-weight:800;letter-spacing:.5px}
.apanel-text h2{margin:0 0 14px;font-size:clamp(30px,3.6vw,46px);line-height:1.08;font-weight:800;letter-spacing:-1.2px;max-width:14ch}
.apanel-text p{margin:0;max-width:380px;font-size:16px;line-height:1.7;color:#D3E2D6}
.apanel-foot{font-size:13px;color:#A9BFB0}
.aform{display:flex;align-items:center;justify-content:center;padding:40px 24px;background:var(--bg)}
.acard{width:100%;max-width:480px;padding:40px;box-sizing:border-box;border-radius:20px}
@media (max-width:900px){
  .alogin{grid-template-columns:1fr}
  .apanel{display:none}
  .abrand.dark{display:inline-flex}
  .aform{align-items:flex-start;padding:24px 16px 48px}
  .acard{padding:28px 20px;border-radius:16px}
}
</style>
