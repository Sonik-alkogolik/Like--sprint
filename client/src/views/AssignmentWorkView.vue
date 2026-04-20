<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'

const route = useRoute()
const assignment = ref(null)
const form = reactive({ report_text: '', attachment_url: '' })
const message = ref('')
const error = ref('')

async function loadAssignment() {
  error.value = ''
  const { data } = await api.get(`/performer/assignments/${route.params.id}`)
  assignment.value = data.assignment
  form.report_text = data.assignment.submission?.report_text || ''
  form.attachment_url = data.assignment.submission?.attachments?.[0]?.file_url || ''
}

async function submitReport() {
  message.value = ''
  error.value = ''
  try {
    await api.post(`/performer/assignments/${route.params.id}/submit`, {
      report_text: form.report_text,
      attachments: form.attachment_url ? [{ attachment_type: 'screenshot', file_url: form.attachment_url }] : [],
    })
    message.value = 'Спасибо, ваш отчёт принят! Осталось дождаться проверки.'
    await loadAssignment()
  } catch (e) {
    error.value = e?.response?.data?.message || 'Ошибка отправки отчёта'
  }
}

async function cancelAssignment() {
  message.value = ''
  error.value = ''
  try {
    await api.post(`/performer/assignments/${route.params.id}/cancel`)
    message.value = 'Выполнение отменено'
    await loadAssignment()
  } catch (e) {
    error.value = e?.response?.data?.message || 'Ошибка отмены'
  }
}

onMounted(loadAssignment)
</script>

<template>
  <section class="card" v-if="assignment" data-testid="assignment-work-page">
    <h2>Оплачиваемое задание</h2>
    <div>
      <strong>#{{ assignment.task.id }} {{ assignment.task.title }}</strong>
    </div>
    <div class="muted">Статус: {{ assignment.status }}</div>
    <div class="muted">Дедлайн: {{ assignment.deadline_at }}</div>

    <div class="card" style="margin-top:12px;">
      <div><strong>Описание</strong></div>
      <div>{{ assignment.task.instruction }}</div>
      <a v-if="assignment.task.start_url" :href="assignment.task.start_url" target="_blank" rel="noopener">Открыть ссылку задания</a>
    </div>

    <p class="ok" v-if="message">{{ message }}</p>
    <p class="error" v-if="error">{{ error }}</p>

    <label>
      Текст отчёта
      <textarea v-model="form.report_text" rows="5" />
    </label>
    <label>
      Ссылка на вложение (опционально)
      <input v-model="form.attachment_url" type="url" />
    </label>

    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button class="btn" @click="submitReport" data-testid="submit-report-btn">Отправить отчёт</button>
      <button class="btn ghost" @click="submitReport" v-if="assignment.status === 'submitted' || assignment.status === 'rework_requested'">Изменить отчёт</button>
      <button class="btn danger" @click="cancelAssignment" v-if="assignment.status !== 'approved' && assignment.status !== 'rejected' && assignment.status !== 'cancelled'">Отказаться от выполнения</button>
    </div>

    <div v-if="assignment.submission" class="card" style="margin-top:12px;">
      <div><strong>Подробности отчёта</strong></div>
      <div class="muted">Статус отчёта: {{ assignment.submission.status }}</div>
      <div class="muted" v-if="assignment.submission.review_deadline_at">Максимальный срок проверки: {{ assignment.submission.review_deadline_at }}</div>
      <div class="error" v-if="assignment.submission.rework_comment">Комментарий на доработку: {{ assignment.submission.rework_comment }}</div>
    </div>
  </section>
</template>