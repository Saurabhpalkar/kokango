// About page text and photos. Edit the words here; the layout is in views/public/AboutView.vue.
// Photos are in public/images/site/ (free Unsplash photos used as stand-ins: replace them with your own).

export const ABOUT_HERO = {
  kicker: 'Our story',
  title: 'Rooted in the Konkan, made for your kitchen',
  text: 'Kokango brings the snacks, sweets and herbal powders of the Konkan coast to homes across India: made in small batches, with simple ingredients, and sealed fresh.',
  photos: [
    { src: '/images/site/about-sunset.jpg', alt: 'Coconut palms at sunset', caption: 'The Konkan coast' },
    { src: '/images/site/about-field.jpg', alt: 'A smiling farmer in a green field', caption: 'Local farmers' },
    { src: '/images/site/about-market.jpg', alt: 'Fresh jamun at a market stall', caption: 'Fresh fruit' },
    { src: '/images/site/about-women.jpg', alt: 'Women sorting fresh leaves', caption: 'Sorted by hand' },
  ],
}

// The story in three chapters (image left / right in turn)
export const CHAPTERS = [
  {
    image: '/images/site/about-coast.jpg',
    kicker: 'Where it begins',
    title: 'The coast we come from',
    text: 'Between the Sahyadri hills and the Arabian Sea, the Konkan grows jackfruit, jamun, mango, coconut and leafy herbs in its monsoon-fed soil. Every Kokango product starts with this land and its seasons.',
  },
  {
    image: '/images/site/story-hands.jpg',
    kicker: 'How it is made',
    title: 'Recipes from Konkan homes',
    text: 'Our chips, sweets and powders follow the way families here have always made them: sun-dried, slow-cooked or stone-ground, in small batches, with nothing added that you would not use in your own kitchen.',
  },
  {
    image: '/images/site/story-spices.jpg',
    kicker: 'How it reaches you',
    title: 'Made fresh, sent with care',
    text: 'Each batch is checked by hand and packed in a resealable pouch the same week, so the aroma and crunch are still there when it reaches your door, anywhere in India.',
  },
]

// What we stand for (icon names match ICONS in AboutView.vue)
export const VALUES = [
  { icon: 'batch', title: 'Small batches', text: 'Made in small quantities so every pack stays fresh and full of flavour.' },
  { icon: 'leaf', title: 'Simple ingredients', text: 'No unnecessary additives. Just leaves, roots and fruit prepared with care.' },
  { icon: 'pack', title: 'Honest packing', text: 'Resealable pouches that protect aroma and taste until you open them.' },
  { icon: 'truck', title: 'Delivered with care', text: 'Tracked shipping across India, with updates until it reaches your door.' },
  { icon: 'hands', title: 'Local makers', text: 'We work with farmers and makers from the Konkan, close to where the food grows.' },
  { icon: 'heart', title: 'Made to be shared', text: 'Snacks for tea time, sweets for festivals, powders for everyday wellness.' },
]

export const QUOTE = {
  image: '/images/site/about-river.jpg',
  text: 'Food from the Konkan should taste like it was made at home this morning.',
  by: 'The Kokango promise',
}

export const GALLERY = [
  { src: '/images/site/about-farm.jpg', alt: 'Women picking leaves in a field' },
  { src: '/images/site/about-mango.jpg', alt: 'Ripe mango cut into cubes' },
  { src: '/images/site/story-grove.jpg', alt: 'A coconut grove' },
  { src: '/images/site/about-sunset.jpg', alt: 'Palms at sunset' },
  { src: '/images/site/cat-powders.jpg', alt: 'Spoons of herbal powders' },
  { src: '/images/site/about-coast.jpg', alt: 'Green fields under monsoon clouds' },
]

// kept for older code that still asks for it
export function getAboutData() {
  return { values: VALUES.map((v, i) => ({ n: String(i + 1), bg: i % 2 ? '#EAF3DD' : '#FFF1CC', title: v.title, text: v.text })) }
}
