<script setup>
import { onMounted, ref } from 'vue'
import api from '../api'

const tasks = ref([])
const comment = ref('approved by admin')

async function loadQueue() {
  const { data } = await api.get('/admin/tasks/moderation-queue')
  tasks.value = data.tasks
}

async function moderate(id, action) {
  await api.post(`/admin/tasks/${id}/moderate`, {
    action,
    comment: comment.value,
  })
  await loadQueue()
}

onMounted(loadQueue)
</script>

<template>
  <section class="card" data-testid="admin-moderation-page">
    <h2>Модерация заданий</h2>
    <label>Комментарий <input v-model="comment" type="text" /></label>
    <div v-if="tasks.length === 0" class="muted">Очередь пустая</div>
    <div v-for="task in tasks" :key="task.id" class="session" :data-testid="`moderation-row-${task.id}`">
      <div class="session-main">
        <strong>#{{ task.id }} {{ task.title }}</strong>
        <span class="muted">{{ task.moderation_status }}</span>
      </div>
      <div style="display:flex;gap:6px;">
        <button class="btn" @click="moderate(task.id, 'approve')" :data-testid="`approve-task-${task.id}`">Принять</button>
        <button class="btn danger" @click="moderate(task.id, 'reject')" :data-testid="`reject-task-${task.id}`">Отклонить</button>
      </div>
    </div>
  </section>
</template>
