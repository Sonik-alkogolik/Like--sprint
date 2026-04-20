<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()
const error = ref('')
const form = reactive({
  name: '',
  email: '',
  role: 'performer',
  password: '',
  password_confirmation: '',
})

async function submit() {
  error.value = ''
  try {
    await auth.register(form)
    router.push('/profile')
  } catch (e) {
    const message = e?.response?.data?.message
    const errors = e?.response?.data?.errors
    error.value = message || (errors ? JSON.stringify(errors) : 'Ошибка регистрации')
  }
}
</script>

<template>
  <section class="card">
    <h1>Регистрация</h1>
    <label>Имя <input v-model="form.name" type="text" autocomplete="name" /></label>
    <label>Email <input v-model="form.email" type="email" autocomplete="email" /></label>
    <label>
      Роль
      <select v-model="form.role">
        <option value="performer">Исполнитель</option>
        <option value="advertiser">Рекламодатель</option>
      </select>
    </label>
    <label>Пароль <input v-model="form.password" type="password" autocomplete="new-password" /></label>
    <label>Подтверждение пароля <input v-model="form.password_confirmation" type="password" autocomplete="new-password" /></label>
    <button class="btn" :disabled="auth.loading" @click="submit">Создать аккаунт</button>
    <p v-if="error" class="error">{{ error }}</p>
  </section>
</template>

