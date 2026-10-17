// Seasonal background effects (light Konkan monsoon rain, a summer breeze, winter mist).
//
// SEASON: 'auto' follows the Indian calendar (Mar–May summer, Jun–Sep rainy, Oct–Feb winter),
//         or force one of 'summer' | 'rainy' | 'winter', or 'off' to switch the effects off.
// It can also be set at build time with VITE_SEASON in frontend/.env (that value wins).
export const SEASON = 'auto'

export function activeSeason(date = new Date()) {
  // preview any season by adding ?season=rainy (summer, winter, off) to the address
  let preview = ''
  try { preview = new URLSearchParams(window.location.search).get('season') || '' } catch (e) { /* no window */ }
  const chosen = String(preview || import.meta.env.VITE_SEASON || SEASON).toLowerCase()
  if (['summer', 'rainy', 'winter', 'off'].includes(chosen)) return chosen
  const m = date.getMonth() + 1
  if (m >= 3 && m <= 5) return 'summer'
  if (m >= 6 && m <= 9) return 'rainy'
  return 'winter'
}

// Timing (milliseconds). Waits are randomised inside each range.
export const SEASON_TIMING = {
  afterPointer: 1800,             // the effect keeps going this long after the mouse stops moving, then fades
  afterTouch: 2600,               // phones: how long a touch on a landscape area plays the effect
  firstAuto: [5000, 10000],       // first automatic effect after the page is ready
  autoEvery: [15000, 30000],      // then roughly this often (only when the mouse is not already playing one)
  autoEveryTouch: [25000, 45000], // phones and tablets: less often
  autoStrength: 0.6,              // automatic effects are lighter than mouse ones (1 = full)
  autoLength: 4000,               // and last about this long
}
