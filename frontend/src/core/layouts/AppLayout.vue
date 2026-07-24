<script setup>
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <div class="site-shell">
    <header class="site-header">
      <div class="container header-inner">
        <RouterLink class="brand" to="/">
          <span class="brand-mark">P</span>
          <span>Wayne's Playground</span>
        </RouterLink>
        <nav aria-label="主要導覽">
          <RouterLink to="/">首頁</RouterLink>
          <RouterLink to="/lottery">威力彩</RouterLink>
          <RouterLink to="/lab">Lab</RouterLink>
        </nav>
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
  border-bottom: 1px solid rgba(34, 54, 44, 0.12);
  background: rgba(243, 241, 232, 0.9);
  backdrop-filter: blur(14px);
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
  font-weight: 800;
  text-decoration: none;
}

.brand-mark {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  color: #f8f4e6;
  background: #1f6348;
  font-family: Georgia, serif;
}

nav {
  display: flex;
  gap: 24px;
}

nav a {
  color: #5b665f;
  font-size: 0.92rem;
  font-weight: 700;
  text-decoration: none;
}

nav a.router-link-active {
  color: #1f6348;
}

main {
  flex: 1;
}

.site-footer {
  border-top: 1px solid #d8d7cd;
  color: #69736d;
  font-size: 0.86rem;
}

@media (max-width: 640px) {
  .header-inner {
    min-height: 64px;
  }

  nav {
    gap: 14px;
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
