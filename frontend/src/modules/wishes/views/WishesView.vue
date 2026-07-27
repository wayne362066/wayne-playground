<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '../../auth/stores/authStore'
import { useWishStore } from '../stores/wishStore'

const wishStore = useWishStore()
const authStore = useAuthStore()
const { items, loading, saving, error, managementMode } = storeToRefs(wishStore)

const editingId = ref('')
const statusFilter = ref('all')
const form = reactive(emptyForm())

const categories = {
  feature: '新功能',
  improvement: '改善',
  content: '內容',
  experiment: '實驗',
  other: '其他',
}

const statuses = {
  submitted: '已收到',
  reviewing: '評估中',
  planned: '已規劃',
  in_progress: '製作中',
  completed: '已完成',
  rejected: '暫不處理',
  archived: '已封存',
}

const moderationStatuses = {
  pending: '待審核',
  approved: '公開',
  hidden: '已隱藏',
}

const visibilities = {
  public: '公開',
  unlisted: '不列出',
  private: '私人',
}

const filteredItems = computed(() => {
  if (statusFilter.value === 'all') return items.value
  return items.value.filter((wish) => wish.status === statusFilter.value)
})

const hasCardActions = computed(() => [
  'wishes.update',
  'wishes.status.update',
  'wishes.moderate',
  'wishes.archive',
  'wishes.restore',
].some((permission) => authStore.can(permission)))

function emptyForm() {
  return {
    title: '',
    description: '',
    category: 'feature',
    author_type: 'guest',
    author_name: '',
    status: 'submitted',
    moderation_status: 'approved',
    visibility: 'public',
  }
}

function resetForm() {
  Object.assign(form, emptyForm())
  editingId.value = ''
}

function editWish(wish) {
  editingId.value = wish.id
  Object.assign(form, {
    title: wish.title,
    description: wish.description || '',
    category: wish.category,
    author_type: wish.author.type,
    author_name: wish.author.name || '',
    status: wish.status,
    moderation_status: wish.moderation_status,
    visibility: wish.visibility,
  })
  document.querySelector('#wish-form')?.scrollIntoView({ behavior: 'smooth' })
}

async function submitWish() {
  const basePayload = {
    title: form.title,
    description: form.description || null,
    category: form.category,
    author_type: form.author_type,
    author_name: form.author_type === 'anonymous' ? null : form.author_name,
  }

  const updatePayload = { ...basePayload }

  if (authStore.can('wishes.status.update')) {
    updatePayload.status = form.status
  }

  if (authStore.can('wishes.moderate')) {
    updatePayload.moderation_status = form.moderation_status
    updatePayload.visibility = form.visibility
  }

  const succeeded = editingId.value
    ? await wishStore.update(editingId.value, updatePayload)
    : await wishStore.create(basePayload)

  if (succeeded) resetForm()
}

async function toggleManagement() {
  if (!authStore.can('wishes.manage.view')) return

  resetForm()
  statusFilter.value = 'all'
  await wishStore.load(!managementMode.value)
}

async function changeStatus(wish, status) {
  await wishStore.update(wish.id, { status })
}

async function toggleHidden(wish) {
  await wishStore.update(wish.id, {
    moderation_status: wish.moderation_status === 'hidden' ? 'approved' : 'hidden',
  })
}

async function archiveWish(wish) {
  if (window.confirm(`確定要將「${wish.title}」移至封存區嗎？之後仍可恢復。`)) {
    await wishStore.remove(wish.id)
  }
}

function authorLabel(wish) {
  return wish.author.type === 'anonymous'
    ? '匿名'
    : wish.author.name || '具名訪客'
}

function formatDate(value) {
  return new Intl.DateTimeFormat('zh-TW', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(value))
}

onMounted(() => wishStore.load(false))
</script>

<template>
  <section class="wish-hero">
    <div class="container hero-layout">
      <div>
        <p class="eyebrow">Wish board / Permission aware</p>
        <h1 class="page-title">下一個想看見的，<br>先把它記下來。</h1>
      </div>
      <div class="hero-copy">
        <p class="page-lead">
          留下一個功能、改善或實驗方向。公開投稿送出後會立即顯示，管理操作則依帳號權限開放。
        </p>
        <button
          v-if="authStore.can('wishes.manage.view')"
          class="mode-button"
          type="button"
          @click="toggleManagement"
        >
          {{ managementMode ? '返回公開列表' : '管理檢視' }}
        </button>
      </div>
    </div>
  </section>

  <section class="container wish-workspace">
    <form
      v-if="authStore.can('wishes.create') || editingId"
      id="wish-form"
      class="panel wish-form"
      @submit.prevent="submitWish"
    >
      <div class="form-heading">
        <div>
          <p class="eyebrow">{{ editingId ? 'Edit wish' : 'Make a wish' }}</p>
          <h2>{{ editingId ? '編輯這個願望' : '我希望這裡有⋯⋯' }}</h2>
        </div>
        <button v-if="editingId" class="text-button" type="button" @click="resetForm">
          取消編輯
        </button>
      </div>

      <label>
        願望標題
        <input
          v-model.trim="form.title"
          maxlength="120"
          placeholder="例如：我想要一個可以記錄書單的功能"
          required
        >
      </label>

      <label>
        詳細說明
        <textarea
          v-model.trim="form.description"
          maxlength="5000"
          rows="5"
          placeholder="可以補充使用情境，或為什麼會想要這個功能。"
        />
      </label>

      <div class="form-grid">
        <label>
          分類
          <select v-model="form.category">
            <option v-for="(label, value) in categories" :key="value" :value="value">
              {{ label }}
            </option>
          </select>
        </label>
        <label>
          投稿身份
          <select v-model="form.author_type">
            <option value="guest">顯示暱稱</option>
            <option value="anonymous">匿名</option>
          </select>
        </label>
        <label v-if="form.author_type === 'guest'">
          暱稱
          <input v-model.trim="form.author_name" maxlength="80" placeholder="怎麼稱呼你？" required>
        </label>
      </div>

      <div v-if="editingId" class="form-grid management-fields">
        <label v-if="authStore.can('wishes.status.update')">
          規劃狀態
          <select v-model="form.status">
            <option v-for="(label, value) in statuses" :key="value" :value="value">
              {{ label }}
            </option>
          </select>
        </label>
        <label v-if="authStore.can('wishes.moderate')">
          審核狀態
          <select v-model="form.moderation_status">
            <option v-for="(label, value) in moderationStatuses" :key="value" :value="value">
              {{ label }}
            </option>
          </select>
        </label>
        <label v-if="authStore.can('wishes.moderate')">
          可見性
          <select v-model="form.visibility">
            <option v-for="(label, value) in visibilities" :key="value" :value="value">
              {{ label }}
            </option>
          </select>
        </label>
      </div>

      <div class="form-footer">
        <p>目前不收集 Email、電話或其他聯絡資料。</p>
        <button class="primary-button" type="submit" :disabled="saving">
          {{ saving ? '儲存中…' : editingId ? '儲存變更' : '送出願望' }}
        </button>
      </div>
    </form>

    <div class="board-heading">
      <div>
        <p class="eyebrow">{{ managementMode ? 'Management view' : 'Public wishes' }}</p>
        <h2>{{ managementMode ? '全部紀錄' : '大家想要什麼？' }}</h2>
      </div>
      <label class="filter">
        <span>狀態</span>
        <select v-model="statusFilter">
          <option value="all">全部</option>
          <option v-for="(label, value) in statuses" :key="value" :value="value">
            {{ label }}
          </option>
        </select>
      </label>
    </div>

    <div v-if="error" class="notice error">
      {{ error }}
      <button type="button" @click="wishStore.load()">重新載入</button>
    </div>
    <div v-else-if="loading" class="notice" aria-live="polite">正在整理願望…</div>
    <div v-else-if="filteredItems.length === 0" class="empty-state">
      <span aria-hidden="true">✦</span>
      <h3>這裡還很安靜</h3>
      <p>成為第一個留下願望的人吧。</p>
    </div>

    <div v-else class="wish-list">
      <article
        v-for="wish in filteredItems"
        :key="wish.id"
        class="wish-card"
        :class="{ deleted: wish.is_deleted }"
      >
        <div class="wish-card-main">
          <div class="wish-meta">
            <span class="category">{{ categories[wish.category] }}</span>
            <span class="status" :class="wish.status">{{ statuses[wish.status] }}</span>
            <span v-if="managementMode && wish.moderation_status !== 'approved'" class="visibility">
              {{ moderationStatuses[wish.moderation_status] }}
            </span>
            <span v-if="wish.is_deleted" class="visibility">已軟刪除</span>
          </div>
          <h3>{{ wish.title }}</h3>
          <p v-if="wish.description" class="description">{{ wish.description }}</p>
          <div class="byline">
            <span>{{ authorLabel(wish) }}</span>
            <span aria-hidden="true">·</span>
            <time :datetime="wish.created_at">{{ formatDate(wish.created_at) }}</time>
          </div>
        </div>

        <div v-if="managementMode" class="card-management">
          <label v-if="!wish.is_deleted && authStore.can('wishes.status.update')">
            <span>狀態</span>
            <select
              :value="wish.status"
              :disabled="saving"
              @change="changeStatus(wish, $event.target.value)"
            >
              <option v-for="(label, value) in statuses" :key="value" :value="value">
                {{ label }}
              </option>
            </select>
          </label>
          <div v-if="hasCardActions" class="card-actions">
            <button
              v-if="!wish.is_deleted && authStore.can('wishes.update')"
              type="button"
              @click="editWish(wish)"
            >
              編輯
            </button>
            <button
              v-if="!wish.is_deleted && authStore.can('wishes.moderate')"
              type="button"
              @click="toggleHidden(wish)"
            >
              {{ wish.moderation_status === 'hidden' ? '恢復公開' : '隱藏' }}
            </button>
            <button
              v-if="!wish.is_deleted && authStore.can('wishes.archive')"
              class="danger"
              type="button"
              @click="archiveWish(wish)"
            >
              軟刪除
            </button>
            <button
              v-if="wish.is_deleted && authStore.can('wishes.restore')"
              type="button"
              @click="wishStore.restore(wish.id)"
            >
              恢復
            </button>
          </div>
          <details v-if="authStore.can('wishes.history.view') && wish.events?.length">
            <summary>變更紀錄（{{ wish.events.length }}）</summary>
            <ul>
              <li v-for="(event, index) in wish.events" :key="`${event.created_at}-${index}`">
                {{ event.type }}
                <span v-if="event.from || event.to">：{{ event.from || '—' }} → {{ event.to || '—' }}</span>
              </li>
            </ul>
          </details>
        </div>
      </article>
    </div>
  </section>
</template>

<style scoped>
.wish-hero {
  padding: 96px 0 80px;
  border-bottom: 1px solid var(--border);
}

.hero-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(300px, 0.65fr);
  align-items: end;
  gap: 72px;
}

.hero-copy {
  display: grid;
  justify-items: start;
  gap: 24px;
}

.hero-copy .page-lead {
  margin: 0;
}

.mode-button,
.primary-button {
  border: 1px solid var(--accent);
  border-radius: 999px;
  color: var(--accent-contrast);
  background: var(--accent);
  font-weight: 800;
}

.mode-button {
  padding: 11px 17px;
}

.wish-workspace {
  padding-block: 64px 112px;
}

.wish-form {
  padding: 30px;
}

.form-heading,
.form-footer,
.board-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

.form-heading {
  margin-bottom: 26px;
}

.form-heading h2,
.board-heading h2 {
  margin: 0;
  font-size: clamp(1.7rem, 4vw, 2.45rem);
  letter-spacing: -0.04em;
}

.wish-form > label,
.form-grid label,
.card-management label {
  display: grid;
  gap: 8px;
  color: var(--text-muted);
  font-size: 0.84rem;
  font-weight: 750;
}

.wish-form > label + label {
  margin-top: 18px;
}

input,
textarea,
select {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--text);
  background: var(--bg-elevated);
}

input,
select {
  min-height: 44px;
  padding: 0 12px;
}

textarea {
  padding: 12px;
  resize: vertical;
  line-height: 1.6;
}

.form-grid {
  margin-top: 18px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.management-fields {
  padding-top: 18px;
  border-top: 1px solid var(--border);
}

.form-footer {
  margin-top: 24px;
  padding-top: 22px;
  border-top: 1px solid var(--border);
}

.form-footer p {
  margin: 0;
  color: var(--text-faint);
  font-size: 0.8rem;
}

.primary-button {
  min-width: 132px;
  padding: 12px 20px;
}

.primary-button:disabled {
  cursor: wait;
  opacity: 0.55;
}

.text-button,
.card-actions button,
.notice button {
  border: 0;
  color: var(--accent);
  background: transparent;
  font-weight: 800;
}

.board-heading {
  margin-top: 72px;
  margin-bottom: 24px;
  align-items: end;
}

.filter {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-faint);
  font-size: 0.8rem;
  font-weight: 750;
}

.filter select {
  width: auto;
}

.wish-list {
  display: grid;
  gap: 14px;
}

.wish-card {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(260px, 0.42fr);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  overflow: hidden;
}

.wish-card.deleted {
  opacity: 0.68;
}

.wish-card-main {
  padding: 26px;
}

.wish-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.wish-meta span {
  padding: 5px 8px;
  border-radius: 999px;
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.68rem;
  font-weight: 800;
}

.category {
  color: var(--accent);
  background: var(--accent-soft);
}

.status,
.visibility {
  color: var(--text-muted);
  background: var(--bg-subtle);
}

.status.completed {
  color: var(--success);
}

.status.in_progress,
.status.planned {
  color: var(--warning);
  background: var(--warning-soft);
}

.wish-card h3 {
  margin: 18px 0 0;
  font-size: clamp(1.35rem, 3vw, 1.85rem);
  letter-spacing: -0.03em;
}

.description {
  margin: 12px 0 0;
  color: var(--text-muted);
  line-height: 1.75;
  white-space: pre-wrap;
}

.byline {
  margin-top: 24px;
  display: flex;
  gap: 8px;
  color: var(--text-faint);
  font-size: 0.8rem;
}

.card-management {
  padding: 24px;
  border-left: 1px solid var(--border);
  background: var(--bg-subtle);
}

.card-actions {
  margin-top: 18px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.card-actions button {
  padding: 0;
}

.card-actions .danger {
  color: var(--danger);
}

details {
  margin-top: 18px;
  color: var(--text-faint);
  font-size: 0.76rem;
}

summary {
  cursor: pointer;
  font-weight: 750;
}

details ul {
  margin: 10px 0 0;
  padding-left: 18px;
}

.empty-state {
  padding: 72px 24px;
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius-lg);
  text-align: center;
}

.empty-state span {
  color: var(--accent);
  font-size: 2rem;
}

.empty-state h3 {
  margin: 14px 0 6px;
}

.empty-state p {
  margin: 0;
  color: var(--text-muted);
}

@media (max-width: 800px) {
  .wish-hero {
    padding: 72px 0 64px;
  }

  .hero-layout,
  .wish-card {
    grid-template-columns: 1fr;
    gap: 28px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .card-management {
    border-top: 1px solid var(--border);
    border-left: 0;
  }
}

@media (max-width: 560px) {
  .wish-form {
    padding: 22px;
  }

  .form-heading,
  .form-footer,
  .board-heading {
    align-items: stretch;
    flex-direction: column;
  }

  .primary-button {
    width: 100%;
  }
}
</style>
