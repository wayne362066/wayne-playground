<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '../../auth/stores/authStore'
import PermissionChecklist from '../components/PermissionChecklist.vue'
import { useAccessStore } from '../stores/accessStore'

const accessStore = useAccessStore()
const authStore = useAuthStore()
const {
  roles,
  permissions,
  users,
  audits,
  loading,
  saving,
  error,
} = storeToRefs(accessStore)
const selectedRoleId = ref('')

const assignableRoles = computed(
  () => roles.value.filter((role) => role.key !== 'guest'),
)
const selectedRole = computed(
  () => roles.value.find((role) => role.id === selectedRoleId.value) ?? null,
)

watch(
  roles,
  (availableRoles) => {
    const selectionExists = availableRoles.some(
      (role) => role.id === selectedRoleId.value,
    )

    if (!selectionExists) {
      selectedRoleId.value = availableRoles[0]?.id ?? ''
    }
  },
  { immediate: true },
)

async function removeRole(role) {
  if (window.confirm(`確定刪除角色「${role.name}」嗎？`)) {
    await accessStore.removeRole(role)
  }
}

async function saveRole(role) {
  if (await accessStore.saveRole(role)) {
    await authStore.restore()
  }
}

async function saveUser(user) {
  const succeeded = await accessStore.saveUser(user)

  if (succeeded && user.id === authStore.user?.id) {
    await authStore.restore()
  }
}

function formatDate(value) {
  return new Intl.DateTimeFormat('zh-TW', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

onMounted(() => accessStore.load())
</script>

<template>
  <section class="access-hero">
    <div class="container hero-grid">
      <div>
        <p class="eyebrow">Access control</p>
        <h1 class="page-title">角色與權限管理</h1>
      </div>
      <p class="page-lead">
        角色是權限的集合。畫面隱藏只改善操作體驗，所有限制仍會由後端再次驗證。
      </p>
    </div>
  </section>

  <section class="container access-workspace">
    <div v-if="error" class="notice error" role="alert">{{ error }}</div>
    <div v-if="loading" class="notice" aria-live="polite">正在載入權限設定…</div>

    <template v-else>
      <section class="access-section">
        <div class="section-heading">
          <div>
            <p class="eyebrow">Roles</p>
            <h2>角色權限</h2>
          </div>
          <div class="heading-actions">
            <span>{{ roles.length }} 個角色</span>
            <RouterLink class="primary-button create-role-link" :to="{ name: 'access-role-create' }">
              新增角色
            </RouterLink>
          </div>
        </div>

        <div class="role-selector">
          <label for="role-select">選擇要管理的角色</label>
          <select id="role-select" v-model="selectedRoleId">
            <option v-for="role in roles" :key="role.id" :value="role.id">
              {{ role.name }}（{{ role.key }}）
            </option>
          </select>
        </div>

        <article v-if="selectedRole" class="panel role-editor">
          <div class="role-title">
            <div>
              <span class="role-key">{{ selectedRole.key }}</span>
              <input v-model.trim="selectedRole.name" maxlength="80">
            </div>
            <span>{{ selectedRole.user_count }} 位使用者</span>
          </div>
          <textarea
            v-model.trim="selectedRole.description"
            rows="2"
            maxlength="1000"
            placeholder="角色用途說明"
          />

          <PermissionChecklist
            v-model="selectedRole.permission_keys"
            :permissions="permissions"
          />

          <div class="editor-actions">
            <button type="button" :disabled="saving" @click="saveRole(selectedRole)">
              儲存權限
            </button>
            <button
              v-if="!selectedRole.is_system"
              class="danger"
              type="button"
              :disabled="saving"
              @click="removeRole(selectedRole)"
            >
              刪除角色
            </button>
          </div>
        </article>
        <p v-else class="panel empty-copy">目前沒有可以管理的角色。</p>
      </section>

      <section class="access-section">
        <div class="section-heading">
          <div>
            <p class="eyebrow">Accounts</p>
            <h2>使用者角色</h2>
          </div>
          <span>{{ users.length }} 個帳號</span>
        </div>

        <div class="user-grid">
          <article v-for="user in users" :key="user.id" class="panel user-card">
            <div>
              <strong>{{ user.nickname || `@${user.username}` }}</strong>
              <span v-if="user.nickname">@{{ user.username }}</span>
              <small>{{ user.id }}</small>
            </div>
            <label v-for="role in assignableRoles" :key="role.id" class="check-row">
              <input v-model="user.role_keys" type="checkbox" :value="role.key">
              <span>
                <strong>{{ role.name }}</strong>
                <small>{{ role.key }}</small>
              </span>
            </label>
            <button type="button" :disabled="saving" @click="saveUser(user)">
              儲存角色
            </button>
          </article>
        </div>
      </section>

      <section v-if="authStore.can('access.audit.view')" class="access-section">
        <div class="section-heading">
          <div>
            <p class="eyebrow">Audit log</p>
            <h2>最近權限異動</h2>
          </div>
          <span>最近 {{ audits.length }} 筆</span>
        </div>

        <div class="panel audit-list">
          <article v-for="audit in audits" :key="audit.id">
            <div>
              <strong>{{ audit.action }}</strong>
              <span>{{ audit.actor }} · {{ formatDate(audit.created_at) }}</span>
            </div>
            <code>{{ audit.subject_type }}:{{ audit.subject_id }}</code>
          </article>
          <p v-if="audits.length === 0" class="empty-copy">目前沒有異動紀錄。</p>
        </div>
      </section>
    </template>
  </section>
</template>

<style scoped>
.access-hero {
  padding: 88px 0 72px;
  border-bottom: 1px solid var(--border);
}

.hero-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(280px, 0.7fr);
  align-items: end;
  gap: 72px;
}

.access-workspace {
  padding-top: 64px;
  padding-bottom: 104px;
}

.access-section + .access-section {
  margin-top: 72px;
}

.section-heading {
  margin-bottom: 22px;
  display: flex;
  align-items: end;
  justify-content: space-between;
}

.section-heading h2 {
  margin: 0;
  font-size: 2rem;
}

.heading-actions > span {
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.78rem;
}

.heading-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.create-role-link {
  padding: 10px 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--accent);
  border-radius: 999px;
  color: var(--accent-contrast);
  background: var(--accent);
  font-weight: 800;
  text-decoration: none;
}

.role-editor {
  padding: 24px;
}

.role-selector {
  margin-bottom: 18px;
  padding: 18px 20px;
  display: grid;
  grid-template-columns: minmax(180px, 0.35fr) minmax(0, 1fr);
  align-items: center;
  gap: 20px;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  background: var(--surface);
}

.role-selector label {
  color: var(--text-muted);
  font-weight: 750;
}

.role-selector select,
.role-editor > textarea {
  width: 100%;
}

.role-selector select {
  padding: 11px 40px 11px 13px;
  border: 1px solid var(--border-strong);
  border-radius: 10px;
  color: var(--text);
  background: var(--bg-elevated);
}

.role-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.role-title > div {
  display: flex;
  align-items: center;
  gap: 12px;
}

.role-title input {
  font-size: 1.2rem;
  font-weight: 750;
}

.role-title > span,
.role-key {
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.76rem;
}

.role-editor > textarea {
  margin-top: 16px;
}

.check-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  color: var(--text-muted);
}

.check-row + .check-row {
  margin-top: 11px;
}

.check-row input {
  margin-top: 4px;
}

.check-row span {
  display: grid;
  gap: 2px;
}

.check-row strong {
  color: var(--text);
  font-size: 0.84rem;
}

.check-row small {
  overflow-wrap: anywhere;
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.68rem;
}

.editor-actions {
  display: flex;
  gap: 10px;
}

.editor-actions button,
.user-card > button {
  padding: 10px 14px;
  border: 1px solid var(--border);
  border-radius: 9px;
  color: var(--text);
  background: var(--surface-strong);
  font-weight: 750;
}

.editor-actions .danger {
  color: var(--danger);
}

.user-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.user-card {
  padding: 22px;
  display: grid;
  gap: 15px;
}

.user-card > div {
  display: grid;
  gap: 4px;
}

.user-card > div > span {
  color: var(--text-muted);
  font-size: 0.78rem;
}

.user-card > div small {
  overflow: hidden;
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.68rem;
  text-overflow: ellipsis;
}

.audit-list {
  padding: 0 22px;
}

.audit-list article {
  padding: 16px 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  border-bottom: 1px solid var(--border);
}

.audit-list article:last-child {
  border-bottom: 0;
}

.audit-list article > div {
  display: grid;
  gap: 4px;
}

.audit-list span,
.audit-list code {
  color: var(--text-faint);
  font-size: 0.74rem;
}

.empty-copy {
  padding: 20px 0;
  color: var(--text-muted);
}

@media (max-width: 720px) {
  .hero-grid,
  .role-selector,
  .user-grid {
    grid-template-columns: 1fr;
  }

  .section-heading,
  .heading-actions,
  .role-title,
  .role-title > div,
  .audit-list article {
    align-items: stretch;
    flex-direction: column;
  }

  .heading-actions {
    gap: 10px;
  }
}
</style>
