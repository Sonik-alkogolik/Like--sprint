<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()
const error = ref('')
const form = reactive({
  email: '',
  password: '',
})

async function submit() {
  error.value = ''
  try {
    await auth.login(form)
    const rolePath = auth.user?.role === 'advertiser'
      ? '/advertiser/home'
      : auth.user?.role === 'admin'
        ? '/admin/moderation'
        : '/performer/home'
    router.push(rolePath)
  } catch (e) {
    error.value = e?.response?.data?.message || 'Ошибка входа'
  }
}
</script>

<template>
  <section class="card">
    <h1>Вход</h1>
    <label>Email <input v-model="form.email" type="email" autocomplete="email" /></label>
    <label>Пароль <input v-model="form.password" type="password" autocomplete="current-password" /></label>
    <button class="btn" :disabled="auth.loading" @click="submit">Войти</button>
    <p v-if="error" class="error">{{ error }}</p>
  </section>
</template>
