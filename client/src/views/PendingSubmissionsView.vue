<script setup>
import { onMounted, ref } from 'vue'
import api from '../api'

const sort = ref('date')
const items = ref([])
const total = ref(0)
const error = ref('')

async function loadItems() {
  error.value = ''
  try {
    const { data } = await api.get('/performer/submissions/pending', { params: { sort: sort.value } })
    items.value = data.items
    total.value = Number(data.total_sum || 0)
  } catch (e) {
    error.value = e?.response?.data?.message || 'Не удалось загрузить список'
  }
}

onMounted(loadItems)
</script>

<template>
  <section class="card" data-testid="pending-submissions-page">
    <h2>Отчёты, ожидающие проверки работодателем</h2>
    <p class="error" v-if="error">{{ error }}</p>

    <label>
      Сортировка
      <select v-model="sort" @change="loadItems">
        <option value="date">По дате отчёта</option>
        <option value="price">По цене</option>
      </select>
    </label>

    <div class="form-title"><b>{{ items.length }}</b> заданий на сумму <b>${{ total.toFixed(2) }}</b></div>

    <div v-if="items.length === 0" class="muted">Пусто</div>
    <div v-for="item in items" :key="item.id" class="session">
      <div class="session-main">
        <strong>#{{ item.task?.id }} {{ item.task?.title }}</strong>
        <span class="muted">На проверке до: {{ item.review_deadline_at }}</span>
      </div>
      <div><strong>${{ Number(item.task?.price_per_action || 0).toFixed(4) }}</strong></div>
    </div>
  </section>
</template>