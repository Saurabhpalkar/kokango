// Second photo per product (by slug), shown when a product card is hovered and as an extra image in the quick view.
// Files are in public/images/products/ (free Unsplash photos as stand-ins: replace with your own of the same name).
// A product that is not listed simply keeps its main photo on hover.
const SLUGS = [
  'moringa-powder', 'jackfruit-chips', 'curry-leaf-powder', 'jamun-vadi', 'tulsi-powder', 'neem-powder', 'beetroot-powder',
  'turmeric-powder', 'amba-poli', 'kokum-sarbat-mix', 'modak-mix', 'banana-chips',
]
export const HOVER_PHOTOS = Object.fromEntries(SLUGS.map((s) => [s, `/images/products/${s}-2.jpg`]))
export const hoverPhoto = (product) => (product && HOVER_PHOTOS[product.slug]) || ''
