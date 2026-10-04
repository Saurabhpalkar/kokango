// Pill colours for order / payment statuses (same palette as the approved design).
const MAP = {
  pending: ['Pending', '#FFF1CC', '#7A4A00'],
  confirmed: ['Confirmed', '#EAF3DD', '#0B5D1E'],
  processing: ['Processing', '#EAF3DD', '#0B5D1E'],
  shipped: ['Shipped', '#FFF1CC', '#7A4A00'],
  delivered: ['Delivered', '#EAF3DD', '#0B5D1E'],
  cancelled: ['Cancelled', '#FBE4E0', '#C0392B'],
}
export function statusPill(status) {
  const [label, bg, fg] = MAP[status] || [String(status || ''), '#EEE', '#555']
  return { label, bg, fg }
}
/** An order can be cancelled by the customer until it ships. */
export const isCancellable = (o) => !!o && ['pending', 'confirmed', 'processing'].includes(o.status) && !(o.shipment && ['picked_up', 'in_transit', 'out_for_delivery', 'delivered'].includes(o.shipment.status))
