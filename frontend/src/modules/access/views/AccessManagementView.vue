<script setup>
import { computed, onMounted, reactive } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '../../auth/stores/authStore'
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
const newRole = reactive({
  key: '',
  name: '',
  description: '',
  permission_keys: [],
})

const permissionGroups = computed(() => {
  const groups = new Map()

  for (const permission of permissions.value) {
    if (!groups.has(permission.module)) groups.set(permission.module, [])
    groups.get(permission.module).push(permission)
  }

  return [...groups.entries()].map(([key, items]) => ({ key, items }))
})

const assignableRoles = computed(
  () => roles.value.filter((role) => role.key !== 'guest'),
)

async function submitRole() {
  const succeeded = await accessStore.createRole({
    key: newRole.key,
    name: newRole.name,
    description: newRole.description || null,
    permission_keys: newRole.permission_keys,
  })

  if (succeeded) {
    Object.assign(newRole, {
      key: '',
      name: '',
      description: '',
      permission_keys: [],
    })
  }
}

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
            <p class="eyebrow">New role</p>
            <h2>建立自訂角色</h2>
          </div>
        </div>

        <form class="panel role-editor new-role" @submit.prevent="submitRole">
          <div class="role-fields">
            <label>
              角色代碼
              <input
                v-model.trim="newRole.key"
                placeholder="wish-reviewer"
                maxlength="64"
                required
              >
            </label>
            <label>
              顯示名稱
              <input v-model.trim="newRole.name" maxlength="80" required>
            </label>
            <label class="wide-field">
              說明
              <input v-model.trim="newRole.description" maxlength="1000">
            </label>
          </div>

          <div class="permission-groups">
            <fieldset v-for="group in permissionGroups" :key="group.key">
              <legend>{{ group.key }}</legend>
              <label v-for="permission in group.items" :key="permission.key" class="check-row">
                <input
                  v-model="newRole.permission_keys"
                  type="checkbox"
                  :value="permission.key"
                >
                <span>
                  <strong>{{ permission.name }}</strong>
                  <small>{{ permission.key }}</small>
                </span>
              </label>
            </fieldset>
          </div>

          <button class="primary-button" type="submit" :disabled="saving">
            建立角色
          </button>
        </form>
      </section>

      <section class="access-section">
        <div class="section-heading">
          <div>
            <p class="eyebrow">Roles</p>
            <h2>角色權限</h2>
          </div>
          <span>{{ roles.length }} 個角色</span>
        </div>

        <div class="role-list">
          <article v-for="role in roles" :key="role.id" class="panel role-editor">
            <div class="role-title">
              <div>
                <span class="role-key">{{ role.key }}</span>
                <input v-model.trim="role.name" maxlength="80">
              </div>
              <span>{{ role.user_count }} 位使用者</span>
            </div>
            <textarea
              v-model.trim="role.description"
              rows="2"
              maxlength="1000"
              placeholder="角色用途說明"
            />

            <div class="permission-groups">
              <fieldset v-for="group in permissionGroups" :key="group.key">
                <legend>{{ group.key }}</legend>
                <label v-for="permission in group.items" :key="permission.key" class="check-row">
                  <input
                    v-model="role.permission_keys"
                    type="checkbox"
                    :value="permission.key"
                  >
                  <span>
                    <strong>{{ permission.name }}</strong>
                    <small>{{ permission.key }}</small>
                  </span>
                </label>
              </fieldset>
            </div>

            <div class="editor-actions">
              <button type="button" :disabled="saving" @click="saveRole(role)">
                儲存權限
              </button>
              <button
                v-if="!role.is_system"
                class="danger"
                type="button"
                :disabled="saving"
                @click="removeRole(role)"
              >
                刪除角色
              </button>
            </div>
          </article>
        </div>
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
              <strong>@{{ user.username }}</strong>
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

.section-heading > span {
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.78rem;
}

.role-editor {
  padding: 24px;
}

.role-list {
  display: grid;
  gap: 18px;
}

.role-fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.wide-field {
  grid-column: 1 / -1;
}

.role-fields label,
.role-editor > textarea {
  width: 100%;
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

.permission-groups {
  margin: 22px 0;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

fieldset {
  min-width: 0;
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
}

legend {
  padding: 0 7px;
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
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

@media (max-width: 900px) {
  .permission-groups {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 720px) {
  .hero-grid,
  .role-fields,
  .user-grid {
    grid-template-columns: 1fr;
  }

  .role-title,
  .role-title > div,
  .audit-list article {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
