<script setup>
// Kokango logo lock-up: the leaf-and-mango "K" in a round badge, the name in Fraunces ("Kok" + golden "ango")
// and a small tagline. Used by the site header and footer.
defineProps({
  tagline: { type: Boolean, default: true },
  size: { type: String, default: 'md' },   // 'md' header, 'lg' footer
})
</script>

<template>
  <a href="/" class="logo" :class="size" aria-label="Kokango, taste of the Konkan: home">
    <span class="badge" aria-hidden="true"><img src="/images/logo.png" alt="" width="423" height="600"></span>
    <span class="words" aria-hidden="true">
      <span class="name">Kok<span class="gold">ango</span></span>
      <span v-if="tagline" class="tag">Taste of the Konkan</span>
    </span>
  </a>
</template>

<style scoped>
.logo { display: inline-flex; align-items: center; gap: 11px; color: #fff; text-decoration: none; min-height: 44px; }
.logo:hover { color: #fff; }

/* round cream badge with a thin golden ring */
.badge {
  position: relative; flex: none; width: 46px; height: 46px; border-radius: 50%;
  background: radial-gradient(circle at 35% 30%, #FFFFFF 0%, #FFF8E6 70%, #F6EBCB 100%);
  box-shadow: 0 0 0 2px var(--mango), 0 0 0 5px rgba(254, 188, 4, .18), 0 6px 14px rgba(0, 0, 0, .25);
  display: flex; align-items: center; justify-content: center;
  transition: transform .45s var(--ease), box-shadow .45s var(--ease);
}
.badge img { height: 31px; width: auto; transform: translateX(1px); filter: drop-shadow(0 1px 1px rgba(0, 0, 0, .12)); }
.logo:hover .badge { transform: rotate(-10deg) scale(1.06); box-shadow: 0 0 0 2px var(--mango), 0 0 0 7px rgba(254, 188, 4, .28), 0 8px 18px rgba(0, 0, 0, .3); }

.words { display: flex; flex-direction: column; line-height: 1; }
.name {
  font-family: 'Fraunces', Georgia, serif; font-weight: 750; font-size: 27px; letter-spacing: -.3px;
  font-variation-settings: 'SOFT' 100, 'opsz' 72; color: #fff;
}
.gold { color: var(--mango); }
.tag { white-space: nowrap; margin-top: 4px; font-size: 9.5px; font-weight: 700; letter-spacing: 2.2px; text-transform: uppercase; color: #CFE3C9; }

.lg .badge { width: 54px; height: 54px; }
.lg .badge img { height: 37px; }
.lg .name { font-size: 32px; }
.lg .tag { font-size: 10.5px; letter-spacing: 2.6px; }

@media (max-width: 899px) {
  .md { gap: 9px; }
  .md .badge { width: 38px; height: 38px; box-shadow: 0 0 0 2px var(--mango), 0 0 0 4px rgba(254, 188, 4, .18), 0 4px 10px rgba(0, 0, 0, .25); }
  .md .badge img { height: 26px; }
  .md .name { font-size: 22px; }
  .md .tag { font-size: 7.5px; letter-spacing: 1.2px; margin-top: 3px; }
}
@media (max-width: 400px) { .md { gap: 7px; } .md .badge { width: 34px; height: 34px; } .md .badge img { height: 23px; } }
@media (max-width: 400px) { .md .tag { display: none; } }
@media (prefers-reduced-motion: reduce) { .badge { transition: none; } .logo:hover .badge { transform: none; } }
</style>
