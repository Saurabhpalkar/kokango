// Shared look-up tables for the admin pages (colours match the approved design).
export const orderStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled']
export const orderStatusStyles = {
  pending: { bg: '#EDEDE4', fg: '#3b4a3f' },
  confirmed: { bg: '#DDF1F0', fg: '#0F6B66' },
  processing: { bg: '#FFF4CC', fg: '#7A4A00' },
  shipped: { bg: '#E1ECFB', fg: '#1D4E9E' },
  delivered: { bg: '#E8F3E4', fg: '#103D2C' },
  cancelled: { bg: 'var(--danger-bg)', fg: 'var(--danger)' },
}
export const orderPill = (s) => { const x = orderStatusStyles[s] || orderStatusStyles.pending; return `background:${x.bg};color:${x.fg}` }

export const shipmentStatuses = ['pending', 'ready_to_ship', 'pickup_scheduled', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'returned', 'cancelled']
export const label = (s) => String(s || '').split('_').map((w) => (w ? w[0].toUpperCase() + w.slice(1) : w)).join(' ')
export const shipmentPill = (s) => {
  let x = { bg: '#FFF4CC', fg: '#7A4A00' }
  if (s === 'delivered') x = { bg: '#E8F3E4', fg: '#103D2C' }
  else if (s === 'returned' || s === 'cancelled') x = { bg: 'var(--danger-bg)', fg: 'var(--danger)' }
  else if (['in_transit', 'out_for_delivery', 'picked_up'].includes(s)) x = { bg: '#E1ECFB', fg: '#1D4E9E' }
  else if (s === 'pending') x = { bg: '#EDEDE4', fg: '#3b4a3f' }
  return `background:${x.bg};color:${x.fg}`
}

export const paymentStatuses = {
  paid: { label: 'Paid', bg: '#E8F3E4', fg: '#103D2C' },
  pending: { label: 'Pending', bg: '#EDEDE4', fg: '#3b4a3f' },
  failed: { label: 'Failed', bg: 'var(--danger-bg)', fg: 'var(--danger)' },
  refunded: { label: 'Refunded', bg: '#FFF4CC', fg: '#7A4A00' },
}
export const paymentPill = (s) => { const x = paymentStatuses[s] || paymentStatuses.pending; return `background:${x.bg};color:${x.fg}` }

export const initial = (name) => (String(name || '?').trim()[0] || '?').toUpperCase()
