import api from '@/services/api'

export const list = async () => (await api.get('/addresses')).data.data
export const create = async (body) => (await api.post('/addresses', body)).data.data
export const update = async (id, body) => (await api.put(`/addresses/${id}`, body)).data.data
export const remove = async (id) => { await api.delete(`/addresses/${id}`) }
export const setDefault = async (id) => (await api.post(`/addresses/${id}/default`)).data.data
