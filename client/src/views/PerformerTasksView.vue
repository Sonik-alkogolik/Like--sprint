<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const tasks = ref([])
const error = ref('')

async function loadAvailable() {
  error.value = ''
  try {
    const { data } = await api.get('/performer/tasks/available')
    tasks.value = data.tasks
  } catch (e) {
    error.value = e?.response?.data?.message || 'Не удалось загрузить задания'
  }
}

async function takeTask(task) {
  error.value = ''
  try {
    const { data } = await api.post(`/performer/tasks/${task.id}/take`)
    if (data.start_url) {
      window.open(data.start_url, '_blank', 'noopener')
    }
    router.push(`/performer/assignments/${data.assignment.id}`)
  } catch (e) {
    error.value = e?.response?.data?.message || 'Не удалось взять задание'
  }
}

onMounted(loadAvailable)
</script>

<template>
  <section class="card" data-testid="performer-tasks-page">
    <h2>Доступные задания</h2>
    <p class="error" v-if="error">{{ error }}</p>
    <div v-if="tasks.length === 0" class="muted">Пока нет доступных заданий</div>
    <div v-for="task in tasks" :key="task.id" class="session" :data-testid="`available-task-${task.id}`">
      <div class="session-main">
        <strong>#{{ task.id }} {{ task.title }}</strong>
        <span class="muted">{{ task.price_per_action }} USD • {{ task.repeat_mode }} • SLA {{ task.check_deadline_days }} дн.</span>
      </div>
      <button class="btn" @click="takeTask(task)" :data-testid="`take-task-${task.id}`">Начать выполнение</button>
    </div>
  </section>
</template>