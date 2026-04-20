<script setup>
import { onMounted, ref } from 'vue'
import api from '../api'

const sessions = ref([])
const error = ref('')

async function load() {
  error.value = ''
  try {
    const { data } = await api.get('/sessions')
    sessions.value = data.sessions || []
  } catch (e) {
    error.value = e?.response?.data?.message || 'Не удалось загрузить сессии'
  }
}

async function revoke(id) {
  await api.post(`/sessions/${id}/revoke`)
  await load()
}

onMounted(load)
</script>

<template>
  <section class="card">
    <h1>Сессии</h1>
    <p v-if="error" class="error">{{ error }}</p>
    <div v-for="s in sessions" :key="s.id" class="session">
      <div class="session-main">
        <div><strong>ID:</strong> {{ s.id }}</div>
        <div><strong>IP:</strong> {{ s.ip_address || 'n/a' }}</div>
        <div><strong>Last seen:</strong> {{ s.last_seen_at || 'n/a' }}</div>
        <div><strong>Current:</strong> {{ s.is_current ? 'yes' : 'no' }}</div>
      </div>
      <button class="btn danger" :disabled="s.is_current || s.revoked_at" @click="revoke(s.id)">
        Revoke
      </button>
    </div>
  </section>
</template>

