// Seasonal background effects (rain / summer light / winter snow and mist) drawn on a small canvas.
//
// Mark a landscape area with  v-season-scene  (registered in main.js). Moving the mouse over the area (or touching it
// on a phone) brings the season's effect in around the pointer; it fades out a moment after the pointer stops or
// leaves. Now and then an effect also plays on its own (content/season.js has the timings). The canvas only runs
// while something is visible, never blocks clicks (pointer-events: none), and nothing runs with
// prefers-reduced-motion, in a background tab or for the 'off' season.
import { activeSeason, SEASON_TIMING as T } from '@/content/season'

const rand = (a, b) => a + Math.random() * (b - a)
const INTERACTIVE = 'a,button,input,select,textarea,label,[role="button"],[role="dialog"],.pc,.card'
const TAU = Math.PI * 2

let SEASON = null
let TOUCH = false
const scenes = new Map()          // element -> Scene
let autoTimer = null

function enabled() {
  if (typeof window === 'undefined') return false
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return false
  if (SEASON === null) SEASON = activeSeason()
  return SEASON !== 'off'
}

// a soft round dot, drawn once and reused (snow, dust motes)
const sprites = {}
function dot(rgb) {
  if (sprites[rgb]) return sprites[rgb]
  const c = document.createElement('canvas'); c.width = c.height = 32
  const g = c.getContext('2d'); const r = g.createRadialGradient(16, 16, 0, 16, 16, 16)
  r.addColorStop(0, `rgba(${rgb}, 1)`); r.addColorStop(.45, `rgba(${rgb}, .55)`); r.addColorStop(1, `rgba(${rgb}, 0)`)
  g.fillStyle = r; g.fillRect(0, 0, 32, 32)
  return (sprites[rgb] = c)
}

// ------------------------------------------------------------------ one area with its canvas

class Scene {
  constructor(el) {
    this.el = el
    this.layer = document.createElement('div')
    this.layer.className = 'sfx-layer'
    this.layer.setAttribute('aria-hidden', 'true')
    this.canvas = document.createElement('canvas')
    this.layer.appendChild(this.canvas)
    if (getComputedStyle(el).position === 'static') el.style.position = 'relative'
    el.appendChild(this.layer)
    this.ctx = this.canvas.getContext('2d')
    this.p = []                    // particles
    this.level = 0                 // 0..1 how strong the effect is right now
    this.goal = 0                  // where level is heading
    this.until = 0                 // keep the goal up to this time (ms), then let it fade
    this.mx = .5; this.my = .4     // pointer position (0..1) and its smoothed copy
    this.sx = .5; this.sy = .4
    this.vx = 0                    // pointer speed (adds wind)
    this.lastRipple = 0
    this.visible = false
    this.raf = 0
    this.t = 0
    this.size()
  }

  size() {
    const r = this.el.getBoundingClientRect()
    const dpr = Math.min(window.devicePixelRatio || 1, TOUCH ? 1.25 : 1.5)
    this.w = Math.max(1, r.width); this.h = Math.max(1, r.height)
    this.canvas.width = Math.round(this.w * dpr); this.canvas.height = Math.round(this.h * dpr)
    this.canvas.style.width = this.w + 'px'; this.canvas.style.height = this.h + 'px'
    this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0)
  }

  /** Bring the effect in for `ms` milliseconds at `strength` (0..1). */
  wake(strength, ms) {
    this.goal = Math.max(this.goal, strength)
    this.until = Math.max(this.until, performance.now() + ms)
    if (!this.raf && !document.hidden) { this.t = performance.now(); this.raf = requestAnimationFrame((t) => this.frame(t)) }
  }

  frame(now) {
    const dt = Math.min(.05, (now - this.t) / 1000); this.t = now
    if (now > this.until) this.goal = 0
    this.level += (this.goal - this.level) * Math.min(1, dt * (this.goal > this.level ? 3.5 : 1.6))
    this.sx += (this.mx - this.sx) * Math.min(1, dt * 3); this.sy += (this.my - this.sy) * Math.min(1, dt * 3)
    this.vx *= Math.pow(.2, dt)

    const g = this.ctx
    g.clearRect(0, 0, this.w, this.h)
    g.save(); g.globalAlpha = 1
    DRAW[SEASON](this, g, dt, now)
    g.restore()
    // fading out: whatever is still on screen goes with it (snow would otherwise take seconds to fall away)
    if (this.goal === 0 && this.level < .03) this.p.length = 0

    // stop completely when nothing is left to draw (no loop runs while the area is calm)
    if (this.level < .01 && this.goal === 0 && !this.p.length) {
      this.level = 0; this.raf = 0; g.clearRect(0, 0, this.w, this.h)
      return
    }
    this.raf = document.hidden ? 0 : requestAnimationFrame((t) => this.frame(t))
  }

  destroy() { cancelAnimationFrame(this.raf); this.raf = 0; this.layer.remove() }
}

// ------------------------------------------------------------------ what each season draws

const DRAW = {
  // Monsoon rain: slanting drops (denser near the pointer), splashes where they land, ripples under the pointer.
  rainy(s, g, dt, now) {
    const W = s.w, H = s.h, L = s.level
    if (L > .01) {   // the light dims a little while it rains
      const sky = g.createLinearGradient(0, 0, 0, H)
      sky.addColorStop(0, `rgba(40, 55, 70, ${.32 * L})`); sky.addColorStop(1, `rgba(20, 30, 40, ${.12 * L})`)
      g.fillStyle = sky; g.fillRect(0, 0, W, H)
    }
    const wind = -180 + s.vx * 2.2
    const rate = (TOUCH ? 140 : 320) * L * dt * (W / 1300 + .4)
    for (let i = 0; i < rate; i++) {
      const near = Math.random() < .55
      const x = near ? s.sx * W + rand(-W * .22, W * .22) : rand(-60, W + 120)
      s.p.push({ k: 'drop', x, y: rand(-80, -10), vy: rand(1100, 1600), vx: wind + rand(-30, 30), len: rand(14, 30), a: rand(.25, .6), end: rand(H * .55, H * 1.02) })
    }
    if (s.goal > 0 && !TOUCH && now - s.lastRipple > 160) {   // ripples where the pointer moves
      s.lastRipple = now
      s.p.push({ k: 'ring', x: s.mx * W + rand(-14, 14), y: s.my * H + rand(-10, 10), r: 2, max: rand(26, 46), a: .55 })
    }
    g.lineCap = 'round'
    for (let i = s.p.length - 1; i >= 0; i--) {
      const p = s.p[i]
      if (p.k === 'drop') {
        p.x += p.vx * dt; p.y += p.vy * dt
        const tail = p.len / p.vy
        g.strokeStyle = `rgba(215, 230, 245, ${p.a})`; g.lineWidth = 1.2
        g.beginPath(); g.moveTo(p.x, p.y); g.lineTo(p.x - p.vx * tail, p.y - p.vy * tail); g.stroke()
        if (p.y >= p.end) {
          s.p.splice(i, 1)
          s.p.push({ k: 'ring', x: p.x, y: p.y, r: 1, max: rand(6, 14), a: .45, flat: true })
          for (let j = 0; j < 2; j++) s.p.push({ k: 'spray', x: p.x, y: p.y, vx: rand(-60, 60), vy: rand(-150, -70), life: rand(.25, .4), a: .7 })
        }
      } else if (p.k === 'ring') {
        p.r += (p.flat ? 40 : 60) * dt; p.a -= (p.flat ? 1.4 : .9) * dt
        if (p.a <= 0 || p.r > p.max) { s.p.splice(i, 1); continue }
        g.strokeStyle = `rgba(225, 238, 250, ${p.a})`; g.lineWidth = 1
        g.beginPath(); g.ellipse(p.x, p.y, p.r, p.r * (p.flat ? .35 : .8), 0, 0, TAU); g.stroke()
      } else {
        p.vy += 900 * dt; p.x += p.vx * dt; p.y += p.vy * dt; p.life -= dt
        if (p.life <= 0) { s.p.splice(i, 1); continue }
        g.fillStyle = `rgba(225, 238, 250, ${p.a * Math.min(1, p.life * 2.5)})`; g.fillRect(p.x, p.y, 1.6, 1.6)
      }
    }
  },

  // Summer: a warm sun glow with slow light rays near the pointer, golden motes rising, a leaf on the breeze.
  summer(s, g, dt, now) {
    const W = s.w, H = s.h, L = s.level
    const cx = s.sx * W, cy = s.sy * H
    if (L > .01) {
      g.save(); g.globalCompositeOperation = 'screen'
      const R = Math.max(W, H) * .42
      const glow = g.createRadialGradient(cx, cy, 0, cx, cy, R)
      glow.addColorStop(0, `rgba(255, 226, 150, ${.55 * L})`); glow.addColorStop(.25, `rgba(255, 196, 90, ${.25 * L})`); glow.addColorStop(1, 'rgba(255, 170, 60, 0)')
      g.fillStyle = glow; g.fillRect(0, 0, W, H)
      g.translate(cx, cy); g.rotate(now / 9000)   // soft rays turning slowly around the light
      for (let i = 0; i < 7; i++) {
        g.rotate(TAU / 7)
        const ray = g.createLinearGradient(0, 0, R * 1.3, 0)
        ray.addColorStop(0, `rgba(255, 232, 175, ${.26 * L})`); ray.addColorStop(1, 'rgba(255, 230, 170, 0)')
        g.fillStyle = ray; g.beginPath(); g.moveTo(0, 0); g.lineTo(R * 1.3, -R * .07); g.lineTo(R * 1.3, R * .07); g.closePath(); g.fill()
      }
      g.restore()
    }
    const rate = (TOUCH ? 6 : 12) * L * dt
    for (let i = 0; i < rate; i++) s.p.push({ k: 'mote', x: cx + rand(-220, 220), y: cy + rand(-120, 140), vy: rand(-26, -10), ph: rand(0, TAU), r: rand(1.2, 3.2), life: rand(2.5, 4.5), max: 0 })
    if (L > .3 && Math.random() < dt * .5) s.p.push({ k: 'leaf', x: -20, y: rand(H * .1, H * .7), vx: rand(110, 180), ph: rand(0, TAU), rot: rand(0, TAU), spin: rand(-2.5, 2.5), sz: rand(9, 14) })
    const mote = dot('255, 224, 140')
    for (let i = s.p.length - 1; i >= 0; i--) {
      const p = s.p[i]
      if (p.k === 'mote') {
        p.max = p.max || p.life; p.life -= dt
        if (p.life <= 0) { s.p.splice(i, 1); continue }
        p.y += p.vy * dt; p.x += Math.sin(now / 900 + p.ph) * 12 * dt + s.vx * .02
        const k = p.life / p.max
        g.globalAlpha = Math.min(1, k * 2.5, (1 - k) * 4) * .75
        g.drawImage(mote, p.x - p.r * 2, p.y - p.r * 2, p.r * 4, p.r * 4)
        g.globalAlpha = 1
      } else {
        p.x += (p.vx + s.vx * .5) * dt; p.y += Math.sin(now / 600 + p.ph) * 40 * dt + 18 * dt; p.rot += p.spin * dt
        if (p.x > W + 30) { s.p.splice(i, 1); continue }
        g.save(); g.translate(p.x, p.y); g.rotate(p.rot); g.fillStyle = 'rgba(122, 170, 66, .9)'
        g.beginPath(); g.moveTo(-p.sz, 0); g.quadraticCurveTo(0, -p.sz * .8, p.sz, 0); g.quadraticCurveTo(0, p.sz * .8, -p.sz, 0); g.fill()
        g.strokeStyle = 'rgba(70, 105, 35, .8)'; g.lineWidth = .8; g.beginPath(); g.moveTo(-p.sz, 0); g.lineTo(p.sz, 0); g.stroke()
        g.restore()
      }
    }
  },

  // Winter: soft snowflakes drifting down (they swirl away from the pointer) and cold mist around it.
  winter(s, g, dt, now) {
    const W = s.w, H = s.h, L = s.level
    const cx = s.sx * W, cy = s.sy * H
    if (L > .01) {
      g.fillStyle = `rgba(200, 220, 235, ${.1 * L})`; g.fillRect(0, 0, W, H)   // a cool tint
      for (let i = 0; i < 2; i++) {   // bands of mist drifting across
        const mx = ((now / (i ? 60 : 85)) % (W * 1.6)) - W * .3 + i * W * .4
        const my = H * (i ? .7 : .45)
        const mist = g.createRadialGradient(mx, my, 0, mx, my, W * .38)
        mist.addColorStop(0, `rgba(240, 246, 250, ${.32 * L})`); mist.addColorStop(1, 'rgba(240, 246, 250, 0)')
        g.fillStyle = mist; g.fillRect(0, 0, W, H)
      }
      const puff = g.createRadialGradient(cx, cy, 0, cx, cy, 140)   // cold breath around the pointer
      puff.addColorStop(0, `rgba(245, 250, 255, ${.28 * L})`); puff.addColorStop(1, 'rgba(245, 250, 255, 0)')
      g.fillStyle = puff; g.fillRect(cx - 140, cy - 140, 280, 280)
    }
    const rate = (TOUCH ? 30 : 70) * L * dt * (W / 1300 + .4)
    for (let i = 0; i < rate; i++) s.p.push({ x: rand(-40, W + 40), y: rand(-30, -5), vy: rand(35, 85), ph: rand(0, TAU), r: rand(1.5, 4.2), a: rand(.55, .95), dx: 0 })
    const flake = dot('255, 255, 255')
    for (let i = s.p.length - 1; i >= 0; i--) {
      const p = s.p[i]
      const ddx = p.x - cx, ddy = p.y - cy, d2 = ddx * ddx + ddy * ddy   // gentle push away from the pointer
      if (s.goal > 0 && d2 < 110 * 110 && d2 > 1) { const d = Math.sqrt(d2); p.dx += (ddx / d) * 960 * dt * (1 - d / 110) }
      p.dx *= Math.pow(.15, dt)
      p.y += p.vy * dt; p.x += (Math.sin(now / 1100 + p.ph) * 22 + p.dx + s.vx * .04) * dt
      if (p.y > H + 10) { s.p.splice(i, 1); continue }
      g.globalAlpha = p.a * Math.min(1, L * 2.2)
      g.drawImage(flake, p.x - p.r * 2, p.y - p.r * 2, p.r * 4, p.r * 4)
    }
    g.globalAlpha = 1
  },
}

// ------------------------------------------------------------------ occasional automatic effect

function scheduleAuto(first = false) {
  clearTimeout(autoTimer)
  const [a, b] = first ? T.firstAuto : (TOUCH ? T.autoEveryTouch : T.autoEvery)
  autoTimer = setTimeout(() => {
    const idle = [...scenes.values()].filter((s) => s.visible && !s.raf)
    if (idle.length && !document.hidden) {
      const s = idle[Math.floor(Math.random() * idle.length)]
      s.mx = s.sx = rand(.3, .75); s.my = s.sy = rand(.25, .55)
      s.wake(T.autoStrength, T.autoLength)
    }
    scheduleAuto()
  }, rand(a, b))
}
function onVisibility() {
  if (document.hidden) { clearTimeout(autoTimer); return }
  scheduleAuto()
  scenes.forEach((s) => { if (!s.raf && (s.level > .01 || s.p.length)) s.wake(0, 0) })   // finish a fade paused by the hidden tab
}
function onResize() { scenes.forEach((s) => s.size()) }

// ------------------------------------------------------------------ directive

export const vSeasonScene = {
  mounted(el) {
    if (!enabled()) return
    if (!scenes.size) {
      TOUCH = !!(window.matchMedia && window.matchMedia('(hover: none), (pointer: coarse)').matches)
      document.addEventListener('visibilitychange', onVisibility)
      window.addEventListener('resize', onResize, { passive: true })
      scheduleAuto(true)
    }
    const s = new Scene(el)

    const at = (e) => { const r = el.getBoundingClientRect(); return [(e.clientX - r.left) / r.width, (e.clientY - r.top) / r.height] }
    const onBackground = (e) => !(e.target instanceof Element && e.target.closest(INTERACTIVE))
    let lastX = null
    const onMove = (e) => {
      if (e.pointerType !== 'mouse') return
      const [x, y] = at(e)
      s.mx = x; s.my = y
      if (lastX !== null) s.vx = s.vx * .7 + (e.clientX - lastX) * 8 * .3
      lastX = e.clientX
      // grows while the mouse moves over the background (not while it rests on a button or link)
      if (onBackground(e)) s.wake(1, T.afterPointer)
    }
    const onLeave = () => { lastX = null; s.until = Math.min(s.until, performance.now() + 300) }
    const onTouch = (e) => {   // phones and tablets: a touch on the background plays a short effect there
      if (e.pointerType === 'mouse' || !onBackground(e)) return
      const [x, y] = at(e); s.mx = s.sx = x; s.my = s.sy = y
      s.wake(.85, T.afterTouch)
    }
    el.addEventListener('pointermove', onMove, { passive: true })
    el.addEventListener('pointerleave', onLeave, { passive: true })
    el.addEventListener('pointerdown', onTouch, { passive: true })

    // the automatic effect only plays in an area that is on screen
    const io = new IntersectionObserver(([en]) => { s.visible = en.isIntersecting; if (en.isIntersecting) s.size() }, { threshold: .15 })
    io.observe(el)

    s.cleanup = () => {
      io.disconnect()
      el.removeEventListener('pointermove', onMove)
      el.removeEventListener('pointerleave', onLeave)
      el.removeEventListener('pointerdown', onTouch)
      s.destroy()
    }
    scenes.set(el, s)
  },
  unmounted(el) {
    const s = scenes.get(el)
    if (!s) return
    s.cleanup()
    scenes.delete(el)
    if (!scenes.size) {
      clearTimeout(autoTimer)
      document.removeEventListener('visibilitychange', onVisibility)
      window.removeEventListener('resize', onResize)
    }
  },
}
