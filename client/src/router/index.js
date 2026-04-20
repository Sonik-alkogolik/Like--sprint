import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ProfileView from '../views/ProfileView.vue'
import SessionsView from '../views/SessionsView.vue'
import PerformerHomeView from '../views/PerformerHomeView.vue'
import AdvertiserHomeView from '../views/AdvertiserHomeView.vue'
import FinanceView from '../views/FinanceView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/profile' },
    { path: '/login', component: LoginView, meta: { guest: true } },
    { path: '/register', component: RegisterView, meta: { guest: true } },
    { path: '/profile', component: ProfileView, meta: { auth: true } },
    { path: '/sessions', component: SessionsView, meta: { auth: true } },
    { path: '/finance', component: FinanceView, meta: { auth: true } },
    { path: '/performer/home', component: PerformerHomeView, meta: { auth: true, role: 'performer' } },
    { path: '/advertiser/home', component: AdvertiserHomeView, meta: { auth: true, role: 'advertiser' } },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  auth.init()
  if (auth.isAuthenticated && !auth.user) {
    try {
      await auth.fetchMe()
    } catch {
      await auth.logout()
    }
  }

  if (to.meta.auth && !auth.isAuthenticated) return '/login'
  if (to.meta.guest && auth.isAuthenticated) return '/profile'
  if (to.meta.role && auth.user?.role !== to.meta.role) return '/profile'
  return true
})

export default router
