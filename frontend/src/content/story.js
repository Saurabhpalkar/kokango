// Storytelling configuration (static, frontend-only).
// To change the homepage film or chapter pictures, edit the paths here.
// Files live in the project's public/ folder (public/videos, public/images).
//
// VIDEO: the homepage film. Supply an optimised landscape clip (1920x1080, H.264 MP4, ideally under
//   8 MB, 15-30 s, looped; an optional WebM is used first by browsers that support it). Leave both
//   empty to show the poster image only. The film never autoplays sound.
// IMAGES: photographs shown in the story chapters. Leave `src` empty to fall back to simple abstract artwork.
export const STORY = {
  video: {
    mp4: '/videos/story.mp4',
    webm: '',
    poster: '/images/story-poster.png',
  },
  // HERO: looping background clip behind the first hero slide (muted, no sound). Keep it short (6-12 s) and
  // under about 3 MB. This is a temporary stand-in clip (MDN sample video), NOT Kokango footage:
  // replace `mp4` with your own file, e.g. '/videos/hero.mp4'. Leave empty to show the poster only.
  hero: {
    mp4: 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
    webm: 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.webm',
    poster: 'https://picsum.photos/seed/kokango-hero/1920/1080',
  },
  // Temporary stand-in photos (picsum.photos serves random sample photographs; they are NOT Konkan or Kokango).
  // Replace each `src` with your own photo, e.g. '/images/story/land.jpg', when you have them.
  land: { src: 'https://picsum.photos/seed/kokango-land/1920/1080', alt: 'The Konkan coast' },
  people: { src: 'https://picsum.photos/seed/kokango-people/900/1125', alt: 'Traditional food preparation' },
  memories: { src: 'https://picsum.photos/seed/kokango-memories/1920/1080', alt: 'A family meal' },
}

// Photos for category cards and collection banners (homepage).
// Add your own per category slug, e.g. { snacks: '/images/categories/snacks.jpg' }.
// Anything not listed uses a temporary stand-in photo from picsum.photos (random sample photo, not Kokango).
export const CATEGORY_PHOTOS = {}
export const categoryPhoto = (slug, w = 900, h = 1100) =>
  CATEGORY_PHOTOS[slug] || `https://picsum.photos/seed/kokango-cat-${encodeURIComponent(slug)}/${w}/${h}`
