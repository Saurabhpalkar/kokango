// Page copy (static text).
export function getFaqData() {
  return { faqs: [
      { open: true, q: 'How long does delivery take?', a: 'Most orders arrive within 3 to 5 working days. Express delivery of 1 to 2 days is available for selected pincodes.' },
      { open: false, q: 'Can I order without creating an account?', a: 'Yes. You can check out as a guest. Creating an account lets you save addresses and view your order history.' },
      { open: false, q: 'Which payment methods do you accept?', a: 'We accept UPI, debit and credit cards, netbanking and wallets through Razorpay.' },
      { open: false, q: 'How can I track my order?', a: 'Open My Orders after logging in, or use the tracking link and AWB number sent to you after your order ships.' },
      { open: false, q: 'What is your return and refund policy?', a: 'If your pack arrives damaged or incorrect, contact us within 48 hours with a photo and we will arrange a replacement or refund.' },
      { open: false, q: 'How should I store the powders?', a: 'Keep the pouch sealed in a cool, dry place away from direct sunlight and use a dry spoon each time.' }
    ] };
}
