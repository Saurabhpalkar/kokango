// Page copy (static text).
const SAMPLE = 'This is sample policy text. Replace it with your final wording before launch.'

const POLICIES = [
  {
    slug: 'shipping', nav: 'Shipping Policy', title: 'Shipping Policy', updated: '1 October 2026',
    intro: SAMPLE,
    sections: [
      { h: 'Delivery areas', p: ['We deliver to most pincodes across India. Enter your pincode on the product page or at checkout to confirm delivery is available for your area.'] },
      { h: 'Processing time', p: ['Orders are packed within 1 to 2 working days of payment confirmation. You will receive an email once your order is shipped.'] },
      { h: 'Delivery time and charges', p: ['Standard delivery takes 3 to 5 working days and express delivery takes 1 to 2 working days where available. The shipping charge is shown at checkout before you pay.'] },
      { h: 'Tracking your order', p: ['After dispatch you will receive a courier name and AWB number. You can follow your shipment from My Orders on this website.'] },
      { h: 'Damaged or missing parcels', p: ['If your parcel arrives damaged or incomplete, contact us within 48 hours with photos and your order number and we will help you right away.'] }
    ]
  },
  {
    slug: 'refund', nav: 'Refund & Cancellation', title: 'Refund & Cancellation Policy', updated: '1 October 2026',
    intro: SAMPLE,
    sections: [
      { h: 'Cancelling an order', p: ['You can cancel an order any time before it is dispatched. Write to us on WhatsApp or email with your order number and we will cancel it and start your refund.', 'Once an order has been handed to the courier it can no longer be cancelled.'] },
      { h: 'Damaged or incorrect items', p: ['If you receive a damaged, leaking or incorrect item, contact us within 48 hours of delivery. Please share clear photos of the product, the outer packaging and the shipping label along with your order number.', 'After we review the photos we will send a replacement or refund the affected item, whichever you prefer.'] },
      { h: 'How refunds are paid', p: ['Approved refunds are returned to your original payment method through Razorpay within 5 to 7 working days. Your bank may take a little longer to show the credit.'] },
      { h: 'Perishable food items', p: ['Because our snacks and powders are food products, we cannot accept returns of opened or unopened packs for change of mind. Refunds apply only to damaged, incorrect or missing items as described above.'] }
    ]
  },
  {
    slug: 'terms', nav: 'Terms & Conditions', title: 'Terms & Conditions', updated: '1 October 2026',
    intro: SAMPLE,
    sections: [
      { h: 'Use of this website', p: ['By browsing or ordering from this website you agree to these terms. You must provide accurate information and use the site only for lawful purposes.'] },
      { h: 'Orders and pricing', p: ['All prices are in Indian rupees and include applicable taxes unless stated otherwise. We may cancel an order if a product is out of stock or a price was listed in error, and any payment made will be refunded in full.'] },
      { h: 'Payments', p: ['Payments are processed securely by Razorpay. We do not store your card, UPI or netbanking details on our servers.'] },
      { h: 'Shipping', p: ['Delivery timelines are estimates and may be affected by courier delays, weather or events outside our control. Please see our Shipping Policy for details.'] },
      { h: 'Limitation of liability', p: ['To the extent permitted by law, Kokango is not liable for indirect or consequential losses arising from the use of this website or our products. Our total liability for any order will not exceed the amount you paid for it.'] },
      { h: 'Governing law', p: ['These terms are governed by the laws of India. Any dispute will be subject to the exclusive jurisdiction of the courts in Maharashtra, India.'] }
    ]
  },
  {
    slug: 'privacy', nav: 'Privacy Policy', title: 'Privacy Policy', updated: '1 October 2026',
    intro: SAMPLE,
    sections: [
      { h: 'What we collect', p: ['We collect the details you give us when you order or create an account: your name, email address, phone number and delivery address. We also collect basic usage data such as pages visited and device type.'] },
      { h: 'How we use it', p: ['We use your information to process and deliver orders, send order updates, answer your questions and improve our website. We only send marketing messages if you have agreed to receive them.'] },
      { h: 'Payments', p: ['Payments are handled by Razorpay. Your card, UPI and bank details are entered on their secure page and are never stored by Kokango.'] },
      { h: 'Cookies', p: ['We use cookies to keep your cart, remember your sign-in and understand how the site is used. You can block cookies in your browser settings, but some features may stop working.'] },
      { h: 'Sharing with courier partners', p: ['We share your name, phone number and delivery address with our courier partners so they can deliver your order. We do not sell your personal information.'] },
      { h: 'Your rights', p: ['You can ask to see, correct or delete the personal information we hold about you at any time, subject to legal record-keeping requirements.'] },
      { h: 'Contact us', p: ['For any privacy question, message us on WhatsApp or write to hello@kokango.example and we will respond within a few working days.'] }
    ]
  }
]

export function getPolicyData() {
  return { policies: POLICIES }
}

export function getPolicy(slug) {
  return POLICIES.find((p) => p.slug === slug) || null
}
