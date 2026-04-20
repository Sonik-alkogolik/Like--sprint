<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'

const route = useRoute()
const task = ref(null)
const items = ref([])
const count = ref(0)
const comment = ref('Нужно доработать отчёт')
const error = ref('')

async function loadReports() {
  error.value = ''
  try {
    const { data } = await api.get(`/advertiser/tasks/${route.params.id}/reports/pending`)
    task.value = data.task
    items.value = data.items
    count.value = data.count
  } catch (e) {
    error.value = e?.response?.data?.message || 'Не удалось загрузить отчёты'
  }
}

async function approve(itemId) {
  await api.post(`/advertiser/submissions/${itemId}/approve`)
  await loadReports()
}

async function reject(itemId) {
  await api.post(`/advertiser/submissions/${itemId}/reject`)
  await loadReports()
}

async function rework(itemId) {
  await api.post(`/advertiser/submissions/${itemId}/rework`, { comment: comment.value })
  await loadReports()
}

async function approveAll() {
  await api.post(`/advertiser/tasks/${route.params.id}/reports/mass-approve`, { all: true })
  await loadReports()
}

onMounted(loadReports)
</script>

<template>
  <section class="card" data-testid="advertiser-reports-page">
    <h2>Проверка отчётов</h2>
    <p class="error" v-if="error">{{ error }}</p>

    <div v-if="task" class="card">
      <strong>#{{ task.id }} {{ task.title }}</strong>
      <div class="muted">Условие задания: {{ task.instruction }}</div>
      <div class="muted">Отчётов на проверке: {{ count }}</div>
    </div>

    <label>
      Комментарий на доработку
      <input v-model="comment" type="text" />
    </label>
    <button class="btn" @click="approveAll" :disabled="count === 0" data-testid="approve-all-btn">Принять все</button>

    <div v-if="items.length === 0" class="muted">Отчётов на проверке нет</div>
    <div v-for="item in items" :key="item.id" class="session" :data-testid="`report-row-${item.id}`">
      <div class="session-main">
        <strong>Submission #{{ item.id }} от пользователя #{{ item.performer_id }}</strong>
        <span class="muted">{{ item.report_text }}</span>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <button class="btn" @click="approve(item.id)" :data-testid="`approve-${item.id}`">Принять</button>
        <button class="btn ghost" @click="rework(item.id)" :data-testid="`rework-${item.id}`">Вернуть на доработку</button>
        <button class="btn danger" @click="reject(item.id)" :data-testid="`reject-${item.id}`">Отклонить</button>
      </div>
    </div>
  </section>
</template>