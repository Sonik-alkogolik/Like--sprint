<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const items = ref([])
const unreadOnly = ref(false)
const error = ref('')
const queueResult = ref(null)
const queueStats = ref(null)

const unreadCount = computed(() => items.value.filter((x) => !x.read_at).length)

async function loadItems() {
  error.value = ''
  try {
    const { data } = await api.get('/notifications', { params: { unread: unreadOnly.value } })
    items.value = data.items || []
    if (auth.user) {
      auth.user.notifications_unread_count = items.value.filter((x) => !x.read_at).length
    }
  } catch (e) {
    error.value = e?.response?.data?.message || 'Не удалось загрузить уведомления'
  }
}

async function markRead(id) {
  await api.post(`/notifications/${id}/read`)
  await loadItems()
}

async function markAllRead() {
  await api.post('/notifications/read-all')
  await loadItems()
}

async function loadQueueStats() {
  if (auth.user?.role !== 'admin') return
  const { data } = await api.get('/admin/notifications/stats')
  queueStats.value = data.stats
}

async function dispatchQueue() {
  const { data } = await api.post('/admin/notifications/dispatch', { limit: 200 })
  queueResult.value = data.result
  await loadQueueStats()
}

onMounted(async () => {
  await loadItems()
  await loadQueueStats()
})
</script>

<template>
  <section class="card" data-testid="notifications-page">
    <h2>Уведомления</h2>
    <p class="error" v-if="error">{{ error }}</p>

    <label>
      <input type="checkbox" v-model="unreadOnly" @change="loadItems" />
      Только непрочитанные
    </label>

    <div class="form-title">Непрочитанных: <b>{{ unreadCount }}</b></div>
    <button class="btn ghost" @click="markAllRead" :disabled="items.length === 0" data-testid="mark-all-read-btn">Отметить всё прочитанным</button>

    <div v-if="auth.user?.role === 'admin'" class="card">
      <strong>Очередь уведомлений (admin)</strong>
      <div class="muted" v-if="queueStats">
        Pending: {{ queueStats.pending }} | Sent: {{ queueStats.sent }} | Failed: {{ queueStats.failed }}
      </div>
      <button class="btn" @click="dispatchQueue" data-testid="dispatch-notification-queue-btn">Отправить очередь сейчас</button>
      <div class="ok" v-if="queueResult">
        Processed: {{ queueResult.processed }}, sent: {{ queueResult.sent }}, failed: {{ queueResult.failed }}
      </div>
    </div>

    <div v-if="items.length === 0" class="muted">Пока уведомлений нет</div>
    <div v-for="item in items" :key="item.id" class="session" :data-testid="`notification-${item.id}`">
      <div class="session-main">
        <strong>{{ item.title }}</strong>
        <span class="muted">{{ item.message }}</span>
        <span class="muted">{{ item.created_at }}</span>
      </div>
      <button class="btn" v-if="!item.read_at" @click="markRead(item.id)" :data-testid="`mark-read-${item.id}`">Прочитано</button>
      <span class="ok" v-else>Прочитано</span>
    </div>
  </section>
</template>
