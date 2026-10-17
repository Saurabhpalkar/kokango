import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useVendorStore } from '@/stores/vendor'

const routes = [
  { path: '/', component: () => import('@/views/public/HomeView.vue') },
  { path: '/products', component: () => import('@/views/public/ProductListView.vue') },
  { path: '/product', redirect: '/product/moringa-powder' },
  { path: '/product/:slug', component: () => import('@/views/public/ProductDetailView.vue') },
  { path: '/about', component: () => import('@/views/public/AboutView.vue') },
  { path: '/faq', component: () => import('@/views/public/FaqView.vue') },
  { path: '/policy', redirect: '/policy/shipping' },
  { path: '/policy/:slug', component: () => import('@/views/public/PolicyView.vue') },
  { path: '/order-confirmation', component: () => import('@/views/customer/OrderConfirmationView.vue') },
  { path: '/forgot-password', component: () => import('@/views/auth/ForgotPasswordView.vue') },
  { path: '/login', component: () => import('@/views/auth/LoginView.vue') },
  { path: '/cart', component: () => import('@/views/customer/CartView.vue') },
  { path: '/wishlist', component: () => import('@/views/customer/WishlistView.vue') },
  { path: '/checkout', component: () => import('@/views/customer/CheckoutView.vue') },
  { path: '/orders', component: () => import('@/views/customer/OrderTrackingView.vue') },
  { path: '/account', component: () => import('@/views/customer/ProfileView.vue') },
  { path: '/admin/login', component: () => import('@/views/admin/AdminLoginView.vue') },
  { path: '/admin', component: () => import('@/views/admin/DashboardView.vue') },
  { path: '/admin/marketplace', component: () => import('@/views/admin/MarketplaceView.vue') },
  { path: '/admin/vendors', component: () => import('@/views/admin/VendorsView.vue') },
  { path: '/admin/vendors/new', component: () => import('@/views/admin/VendorFormView.vue') },
  { path: '/admin/vendors/:id', component: () => import('@/views/admin/VendorDetailView.vue') },
  { path: '/admin/approvals', component: () => import('@/views/admin/ProductApprovalsView.vue') },
  { path: '/admin/vendor-payouts', component: () => import('@/views/admin/VendorPayoutsView.vue') },
  { path: '/admin/products', component: () => import('@/views/admin/ProductListView.vue') },
  { path: '/admin/orders', component: () => import('@/views/admin/OrderListView.vue') },
  { path: '/admin/coupons', component: () => import('@/views/admin/CouponsView.vue') },
  { path: '/admin/reviews', component: () => import('@/views/admin/ReviewsView.vue') },
  { path: '/admin/products/new', component: () => import('@/views/admin/ProductFormView.vue') },
  { path: '/admin/products/:id', component: () => import('@/views/admin/ProductFormView.vue') },
  { path: '/admin/categories', component: () => import('@/views/admin/CategoriesView.vue') },
  { path: '/admin/inventory', component: () => import('@/views/admin/InventoryView.vue') },
  { path: '/admin/payments', component: () => import('@/views/admin/PaymentsView.vue') },
  { path: '/admin/shipments', component: () => import('@/views/admin/ShipmentsView.vue') },
  { path: '/admin/customers', component: () => import('@/views/admin/CustomersView.vue') },
  { path: '/admin/settings', component: () => import('@/views/admin/SettingsView.vue') },
  { path: '/track-order', component: () => import('@/views/customer/TrackOrderView.vue') },
  { path: '/reset-password', component: () => import('@/views/auth/ResetPasswordView.vue') },
  { path: '/admin/users', component: () => import('@/views/admin/UsersView.vue') },
  { path: '/vendor/login', component: () => import('@/views/vendor/VendorLoginView.vue') },
  { path: '/vendor/register', component: () => import('@/views/vendor/VendorRegisterView.vue') },
  { path: '/vendor', component: () => import('@/views/vendor/VendorDashboardView.vue') },
  { path: '/vendor/products', component: () => import('@/views/vendor/VendorProductsView.vue') },
  { path: '/vendor/products/new', component: () => import('@/views/vendor/VendorProductFormView.vue') },
  { path: '/vendor/products/:id', component: () => import('@/views/vendor/VendorProductFormView.vue') },
  { path: '/vendor/inventory', component: () => import('@/views/vendor/VendorInventoryView.vue') },
  { path: '/vendor/orders', component: () => import('@/views/vendor/VendorOrdersView.vue') },
  { path: '/vendor/payouts', component: () => import('@/views/vendor/VendorPayoutsView.vue') },
  { path: '/vendor/store', component: () => import('@/views/vendor/VendorStoreView.vue') },
  { path: '/:pathMatch(.*)*', component: () => import('@/views/public/NotFoundView.vue') },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  // in-page links stop below the sticky header instead of sliding underneath it (vue-router ignores CSS scroll-padding)
  scrollBehavior: (to, from, saved) => saved || (to.hash ? { el: to.hash, top: window.innerWidth >= 900 ? 124 : 76, behavior: 'smooth' } : { top: 0 }),
})

// Guards: customer pages need a login; the admin panel needs the admin role (the API enforces it too).
router.beforeEach(async (to) => {
  const auth = useAuthStore()
  await auth.init()
  if (to.path.startsWith('/admin') && to.path !== '/admin/login' && !auth.isAdmin) {
    return { path: '/admin/login', query: { redirect: to.fullPath } }
  }
  if (to.path.startsWith('/vendor')) {
    const vendor = useVendorStore()
    await vendor.init()
    const open = ['/vendor/login', '/vendor/register'].includes(to.path)
    if (!open && !vendor.isVendor) return { path: '/vendor/login', query: { redirect: to.fullPath } }
  }
  if (['/account', '/orders'].includes(to.path) && !auth.isLoggedIn) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }
})

export default router
