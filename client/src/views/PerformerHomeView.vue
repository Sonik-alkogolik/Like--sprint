<script setup>
import { onMounted, ref } from 'vue'
import api from '../api'

const message = ref('')
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await api.get('/performer/home')
    message.value = data.message
  } catch (e) {
    error.value = e?.response?.data?.message || 'Ошибка доступа к кабинету исполнителя'
  }
})
</script>

<template>
  <section class="card">
    <h1>Кабинет исполнителя</h1>
    <p v-if="message" class="ok">{{ message }}</p>
    <p v-if="error" class="error">{{ error }}</p>
  </section>
</template>

