import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ProfileView from '../views/ProfileView.vue'
import SessionsView from '../views/SessionsView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/profile' },
    { path: '/login', component: LoginView, meta: { guest: true } },
    { path: '/register', component: RegisterView, meta: { guest: true } },
    { path: '/profile', component: ProfileView, meta: { auth: true } },
    { path: '/sessions', component: SessionsView, meta: { auth: true } },
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
  return true
})

export default router

