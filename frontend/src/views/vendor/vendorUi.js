import { orderPill, label } from '@/views/admin/adminUi'
export { label }
export const productStatusLabel = { active: 'Live', draft: 'Draft', pending_review: 'In review', rejected: 'Rejected', paused: 'Paused' }
const P = {
  active: 'background:var(--tint);color:var(--forest)',
  draft: 'background:#EDEDE4;color:#3b4a3f',
  pending_review: 'background:var(--sun);color:#7A4A00',
  rejected: 'background:var(--danger-bg);color:var(--danger)',
  paused: 'background:#EDEDE4;color:#3b4a3f',
}
export const productPill = (s) => P[s] || P.draft
export const vOrderPill = (s) => (s === 'packed' ? 'background:#E8E3F8;color:#4A3A94' : orderPill(s))
export const payoutPill = (s) => ({ paid: 'background:var(--tint);color:var(--forest)', processing: 'background:#E1ECFB;color:#1D4E9E', scheduled: 'background:var(--sun);color:#7A4A00' }[s] || 'background:#EDEDE4;color:#3b4a3f')
// next status a vendor can move an order to
export const nextStatus = { pending: 'confirmed', confirmed: 'packed', packed: 'shipped' }
export const nextLabel = { confirmed: 'Confirm order', packed: 'Mark as packed', shipped: 'Mark as shipped' }
