// Home page content (text, photos, video). Edit the words and paths here; the layout lives in HomeView.vue.
// Photos are in public/images/site/ (free Unsplash photos used as stand-ins: replace them with your own
// photos of the same name, or change the paths). Product packs and prices always come from the shop's API.

export const HERO_SLIDES = [
  {
    id: 'konkan',
    image: '/images/site/hero-konkan.jpg',
    imageMobile: '/images/site/hero-konkan-m.jpg',
    kicker: 'Fresh from the Konkan',
    title: 'The taste of the Konkan, delivered home',
    text: 'Traditional snacks, sweets and herbal powders, made in small batches.',
    cta: { label: 'Shop now', to: '/products' },
    packs: ['jackfruit-chips', 'moringa-powder', 'jamun-vadi'],
  },
  {
    id: 'powders',
    image: '/images/site/hero-powders.jpg',
    imageMobile: '/images/site/hero-powders-m.jpg',
    kicker: 'Herbal powders',
    title: 'Pure leaf powders for everyday wellness',
    text: 'Moringa, curry leaf, tulsi, neem and beetroot, dried and ground fresh.',
    cta: { label: 'Shop herbal powders', to: '/products?category=powders' },
    packs: ['moringa-powder', 'curry-leaf-powder', 'tulsi-powder'],
  },
  {
    id: 'sweets',
    image: '/images/site/hero-sweets.jpg',
    imageMobile: '/images/site/hero-sweets-m.jpg',
    kicker: 'Konkan sweets',
    title: 'Sweet traditions, made the old way',
    text: 'Jamun vadi and Konkan favourites, chewy, fruity and handmade.',
    cta: { label: 'Shop sweets', to: '/products?category=sweets' },
    packs: ['jamun-vadi'],
  },
]

// Round photos for "Shop by category" (by category slug). Unknown slugs fall back to a product pack photo.
export const CATEGORY_PHOTOS = {
  powders: '/images/site/cat-powders.jpg',
  snacks: '/images/site/cat-snacks.jpg',
  sweets: '/images/site/cat-sweets.jpg',
}

// Small trust strip under the banner.
export const PROMISES = [
  { icon: 'leaf', title: 'Fresh small batches', text: 'Made and sealed with care' },
  { icon: 'truck', title: 'Delivery in 3–5 days', text: 'Tracked, across India' },
  { icon: 'cash', title: 'Cash on Delivery', text: 'Pay when it arrives' },
  { icon: 'shield', title: 'Secure payments', text: 'UPI, cards via Razorpay' },
]

// "About Kokango" teaser (links to the About page).
export const ABOUT = {
  image: '/images/site/about-farm.jpg',
  // fanned out like playing cards (components/PhotoDeck.vue)
  photos: [
    { src: '/images/site/about-field.jpg', alt: 'A smiling farmer in a green field', caption: 'Local farmers' },
    { src: '/images/site/about-women.jpg', alt: 'Women sorting fresh leaves', caption: 'Sorted by hand' },
    { src: '/images/site/about-farm.jpg', alt: 'Women picking leaves in a field', caption: 'Picked in season' },
    { src: '/images/site/about-market.jpg', alt: 'Fresh jamun at a market stall', caption: 'Fresh fruit' },
    { src: '/images/site/about-mango.jpg', alt: 'Ripe mango cut into cubes', caption: 'Konkan flavours' },
  ],
  kicker: 'About Kokango',
  title: 'Konkan flavours, the way they are made at home',
  text: [
    'Kokango brings the snacks, sweets and herbal powders of the Konkan coast to homes across India.',
    'We work in small batches with local makers, use simple ingredients and pack everything in resealable pouches, so what reaches you tastes like it was made that morning.',
  ],
  points: ['Recipes from Konkan homes', 'Simple, honest ingredients', 'Packed fresh, delivered fast'],
  cta: { label: 'Read our story', to: '/about' },
}

// "From Konkan farms to your door" steps.
export const STEPS = [
  { icon: 'sprout', title: 'Picked in season', text: 'Leaves, fruit and roots from Konkan farms, at their best.' },
  { icon: 'pot', title: 'Made in small batches', text: 'Dried, ground or cooked the traditional way.' },
  { icon: 'pack', title: 'Sealed fresh', text: 'Resealable pouches keep the aroma and taste in.' },
  { icon: 'truck', title: 'Delivered to you', text: 'Tracked delivery across India in 3–5 days.' },
]

// Customer reviews. SAMPLE TEXT: replace with real reviews from your customers before going live
// (or empty the list to hide the section).
export const TESTIMONIALS = [
  { name: 'Priya S.', city: 'Pune', rating: 5, text: 'The jackfruit chips taste exactly like the ones from my grandmother’s village. Crisp and not oily at all.' },
  { name: 'Rahul D.', city: 'Mumbai', rating: 5, text: 'I add the moringa powder to my morning smoothie every day. Fresh smell, fine powder and quick delivery.' },
  { name: 'Anjali K.', city: 'Bengaluru', rating: 4, text: 'Jamun vadi was a lovely surprise for the family. Nicely packed and it arrived in four days.' },
]

// Home page FAQ teaser: the first questions of content/faq.js are shown; this is how many.
export const FAQ_COUNT = 4

// The coloured band of figures. Replace with your own real numbers.
export const STATS = [
  { value: '100%', label: 'Natural ingredients' },
  { value: '3–5 days', label: 'Delivery across India' },
  { value: 'Small batch', label: 'Made fresh, sealed fresh' },
]

// "Rooted in the Konkan" story section.
export const STORY = {
  title: 'Rooted in nature, made in the Konkan',
  text: 'Every Kokango pack starts on the Konkan coast: local fruit and leaves, family recipes and careful hands.',
  // Full-width background film (muted loop). Stand-in clip from Mixkit (free licence): replace with your own footage.
  // /videos/story.mp4 is the Kokango product slideshow, if you prefer that here.
  video: { mp4: '/videos/konkan-story.mp4', poster: '/images/site/story-poster.jpg' },
  cta: { label: 'Shop the Konkan', to: '/products' },
  points: [
    { image: '/images/site/story-coast.jpg', title: 'Authentic', text: 'Recipes from Konkan homes, made the way they always have been.' },
    { image: '/images/site/story-hands.jpg', title: 'Local hands', text: 'Prepared in small batches with local makers and farmers.' },
    { image: '/images/site/story-grove.jpg', title: 'Farm fresh', text: 'Fruit and leaves picked in season, dried and packed quickly.' },
    { image: '/images/site/story-spices.jpg', title: 'Nothing harsh', text: 'No artificial colours or flavours; just the ingredients on the pack.' },
  ],
}
