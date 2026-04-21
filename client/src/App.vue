<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from './api'
import { useAuthStore } from './stores/auth'

const auth = useAuthStore()
const router = useRouter()
const userName = computed(() => auth.user?.name || auth.user?.email || 'User')
const unreadCount = computed(() => Number(auth.user?.notifications_unread_count || 0))

async function loadUnreadCount() {
  if (!auth.isAuthenticated) return
  try {
    const { data } = await api.get('/notifications/unread-count')
    auth.user = {
      ...(auth.user || {}),
      notifications_unread_count: Number(data.count || 0),
    }
  } catch {
    // ignore
  }
}

async function doLogout() {
  await auth.logout()
  router.push('/login')
}

watch(
  () => auth.isAuthenticated,
  async (isAuth) => {
    if (isAuth) await loadUnreadCount()
  },
)

onMounted(async () => {
  if (auth.isAuthenticated) await loadUnreadCount()
})
</script>

<template>
  <div class="app-shell">
    <header class="topbar">
      <div class="brand">Like-sprint</div>
      <nav class="menu">
        <RouterLink v-if="auth.user?.role === 'performer'" to="/performer/home">Кабинет исполнителя</RouterLink>
        <RouterLink v-if="auth.user?.role === 'performer'" to="/performer/tasks">Доступные задания</RouterLink>
        <RouterLink v-if="auth.user?.role === 'performer'" to="/performer/pending-submissions">Ожидают проверки</RouterLink>
        <RouterLink v-if="auth.user?.role === 'advertiser'" to="/advertiser/home">Кабинет рекламодателя</RouterLink>
        <RouterLink v-if="auth.user?.role === 'advertiser'" to="/advertiser/tasks">Мои задания</RouterLink>
        <RouterLink v-if="auth.user?.role === 'admin'" to="/admin/moderation">Модерация</RouterLink>
        <RouterLink to="/profile">Профиль</RouterLink>
        <RouterLink to="/finance">Финансы</RouterLink>
        <RouterLink to="/notifications">Уведомления ({{ unreadCount }})</RouterLink>
        <RouterLink to="/sessions">Сессии</RouterLink>
      </nav>
      <div class="auth-zone">
        <template v-if="auth.isAuthenticated">
          <span class="user">{{ userName }}</span>
          <button class="btn danger" @click="doLogout">Выход</button>
        </template>
        <template v-else>
          <RouterLink class="btn ghost" to="/login">Вход</RouterLink>
          <RouterLink class="btn" to="/register">Регистрация</RouterLink>
        </template>
      </div>
    </header>
    <main class="content">
      <RouterView />
    </main>
  </div>
</template>
