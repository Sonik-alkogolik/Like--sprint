<script setup>
import { onMounted, ref } from 'vue'
import api from '../api'

const tasks = ref([])

async function loadAvailable() {
  const { data } = await api.get('/performer/tasks/available')
  tasks.value = data.tasks
}

onMounted(loadAvailable)
</script>

<template>
  <section class="card" data-testid="performer-tasks-page">
    <h2>Доступные задания</h2>
    <div v-if="tasks.length === 0" class="muted">Пока нет доступных заданий</div>
    <div v-for="task in tasks" :key="task.id" class="session" :data-testid="`available-task-${task.id}`">
      <div class="session-main">
        <strong>#{{ task.id }} {{ task.title }}</strong>
        <span class="muted">{{ task.price_per_action }} USD • {{ task.repeat_mode }}</span>
      </div>
    </div>
  </section>
</template>
