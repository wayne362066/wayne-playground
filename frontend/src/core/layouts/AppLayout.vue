<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'

const theme = ref('light')
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
            <RouterLink to="/lab">Lab</RouterLink>
          </nav>
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

  nav a:first-child {
    display: none;
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
