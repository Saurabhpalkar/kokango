// Shared look-up tables for the admin pages (colours match the approved design).
export const orderStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled']
export const orderStatusStyles = {
  pending: { bg: '#EDEDE4', fg: '#3b4a3f' },
  confirmed: { bg: '#DDF1F0', fg: '#0F6B66' },
  processing: { bg: '#FFF1CC', fg: '#7A4A00' },
  shipped: { bg: '#E1ECFB', fg: '#1D4E9E' },
  delivered: { bg: '#EAF3DD', fg: '#0B5D1E' },
  cancelled: { bg: '#FBE3E1', fg: '#B3261E' },
}
export const orderPill = (s) => { const x = orderStatusStyles[s] || orderStatusStyles.pending; return `background:${x.bg};color:${x.fg}` }

export const shipmentStatuses = ['pending', 'ready_to_ship', 'pickup_scheduled', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'returned', 'cancelled']
export const label = (s) => String(s || '').split('_').map((w) => (w ? w[0].toUpperCase() + w.slice(1) : w)).join(' ')
export const shipmentPill = (s) => {
  let x = { bg: '#FFF1CC', fg: '#7A4A00' }
  if (s === 'delivered') x = { bg: '#EAF3DD', fg: '#0B5D1E' }
  else if (s === 'returned' || s === 'cancelled') x = { bg: '#FBE3E1', fg: '#B3261E' }
  else if (['in_transit', 'out_for_delivery', 'picked_up'].includes(s)) x = { bg: '#E1ECFB', fg: '#1D4E9E' }
  else if (s === 'pending') x = { bg: '#EDEDE4', fg: '#3b4a3f' }
  return `background:${x.bg};color:${x.fg}`
}

export const paymentStatuses = {
  paid: { label: 'Paid', bg: '#EAF3DD', fg: '#0B5D1E' },
  pending: { label: 'Pending', bg: '#EDEDE4', fg: '#3b4a3f' },
  failed: { label: 'Failed', bg: '#FBE3E1', fg: '#B3261E' },
  refunded: { label: 'Refunded', bg: '#FFF1CC', fg: '#7A4A00' },
}
export const paymentPill = (s) => { const x = paymentStatuses[s] || paymentStatuses.pending; return `background:${x.bg};color:${x.fg}` }

export const initial = (name) => (String(name || '?').trim()[0] || '?').toUpperCase()
