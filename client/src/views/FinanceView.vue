<script setup>
import { onMounted, ref } from 'vue'
import api from '../api'

const wallet = ref({ available_balance: 0, hold_balance: 0, currency: 'USD' })
const ledger = ref([])
const withdrawals = ref([])
const depositAmount = ref('10.00')
const withdrawalAmount = ref('2.00')
const requisites = ref('4111111111111111')
const loading = ref(false)
const message = ref('')
const error = ref('')

async function loadData() {
  loading.value = true
  error.value = ''
  try {
    const [w, l, wd] = await Promise.all([
      api.get('/finance/wallet'),
      api.get('/finance/ledger'),
      api.get('/finance/withdrawals'),
    ])
    wallet.value = w.data.wallet
    ledger.value = l.data.entries
    withdrawals.value = wd.data.withdrawals
  } catch (e) {
    error.value = e?.response?.data?.message || 'Не удалось загрузить финансы'
  } finally {
    loading.value = false
  }
}

async function simulateDeposit() {
  message.value = ''
  error.value = ''
  try {
    await api.post('/finance/deposits/simulate', {
      amount: Number(depositAmount.value),
      provider: 'yookassa',
    })
    message.value = 'Тестовое пополнение проведено'
    await loadData()
  } catch (e) {
    error.value = e?.response?.data?.message || 'Ошибка пополнения'
  }
}

async function createWithdrawal() {
  message.value = ''
  error.value = ''
  try {
    await api.post('/finance/withdrawals', {
      amount: Number(withdrawalAmount.value),
      payout_method: 'card',
      requisites: requisites.value,
    })
    message.value = 'Заявка на вывод создана'
    await loadData()
  } catch (e) {
    error.value = e?.response?.data?.message || 'Ошибка вывода'
  }
}

onMounted(loadData)
</script>

<template>
  <section class="card" data-testid="finance-page">
    <h2>Финансы</h2>
    <p class="muted" v-if="loading">Загрузка...</p>
    <p class="error" v-if="error">{{ error }}</p>
    <p class="ok" v-if="message">{{ message }}</p>

    <div>
      <strong>Доступно:</strong>
      <span data-testid="wallet-available">{{ Number(wallet.available_balance).toFixed(4) }}</span>
      {{ wallet.currency }}
    </div>
    <div>
      <strong>В холде:</strong>
      <span data-testid="wallet-hold">{{ Number(wallet.hold_balance).toFixed(4) }}</span>
      {{ wallet.currency }}
    </div>

    <label>
      Тестовое пополнение
      <input v-model="depositAmount" type="number" min="0.01" step="0.01" />
    </label>
    <button class="btn" @click="simulateDeposit" data-testid="deposit-btn">Пополнить тестовый баланс</button>

    <label>
      Сумма вывода
      <input v-model="withdrawalAmount" type="number" min="0.01" step="0.01" />
    </label>
    <label>
      Реквизиты карты
      <input v-model="requisites" type="text" />
    </label>
    <button class="btn ghost" @click="createWithdrawal" data-testid="withdraw-btn">Создать заявку на вывод</button>

    <h3>Последние проводки</h3>
    <div v-if="ledger.length === 0" class="muted">Проводок пока нет</div>
    <div v-for="entry in ledger" :key="entry.id" class="session">
      <div class="session-main">
        <strong>{{ entry.entry_type }}</strong>
        <span class="muted">{{ entry.created_at }}</span>
      </div>
      <div>
        <strong>{{ Number(entry.amount).toFixed(4) }}</strong>
      </div>
    </div>

    <h3>Выводы</h3>
    <div v-if="withdrawals.length === 0" class="muted">Выводов пока нет</div>
    <div v-for="item in withdrawals" :key="item.id" class="session">
      <div class="session-main">
        <strong>#{{ item.id }} — {{ item.status }}</strong>
        <span class="muted">{{ item.requisites }}</span>
      </div>
      <div>
        <strong>{{ Number(item.amount).toFixed(4) }}</strong>
      </div>
    </div>
  </section>
</template>