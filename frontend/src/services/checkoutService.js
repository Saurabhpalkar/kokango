import api from '@/services/api'

export const quote = async (shippingMethod) => (await api.post('/checkout/quote', { shipping_method: shippingMethod })).data.data
/** body: { customer?, address | address_id, shipping_method, notes? } -> { order, payment } */
export const placeOrder = async (body) => (await api.post('/checkout', body)).data.data
/** body: { order_no, token?, gateway_order_id, gateway_payment_id, signature } -> Order */
export const verify = async (body) => (await api.post('/payments/verify', body)).data.data
export const failed = async (body) => (await api.post('/payments/failed', body)).data
