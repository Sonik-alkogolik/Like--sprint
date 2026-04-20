<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '../api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const done = ref('')
const error = ref('')
const form = reactive({
  username: '',
  display_name: '',
  about: '',
  payout_wallet: '',
})

onMounted(async () => {
  await auth.fetchMe()
  form.username = auth.user?.profile?.username || ''
  form.display_name = auth.user?.profile?.display_name || ''
  form.about = auth.user?.profile?.about || ''
  form.payout_wallet = auth.user?.profile?.payout_wallet || ''
})

async function save() {
  done.value = ''
  error.value = ''
  try {
    await api.put('/profile', form)
    await auth.fetchMe()
    done.value = 'Профиль сохранен'
  } catch (e) {
    error.value = e?.response?.data?.message || JSON.stringify(e?.response?.data?.errors || {}) || 'Ошибка'
  }
}
</script>

<template>
  <section class="card">
    <h1>Профиль</h1>
    <p class="muted">Роль: <strong>{{ auth.user?.role }}</strong></p>
    <label>Username <input v-model="form.username" type="text" /></label>
    <label>Display name <input v-model="form.display_name" type="text" /></label>
    <label>О себе <textarea v-model="form.about" rows="3" /></label>
    <label>Кошелек для выплат <input v-model="form.payout_wallet" type="text" /></label>
    <button class="btn" @click="save">Сохранить</button>
    <p v-if="done" class="ok">{{ done }}</p>
    <p v-if="error" class="error">{{ error }}</p>
  </section>
</template>

