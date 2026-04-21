<script setup>
import { onMounted, ref } from 'vue'
import api from '../api'

const tasks = ref([])
const overview = ref(null)
const comment = ref('approved by admin')
const disputes = ref([])
const disputeStatus = ref('open')
const disputeComment = ref('Решение администратора')
const disputeCompensation = ref(true)
const users = ref([])
const blockReason = ref('Suspicious behavior')
const fraudEvents = ref([])
const fraudSeverity = ref('')
const auditLogs = ref([])
const auditAction = ref('')
const blacklistItems = ref([])
const blacklistType = ref('email')
const blacklistValue = ref('')
const blacklistNote = ref('Manual admin block')
const error = ref('')

async function loadQueue() {
  error.value = ''
  try {
    const { data } = await api.get('/admin/tasks/moderation-queue')
    tasks.value = data.tasks
  } catch (e) {
    error.value = e?.response?.data?.message || 'Ошибка загрузки модерации'
  }
}

async function loadOverview() {
  const { data } = await api.get('/admin/overview')
  overview.value = data.overview || null
}

async function moderate(id, action) {
  await api.post(`/admin/tasks/${id}/moderate`, {
    action,
    comment: comment.value,
  })
  await loadQueue()
}

async function loadDisputes() {
  const { data } = await api.get('/admin/disputes', { params: { status: disputeStatus.value } })
  disputes.value = data.items || []
}

async function setDisputeStatus(id, status) {
  await api.post(`/admin/disputes/${id}/status`, {
    status,
    admin_comment: disputeComment.value,
    apply_compensation: disputeCompensation.value,
  })
  await loadDisputes()
  await loadFraudEvents()
  await loadAuditLogs()
}

async function loadUsers() {
  const { data } = await api.get('/admin/users')
  users.value = data.items || []
}

async function blockUser(id) {
  await api.post(`/admin/users/${id}/block`, { reason: blockReason.value })
  await loadUsers()
  await loadFraudEvents()
  await loadAuditLogs()
}

async function unblockUser(id) {
  await api.post(`/admin/users/${id}/unblock`)
  await loadUsers()
  await loadFraudEvents()
  await loadAuditLogs()
}

async function loadFraudEvents() {
  const params = fraudSeverity.value ? { severity: fraudSeverity.value } : {}
  const { data } = await api.get('/admin/fraud-events', { params })
  fraudEvents.value = data.items || []
}

async function loadAuditLogs() {
  const params = auditAction.value ? { action: auditAction.value } : {}
  const { data } = await api.get('/admin/audit-logs', { params })
  auditLogs.value = data.items || []
}

async function loadBlacklist() {
  const { data } = await api.get('/admin/blacklist')
  blacklistItems.value = data.items || []
}

async function addBlacklist() {
  await api.post('/admin/blacklist', {
    entry_type: blacklistType.value,
    entry_value: blacklistValue.value,
    note: blacklistNote.value,
  })
  blacklistValue.value = ''
  await loadBlacklist()
  await loadAuditLogs()
  await loadFraudEvents()
}

async function deactivateBlacklist(id) {
  await api.post(`/admin/blacklist/${id}/deactivate`)
  await loadBlacklist()
  await loadAuditLogs()
}

onMounted(async () => {
  await loadOverview()
  await loadQueue()
  await loadDisputes()
  await loadUsers()
  await loadFraudEvents()
  await loadAuditLogs()
  await loadBlacklist()
})
</script>

<template>
  <section class="card" data-testid="admin-moderation-page">
    <h2>Admin Control Center</h2>
    <p class="error" v-if="error">{{ error }}</p>

    <div class="card" v-if="overview" data-testid="admin-overview">
      <h3>Overview</h3>
      <div class="admin-overview-grid">
        <div class="session" data-testid="overview-users-total"><div class="session-main"><strong>Users total</strong><span class="muted">{{ overview.users_total }}</span></div></div>
        <div class="session" data-testid="overview-users-blocked"><div class="session-main"><strong>Users blocked</strong><span class="muted">{{ overview.users_blocked }}</span></div></div>
        <div class="session" data-testid="overview-tasks-active"><div class="session-main"><strong>Tasks active</strong><span class="muted">{{ overview.tasks_active }}</span></div></div>
        <div class="session" data-testid="overview-tasks-pending"><div class="session-main"><strong>Tasks pending moderation</strong><span class="muted">{{ overview.tasks_pending_moderation }}</span></div></div>
        <div class="session" data-testid="overview-disputes-open"><div class="session-main"><strong>Disputes open</strong><span class="muted">{{ overview.disputes_open }}</span></div></div>
        <div class="session" data-testid="overview-fraud-high"><div class="session-main"><strong>Fraud high 24h</strong><span class="muted">{{ overview.fraud_events_high_24h }}</span></div></div>
        <div class="session" data-testid="overview-audit-24h"><div class="session-main"><strong>Audit logs 24h</strong><span class="muted">{{ overview.audit_logs_24h }}</span></div></div>
        <div class="session" data-testid="overview-blacklist-active"><div class="session-main"><strong>Blacklist active</strong><span class="muted">{{ overview.blacklist_active }}</span></div></div>
        <div class="session" data-testid="overview-notify-pending"><div class="session-main"><strong>Notify queue pending</strong><span class="muted">{{ overview.notification_queue_pending }}</span></div></div>
      </div>
    </div>

    <div class="admin-grid">
      <div class="card">
        <h3>Модерация заданий</h3>
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
      </div>

      <div class="card">
        <h3>Очередь споров</h3>
        <label>
          Статус
          <select v-model="disputeStatus" @change="loadDisputes">
            <option value="open">open</option>
            <option value="in_review">in_review</option>
            <option value="resolved_for_performer">resolved_for_performer</option>
            <option value="resolved_for_advertiser">resolved_for_advertiser</option>
          </select>
        </label>
        <label>Комментарий <input v-model="disputeComment" type="text" /></label>
        <label style="flex-direction:row;align-items:center;gap:8px;">
          <input type="checkbox" v-model="disputeCompensation" />
          Применять финкоррекцию при решении в пользу исполнителя
        </label>
        <div v-if="disputes.length === 0" class="muted">Споров в этой выборке нет</div>
        <div v-for="item in disputes" :key="item.id" class="session" :data-testid="`dispute-row-${item.id}`">
          <div class="session-main">
            <strong>Dispute #{{ item.id }} / submission #{{ item.submission_id }}</strong>
            <span class="muted">{{ item.reason }}</span>
            <span class="muted" v-if="item.compensation_applied">Компенсация: ${{ Number(item.compensation_amount || 0).toFixed(4) }}</span>
          </div>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <button class="btn ghost" @click="setDisputeStatus(item.id, 'in_review')" :data-testid="`dispute-in-review-${item.id}`">В работу</button>
            <button class="btn" @click="setDisputeStatus(item.id, 'resolved_for_performer')" :data-testid="`dispute-performer-${item.id}`">В пользу исполнителя</button>
            <button class="btn danger" @click="setDisputeStatus(item.id, 'resolved_for_advertiser')" :data-testid="`dispute-advertiser-${item.id}`">В пользу рекламодателя</button>
          </div>
        </div>
      </div>

      <div class="card">
        <h3>Пользователи</h3>
        <label>Причина блокировки <input v-model="blockReason" type="text" /></label>
        <div v-for="u in users" :key="u.id" class="session" :data-testid="`admin-user-${u.id}`">
          <div class="session-main">
            <strong>#{{ u.id }} {{ u.email }}</strong>
            <span class="muted">{{ u.role }} • blocked: {{ u.is_blocked ? 'yes' : 'no' }}</span>
          </div>
          <div style="display:flex;gap:6px;">
            <button class="btn danger" v-if="!u.is_blocked" @click="blockUser(u.id)" :data-testid="`block-user-${u.id}`">Блок</button>
            <button class="btn ghost" v-else @click="unblockUser(u.id)" :data-testid="`unblock-user-${u.id}`">Разблок</button>
          </div>
        </div>
      </div>

      <div class="card">
        <h3>Fraud Events</h3>
        <label>
          Severity
          <select v-model="fraudSeverity" @change="loadFraudEvents">
            <option value="">all</option>
            <option value="low">low</option>
            <option value="medium">medium</option>
            <option value="high">high</option>
          </select>
        </label>
        <div v-if="fraudEvents.length === 0" class="muted">Событий пока нет</div>
        <div v-for="ev in fraudEvents" :key="ev.id" class="session" :data-testid="`fraud-event-${ev.id}`">
          <div class="session-main">
            <strong>{{ ev.event_type }} ({{ ev.severity }})</strong>
            <span class="muted">{{ ev.message || 'без сообщения' }}</span>
          </div>
        </div>
      </div>

      <div class="card">
        <h3>Audit Log</h3>
        <label>
          Action
          <input v-model="auditAction" type="text" @change="loadAuditLogs" placeholder="admin_user_blocked" />
        </label>
        <div v-if="auditLogs.length === 0" class="muted">Логов пока нет</div>
        <div v-for="log in auditLogs" :key="log.id" class="session" :data-testid="`audit-log-${log.id}`">
          <div class="session-main">
            <strong>{{ log.action }}</strong>
            <span class="muted">{{ log.entity_type }} #{{ log.entity_id }}</span>
            <span class="muted">actor #{{ log.actor_user_id || 'system' }}</span>
          </div>
        </div>
      </div>

      <div class="card">
        <h3>Blacklist</h3>
        <label>
          Type
          <select v-model="blacklistType" data-testid="blacklist-type">
            <option value="email">email</option>
            <option value="ip">ip</option>
          </select>
        </label>
        <label>
          Value
          <input v-model="blacklistValue" type="text" data-testid="blacklist-value" placeholder="blocked@example.com" />
        </label>
        <label>
          Note
          <input v-model="blacklistNote" type="text" data-testid="blacklist-note" />
        </label>
        <button class="btn danger" @click="addBlacklist" data-testid="add-blacklist-btn">Добавить в blacklist</button>

        <div v-if="blacklistItems.length === 0" class="muted">Blacklist пуст</div>
        <div v-for="item in blacklistItems" :key="item.id" class="session" :data-testid="`blacklist-row-${item.id}`">
          <div class="session-main">
            <strong>{{ item.entry_type }}: {{ item.entry_value }}</strong>
            <span class="muted">{{ item.note || 'без заметки' }}</span>
          </div>
          <button class="btn ghost" @click="deactivateBlacklist(item.id)" data-testid="deactivate-blacklist-btn">Деактивировать</button>
        </div>
      </div>
    </div>
  </section>
</template>
