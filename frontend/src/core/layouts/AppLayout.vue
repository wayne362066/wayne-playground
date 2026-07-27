<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '../../modules/auth/stores/authStore'

const theme = ref('light')
const authStore = useAuthStore()
let mediaQuery

const isDark = computed(() => theme.value === 'dark')

function applyTheme(nextTheme, persist = true) {
  theme.value = nextTheme
  document.documentElement.dataset.theme = nextTheme
  document.querySelector('meta[name="theme-color"]')?.setAttribute(
    'content',
    nextTheme === 'dark' ? '#0a0f14' : '#f5f7fa',
  )
  if (persist) localStorage.setItem('wayne-theme', nextTheme)
}

function toggleTheme() {
  applyTheme(isDark.value ? 'light' : 'dark')
}

async function logout() {
  await authStore.logout()
}

function handleSystemTheme(event) {
  if (!localStorage.getItem('wayne-theme')) {
    applyTheme(event.matches ? 'dark' : 'light', false)
  }
}

onMounted(() => {
  theme.value = document.documentElement.dataset.theme || 'light'
  mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
  mediaQuery.addEventListener('change', handleSystemTheme)
})

onBeforeUnmount(() => mediaQuery?.removeEventListener('change', handleSystemTheme))
</script>

<template>
  <div class="site-shell">
    <header class="site-header">
      <div class="container header-inner">
        <RouterLink class="brand" to="/">
          <span class="brand-mark">P</span>
          <span>Wayne's Playground</span>
        </RouterLink>
        <div class="header-actions">
          <nav aria-label="主要導覽">
            <RouterLink to="/">首頁</RouterLink>
            <RouterLink to="/lottery">威力彩</RouterLink>
            <RouterLink to="/tarot">塔羅</RouterLink>
            <RouterLink to="/wishes">許願板</RouterLink>
          </nav>
          <div class="account-actions">
            <template v-if="authStore.user">
              <span class="account-name">@{{ authStore.user.username }}</span>
              <button
                class="account-link"
                type="button"
                :disabled="authStore.loading"
                @click="logout"
              >
                登出
              </button>
            </template>
            <template v-else>
              <RouterLink class="account-link" to="/login">登入</RouterLink>
              <RouterLink class="account-link account-link-primary" to="/register">
                註冊
              </RouterLink>
            </template>
          </div>
          <button
            class="theme-toggle"
            type="button"
            :aria-label="isDark ? '切換為淺色模式' : '切換為深色模式'"
            :title="isDark ? '切換為淺色模式' : '切換為深色模式'"
            :aria-pressed="isDark"
            @click="toggleTheme"
          >
            <span aria-hidden="true">{{ isDark ? '☀' : '◐' }}</span>
          </button>
        </div>
      </div>
    </header>

    <main>
      <RouterView />
    </main>

    <footer class="site-footer">
      <div class="container footer-inner">
        <span>Wayne's Playground</span>
        <span>Build small. Learn always.</span>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.site-shell {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.site-header {
  position: sticky;
  z-index: 10;
  top: 0;
  border-bottom: 1px solid var(--border);
  background: var(--header-bg);
  backdrop-filter: blur(18px);
}

.header-inner,
.footer-inner {
  min-height: 72px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 750;
  letter-spacing: -0.02em;
  text-decoration: none;
}

.brand-mark {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border: 1px solid var(--accent);
  border-radius: 10px;
  color: var(--accent-contrast);
  background: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  box-shadow: 0 0 22px var(--accent-soft);
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 22px;
}

nav {
  display: flex;
  gap: 24px;
}

nav a {
  position: relative;
  color: var(--text-muted);
  font-size: 0.92rem;
  font-weight: 700;
  text-decoration: none;
}

nav a.router-link-active {
  color: var(--text);
}

nav a.router-link-active::after {
  position: absolute;
  right: 0;
  bottom: -9px;
  left: 0;
  height: 2px;
  border-radius: 2px;
  background: var(--accent);
  content: "";
}

.account-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.account-name {
  max-width: 140px;
  overflow: hidden;
  color: var(--text-muted);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.78rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.account-link {
  padding: 8px 10px;
  border: 0;
  border-radius: 9px;
  color: var(--text-muted);
  background: transparent;
  font-size: 0.82rem;
  font-weight: 750;
  text-decoration: none;
}

.account-link:hover {
  color: var(--accent);
  background: var(--accent-soft);
}

.account-link-primary {
  color: var(--accent-contrast);
  background: var(--accent);
}

.account-link-primary:hover {
  color: var(--accent-contrast);
  background: var(--accent-strong);
}

.account-link:disabled {
  cursor: wait;
  opacity: 0.55;
}

.theme-toggle {
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--text-muted);
  background: var(--surface);
  font-size: 1rem;
  transition: color 160ms ease, border-color 160ms ease, background 160ms ease;
}

.theme-toggle:hover {
  color: var(--accent);
  border-color: var(--accent);
  background: var(--accent-soft);
}

main {
  flex: 1;
}

.site-footer {
  border-top: 1px solid var(--border);
  color: var(--text-faint);
  font-size: 0.86rem;
}

@media (max-width: 640px) {
  .header-inner {
    min-height: 64px;
  }

  nav {
    gap: 14px;
  }

  .header-actions {
    gap: 12px;
  }

  nav a:not(:last-child) {
    display: none;
  }

  nav a:first-child {
    display: none;
  }

  .account-name {
    display: none;
  }

  .account-actions {
    gap: 2px;
  }

  .account-link {
    padding: 7px;
  }

  .brand > span:last-child {
    display: none;
  }

  .footer-inner {
    align-items: flex-start;
    flex-direction: column;
    justify-content: center;
    gap: 5px;
  }
}
</style>
