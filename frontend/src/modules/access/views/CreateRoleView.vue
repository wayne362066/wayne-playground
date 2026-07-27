<script setup>
import { onMounted, reactive } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import PermissionChecklist from '../components/PermissionChecklist.vue'
import { useAccessStore } from '../stores/accessStore'

const router = useRouter()
const accessStore = useAccessStore()
const { permissions, loading, saving, error } = storeToRefs(accessStore)
const newRole = reactive({
  key: '',
  name: '',
  description: '',
  permission_keys: [],
})

async function submitRole() {
  const succeeded = await accessStore.createRole({
    key: newRole.key,
    name: newRole.name,
    description: newRole.description || null,
    permission_keys: newRole.permission_keys,
  })

  if (succeeded) {
    await router.push({ name: 'access-management' })
  }
}

onMounted(() => accessStore.load())
</script>

<template>
  <section class="access-hero">
    <div class="container hero-grid">
      <div>
        <p class="eyebrow">New role</p>
        <h1 class="page-title">建立自訂角色</h1>
      </div>
      <p class="page-lead">
        建立角色後，可以回到角色管理頁繼續調整權限或指派使用者。
      </p>
    </div>
  </section>

  <section class="container role-workspace">
    <div v-if="error" class="notice error" role="alert">{{ error }}</div>
    <div v-if="loading" class="notice" aria-live="polite">正在載入權限設定…</div>

    <form v-else class="panel role-editor" @submit.prevent="submitRole">
      <div class="form-heading">
        <div>
          <p class="eyebrow">Role details</p>
          <h2>角色資料與權限</h2>
        </div>
        <RouterLink :to="{ name: 'access-management' }">返回角色管理</RouterLink>
      </div>

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
          <textarea v-model.trim="newRole.description" rows="3" maxlength="1000" />
        </label>
      </div>

      <PermissionChecklist
        v-model="newRole.permission_keys"
        :permissions="permissions"
      />

      <div class="form-actions">
        <button class="primary-button" type="submit" :disabled="saving">
          {{ saving ? '建立中…' : '建立角色' }}
        </button>
        <RouterLink :to="{ name: 'access-management' }">取消</RouterLink>
      </div>
    </form>
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

.role-workspace {
  padding-top: 64px;
  padding-bottom: 104px;
}

.role-editor {
  padding: 24px;
}

.form-heading {
  margin-bottom: 24px;
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 24px;
}

.form-heading h2 {
  margin: 0;
  font-size: 2rem;
}

.form-heading > a,
.form-actions > a {
  color: var(--text-muted);
  font-weight: 700;
}

.role-fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.role-fields label {
  display: grid;
  gap: 8px;
}

.wide-field {
  grid-column: 1 / -1;
}

.role-fields input,
.role-fields textarea {
  width: 100%;
}

.form-actions {
  display: flex;
  align-items: center;
  gap: 18px;
}

.primary-button {
  padding: 11px 18px;
  border: 1px solid var(--accent);
  border-radius: 999px;
  color: var(--accent-contrast);
  background: var(--accent);
  font-weight: 800;
}

.primary-button:disabled {
  cursor: wait;
  opacity: 0.62;
}

@media (max-width: 720px) {
  .hero-grid,
  .role-fields {
    grid-template-columns: 1fr;
  }

  .form-heading {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
