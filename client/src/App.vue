<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'

const auth = useAuthStore()
const router = useRouter()
const userName = computed(() => auth.user?.name || auth.user?.email || 'User')

async function doLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="app-shell">
    <header class="topbar">
      <div class="brand">Like-sprint</div>
      <nav class="menu">
        <RouterLink v-if="auth.user?.role === 'performer'" to="/performer/home">Кабинет исполнителя</RouterLink>
        <RouterLink v-if="auth.user?.role === 'advertiser'" to="/advertiser/home">Кабинет рекламодателя</RouterLink>
        <RouterLink to="/profile">Профиль</RouterLink>
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
