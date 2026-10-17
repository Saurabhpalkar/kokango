// Look-ups shared by the admin marketplace screens.
export { label, initial } from './adminUi'
export { productPill, productStatusLabel, payoutPill } from '@/views/vendor/vendorUi'
const V = {
  approved: 'background:var(--tint);color:var(--forest)',
  pending: 'background:var(--sun);color:#7A4A00',
  suspended: 'background:#EDEDE4;color:#3b4a3f',
  rejected: 'background:var(--danger-bg);color:var(--danger)',
}
export const vendorPill = (s) => V[s] || V.pending
export const vendorStatusLabel = { approved: 'Approved', pending: 'Pending review', suspended: 'Suspended', rejected: 'Rejected' }
