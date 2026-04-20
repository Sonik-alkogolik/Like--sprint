<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../api'

const form = reactive({
  title: '',
  instruction: '',
  start_url: '',
  price_per_action: '0.05',
  commission_per_action: '0.01',
  max_approvals: '10',
  repeat_mode: 'one_time',
  repeat_interval_hours: '24',
  check_deadline_days: '3',
  verification_mode: 'manual',
})

const tasks = ref([])
const message = ref('')
const error = ref('')

async function loadTasks() {
  const { data } = await api.get('/advertiser/tasks')
  tasks.value = data.tasks
}

async function createTask() {
  message.value = ''
  error.value = ''
  try {
    await api.post('/advertiser/tasks', {
      ...form,
      price_per_action: Number(form.price_per_action),
      commission_per_action: Number(form.commission_per_action),
      max_approvals: Number(form.max_approvals),
      check_deadline_days: Number(form.check_deadline_days),
      repeat_interval_hours: form.repeat_mode === 'repeat_interval' ? Number(form.repeat_interval_hours) : null,
      requirements: [
        { kind: 'text', label: 'Отчёт в текстовом формате', is_required: true },
      ],
      links: form.start_url ? [{ url: form.start_url, label: 'Основная ссылка' }] : [],
    })
    message.value = 'Задание создано'
    await loadTasks()
  } catch (e) {
    error.value = e?.response?.data?.message || 'Ошибка создания задания'
  }
}

async function submitModeration(id) {
  await api.post(`/advertiser/tasks/${id}/submit-moderation`)
  await loadTasks()
}

async function launchTask(id) {
  try {
    await api.post(`/advertiser/tasks/${id}/launch`)
    await loadTasks()
  } catch (e) {
    error.value = e?.response?.data?.message || 'Ошибка запуска'
  }
}

async function pauseTask(id) {
  await api.post(`/advertiser/tasks/${id}/pause`)
  await loadTasks()
}

onMounted(loadTasks)
</script>

<template>
  <section class="card" data-testid="advertiser-tasks-page">
    <h2>Задания рекламодателя</h2>
    <p class="ok" v-if="message">{{ message }}</p>
    <p class="error" v-if="error">{{ error }}</p>

    <label>Заголовок <input v-model="form.title" type="text" /></label>
    <label>Инструкция <textarea v-model="form.instruction" rows="4" /></label>
    <label>Start URL <input v-model="form.start_url" type="url" /></label>
    <label>Цена <input v-model="form.price_per_action" type="number" min="0.01" step="0.01" /></label>
    <label>Комиссия <input v-model="form.commission_per_action" type="number" min="0" step="0.01" /></label>
    <label>Лимит <input v-model="form.max_approvals" type="number" min="1" /></label>
    <label>
      Repeat mode
      <select v-model="form.repeat_mode">
        <option value="one_time">one_time</option>
        <option value="repeat_after_review">repeat_after_review</option>
        <option value="repeat_interval">repeat_interval</option>
      </select>
    </label>
    <label v-if="form.repeat_mode === 'repeat_interval'">Repeat interval (hours) <input v-model="form.repeat_interval_hours" type="number" min="1" /></label>
    <label>
      Проверка
      <select v-model="form.verification_mode">
        <option value="manual">manual</option>
        <option value="auto_accept">auto_accept</option>
      </select>
    </label>
    <button class="btn" @click="createTask" data-testid="create-task-btn">Создать задание</button>

    <h3>Мои задания</h3>
    <div v-if="tasks.length === 0" class="muted">Нет заданий</div>
    <div v-for="task in tasks" :key="task.id" class="session" :data-testid="`task-row-${task.id}`">
      <div class="session-main">
        <strong>#{{ task.id }} {{ task.title }}</strong>
        <span class="muted">status: {{ task.status }} | moderation: {{ task.moderation_status }}</span>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;">
        <RouterLink class="btn ghost" :to="`/advertiser/tasks/${task.id}/reports`">Отчёты</RouterLink>
        <button class="btn ghost" @click="submitModeration(task.id)" :data-testid="`submit-moderation-${task.id}`" v-if="task.status === 'draft' || task.status === 'paused'">На модерацию</button>
        <button class="btn" @click="launchTask(task.id)" :data-testid="`launch-task-${task.id}`" v-if="task.status !== 'active'">Запустить</button>
        <button class="btn danger" @click="pauseTask(task.id)" :data-testid="`pause-task-${task.id}`" v-if="task.status === 'active'">Остановить</button>
      </div>
    </div>
  </section>
</template>
