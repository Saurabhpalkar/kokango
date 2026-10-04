import axios from 'axios'

// One axios client for the whole app. Base URL comes from frontend/.env (VITE_API_BASE_URL).
export const TOKEN_KEY = 'kokango_token'
export const CART_TOKEN_KEY = 'kokango_cart_token'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
  headers: { Accept: 'application/json' },
  timeout: 20000,
})

api.interceptors.request.use((config) => {
  try {
    const token = localStorage.getItem(TOKEN_KEY)
    if (token) config.headers.Authorization = `Bearer ${token}`
    const cartToken = localStorage.getItem(CART_TOKEN_KEY)
    if (cartToken) config.headers['X-Cart-Token'] = cartToken
  } catch (e) { /* storage blocked */ }
  return config
})

// A 401 on a request that sent a token means the login expired: forget it.
api.interceptors.response.use(
  (res) => res,
  (error) => {
    if (error.response?.status === 401 && localStorage.getItem(TOKEN_KEY)) {
      localStorage.removeItem(TOKEN_KEY)
      window.dispatchEvent(new CustomEvent('kokango:unauthorized'))
    }
    return Promise.reject(error)
  },
)

/** Turn an axios error into { message, errors } for forms. errors = { field: 'first message' } */
export function apiError(error, fallback = 'Something went wrong. Please try again.') {
  const data = error?.response?.data
  const errors = {}
  if (data?.errors) for (const [k, v] of Object.entries(data.errors)) errors[k] = Array.isArray(v) ? v[0] : String(v)
  if (!error?.response) return { message: 'Cannot reach the server. Check your internet or that the API is running.', errors }
  return { message: data?.message || fallback, errors, status: error.response.status }
}

export default api
