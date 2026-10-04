import api from '@/services/api'

/** -> { pincode, serviceable, city, state, methods:[{code,label,price_paise,days}] } */
export const serviceability = async (pincode) => (await api.post('/shipping/serviceability', { pincode })).data.data
