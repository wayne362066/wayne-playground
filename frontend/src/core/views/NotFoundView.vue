<template>
  <section class="container page-section">
    <p class="eyebrow">404 / Not Found</p>
    <h1 class="page-title">這裡還沒有<br>實驗。</h1>
    <p class="page-lead">你造訪的頁面不存在，可能已經移動或仍在構想中。</p>
    <RouterLink class="home-link" to="/">返回 Playground</RouterLink>
  </section>
</template>

<style scoped>
.home-link {
  display: inline-block;
  margin-top: 32px;
  padding: 13px 18px;
  border-radius: 999px;
  color: white;
  background: #1f6348;
  font-weight: 800;
  text-decoration: none;
}
</style>
