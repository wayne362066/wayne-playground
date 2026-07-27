<script setup>
import { computed, reactive, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  username: '',
  password: '',
  password_confirmation: '',
})

const isRegister = computed(() => route.name === 'register')
const title = computed(() => isRegister.value ? '建立帳戶' : '歡迎回來')
const submitLabel = computed(() => isRegister.value ? '註冊並登入' : '登入')

watch(
  () => route.name,
  () => {
    form.password = ''
    form.password_confirmation = ''
    authStore.error = ''
  },
)

async function submit() {
  const payload = {
    username: form.username,
    password: form.password,
  }

  if (isRegister.value) {
    payload.password_confirmation = form.password_confirmation
  }

  const succeeded = isRegister.value
    ? await authStore.register(payload)
    : await authStore.login(payload)

  if (succeeded) {
    await router.push('/')
  }
}
</script>

<template>
  <section class="page-section auth-page">
    <div class="container auth-container">
      <div class="auth-copy">
        <p class="eyebrow">Account</p>
        <h1 class="page-title">{{ title }}</h1>
        <p class="page-lead">
          只保存帳號與不可還原的密碼雜湊，不需要姓名、Email 或其他個人資料。
        </p>
      </div>

      <div v-if="authStore.user" class="panel auth-panel signed-in">
        <p class="eyebrow">Signed in</p>
        <h2>你已經登入</h2>
        <p>目前帳號：<strong>@{{ authStore.user.username }}</strong></p>
        <RouterLink class="primary-action" to="/">回到首頁</RouterLink>
      </div>

      <form v-else class="panel auth-panel" @submit.prevent="submit">
        <label>
          <span>帳號</span>
          <input
            v-model.trim="form.username"
            name="username"
            type="text"
            autocomplete="username"
            minlength="3"
            maxlength="32"
            pattern="[A-Za-z0-9_]+"
            placeholder="wayne_01"
            required
          >
          <small>3–32 字元，可使用英文字母、數字與底線。</small>
        </label>

        <label>
          <span>密碼</span>
          <input
            v-model="form.password"
            name="password"
            type="password"
            :autocomplete="isRegister ? 'new-password' : 'current-password'"
            minlength="10"
            maxlength="72"
            required
          >
          <small v-if="isRegister">至少 10 個字元。</small>
        </label>

        <label v-if="isRegister">
          <span>再次輸入密碼</span>
          <input
            v-model="form.password_confirmation"
            name="password_confirmation"
            type="password"
            autocomplete="new-password"
            minlength="10"
            maxlength="72"
            required
          >
        </label>

        <p v-if="authStore.error" class="form-error" role="alert">
          {{ authStore.error }}
        </p>

        <button class="primary-action" type="submit" :disabled="authStore.loading">
          {{ authStore.loading ? '處理中…' : submitLabel }}
        </button>

        <p class="auth-switch">
          <template v-if="isRegister">
            已經有帳戶？<RouterLink to="/login">前往登入</RouterLink>
          </template>
          <template v-else>
            還沒有帳戶？<RouterLink to="/register">免費註冊</RouterLink>
          </template>
        </p>
      </form>
    </div>
  </section>
</template>

<style scoped>
.auth-page {
  min-height: calc(100vh - 145px);
  display: grid;
  align-items: center;
}

.auth-container {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(340px, 460px);
  align-items: center;
  gap: clamp(48px, 8vw, 120px);
}

.auth-copy .page-title {
  max-width: 620px;
}

.auth-panel {
  padding: clamp(28px, 5vw, 44px);
}

.auth-panel label {
  display: grid;
  gap: 9px;
  margin-bottom: 22px;
  color: var(--text);
  font-size: 0.88rem;
  font-weight: 750;
}

.auth-panel input {
  width: 100%;
  padding: 13px 14px;
  border: 1px solid var(--border-strong);
  border-radius: 10px;
  color: var(--text);
  background: var(--bg-elevated);
}

.auth-panel input:focus {
  border-color: var(--accent);
}

.auth-panel small {
  color: var(--text-faint);
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.5;
}

.primary-action {
  width: 100%;
  min-height: 46px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 10px;
  color: var(--accent-contrast);
  background: var(--accent);
  font-weight: 800;
  text-decoration: none;
}

.primary-action:hover {
  background: var(--accent-strong);
}

.primary-action:disabled {
  cursor: wait;
  opacity: 0.62;
}

.form-error {
  margin: 0 0 18px;
  padding: 12px 14px;
  border-radius: 10px;
  color: var(--danger);
  background: var(--danger-soft);
  font-size: 0.85rem;
  line-height: 1.5;
}

.auth-switch {
  margin: 22px 0 0;
  color: var(--text-muted);
  font-size: 0.86rem;
  text-align: center;
}

.auth-switch a {
  color: var(--accent);
  font-weight: 750;
}

.signed-in h2 {
  margin: 0 0 12px;
  font-size: 1.8rem;
}

.signed-in p:not(.eyebrow) {
  margin: 0 0 28px;
  color: var(--text-muted);
}

@media (max-width: 840px) {
  .auth-container {
    grid-template-columns: 1fr;
    gap: 40px;
  }

  .auth-copy .page-title {
    font-size: clamp(2.6rem, 12vw, 4rem);
  }
}
</style>
