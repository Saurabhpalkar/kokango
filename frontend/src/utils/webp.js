// WebP copies exist next to the JPG photos in public/images/site and public/images/products.
// Returns the .webp path for those, '' for anything else (uploads, external links).
export const webpOf = (src) => (typeof src === 'string' && /^\/images\/(site|products)\/[^?#]+\.jpe?g$/i.test(src) ? src.replace(/\.jpe?g$/i, '.webp') : '')
