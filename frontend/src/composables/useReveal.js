// Scroll reveal directive: v-reveal  or  v-reveal="{ delay: 120 }"  or  v-reveal="{ split: true }" (word by word).
// Content is fully visible without JavaScript and with prefers-reduced-motion; the hidden start state
// is only applied once the element is mounted in a browser that can animate it.
const canAnimate = () => typeof window !== 'undefined' && 'IntersectionObserver' in window
  && !(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)

export const vReveal = {
  mounted(el, binding) {
    if (!canAnimate()) return
    const o = binding.value || {}
    if (o.delay) el.style.setProperty('--rv-delay', o.delay + 'ms')
    if (o.split) {
      // wrap each word so it can rise on its own (the text itself is unchanged for screen readers)
      el.innerHTML = el.textContent.trim().split(/\s+/).map((w, i) => `<span class="w" style="--i:${i}">${w.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]))}</span>`).join(' ')
    }
    el.classList.add(o.split ? 'rv-split' : 'rv')
    const io = new IntersectionObserver((entries) => {
      if (entries.some((e) => e.isIntersecting)) { el.classList.add('rv-in'); io.disconnect() }
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' })
    io.observe(el)
    el._rvIo = io
  },
  unmounted(el) { el._rvIo && el._rvIo.disconnect() },
}

// Count-up for figures: v-count on an element whose text starts with a number ("100%", "3–5 days", "70+").
// Text without a leading number is left as it is.
export const vCount = {
  mounted(el) {
    const m = el.textContent.match(/^(\d+)(.*)$/s)
    if (!m || !canAnimate()) return
    const target = Number(m[1]); const rest = m[2]
    el.textContent = '0' + rest
    const io = new IntersectionObserver((entries) => {
      if (!entries.some((e) => e.isIntersecting)) return
      io.disconnect()
      const t0 = performance.now(); const dur = 1400
      const tick = (t) => {
        const k = Math.min(1, (t - t0) / dur)
        el.textContent = Math.round(target * (1 - Math.pow(1 - k, 3))) + rest
        if (k < 1) requestAnimationFrame(tick)
      }
      requestAnimationFrame(tick)
    }, { threshold: 0.4 })
    io.observe(el)
    el._cIo = io
  },
  unmounted(el) { el._cIo && el._cIo.disconnect() },
}
