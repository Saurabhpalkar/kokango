// Money helpers. The API sends paise (integer); the site shows rupees like "₹ 1,196" or "₹ 937.80".
export function inr(rupees) {
  const n = Number(rupees) || 0
  const whole = Math.abs(n - Math.round(n)) < 0.005
  return '₹ ' + n.toLocaleString('en-IN', whole ? { maximumFractionDigits: 0 } : { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
export const inrp = (paise) => inr((Number(paise) || 0) / 100)
export const rupeesToPaise = (r) => Math.round((Number(r) || 0) * 100)
export function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })
}
export function formatDateTime(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString('en-IN', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' })
}
