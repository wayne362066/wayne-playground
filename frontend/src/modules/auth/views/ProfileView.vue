<script setup>
import { reactive, watch } from 'vue'
import { useAuthStore } from '../stores/authStore'

const authStore = useAuthStore()
const form = reactive({
  nickname: '',
})
const saved = reactive({
  message: '',
})

watch(
  () => authStore.user?.nickname,
  (nickname) => {
    form.nickname = nickname || ''
  },
  { immediate: true },
)

async function submit() {
  saved.message = ''

  if (await authStore.updateProfile({ nickname: form.nickname })) {
    saved.message = '個人設定已儲存。'
  }
}
</script>

<template>
  <section class="page-section profile-page">
    <div class="container profile-layout">
      <div class="profile-copy">
        <p class="eyebrow">Personal panel</p>
        <h1 class="page-title">個人面板</h1>
        <p class="page-lead">
          設定你想讓其他人看到的暱稱。登入帳號不會因此改變。
        </p>
      </div>

      <form class="panel profile-panel" @submit.prevent="submit">
        <div class="account-summary">
          <span>登入帳號</span>
          <strong>@{{ authStore.user?.username }}</strong>
        </div>

        <label>
          <span>暱稱</span>
          <input
            v-model="form.nickname"
            name="nickname"
            type="text"
            autocomplete="nickname"
            maxlength="40"
            placeholder="例如：Wayne"
          >
          <small>最多 40 個字；留空時會顯示登入帳號。</small>
        </label>

        <p v-if="authStore.error" class="form-message error" role="alert">
          {{ authStore.error }}
        </p>
        <p v-if="saved.message" class="form-message success" role="status">
          {{ saved.message }}
        </p>

        <button type="submit" :disabled="authStore.loading">
          {{ authStore.loading ? '儲存中…' : '儲存設定' }}
        </button>
      </form>
    </div>
  </section>
</template>

<style scoped>
.profile-page {
  min-height: calc(100vh - 145px);
  display: grid;
  align-items: center;
}

.profile-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(340px, 460px);
  align-items: center;
  gap: clamp(48px, 8vw, 120px);
}

.profile-panel {
  padding: clamp(28px, 5vw, 44px);
}

.account-summary {
  margin-bottom: 28px;
  padding-bottom: 22px;
  display: grid;
  gap: 6px;
  border-bottom: 1px solid var(--border);
}

.account-summary span,
.profile-panel small {
  color: var(--text-faint);
  font-size: 0.76rem;
}

.account-summary strong {
  overflow-wrap: anywhere;
  font-family: "SFMono-Regular", Consolas, monospace;
}

.profile-panel label {
  display: grid;
  gap: 9px;
  color: var(--text);
  font-size: 0.88rem;
  font-weight: 750;
}

.profile-panel input {
  width: 100%;
  padding: 13px 14px;
  border: 1px solid var(--border-strong);
  border-radius: 10px;
  color: var(--text);
  background: var(--bg-elevated);
}

.profile-panel input:focus {
  border-color: var(--accent);
}

.profile-panel button {
  width: 100%;
  min-height: 46px;
  margin-top: 22px;
  border: 0;
  border-radius: 10px;
  color: var(--accent-contrast);
  background: var(--accent);
  font-weight: 800;
}

.profile-panel button:hover {
  background: var(--accent-strong);
}

.profile-panel button:disabled {
  cursor: wait;
  opacity: 0.62;
}

.form-message {
  margin: 18px 0 0;
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 0.85rem;
}

.form-message.error {
  color: var(--danger);
  background: var(--danger-soft);
}

.form-message.success {
  color: var(--success);
  background: var(--accent-soft);
}

@media (max-width: 840px) {
  .profile-layout {
    grid-template-columns: 1fr;
    gap: 40px;
  }
}
</style>
