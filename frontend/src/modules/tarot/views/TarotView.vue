<template>
  <section class="tarot-page">
    <div class="container page-section">
      <p class="eyebrow">Tarot / Coming soon</p>
      <h1 class="page-title">有些答案，<br>值得慢慢翻開。</h1>
      <p class="page-lead">
        單張牌、三張牌、規則式解讀與 AI 解讀正在規劃中。這個模組目前先保留一個安靜的位置。
      </p>
      <div class="card-placeholder" aria-hidden="true">
        <span>✦</span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.tarot-page {
  min-height: calc(100vh - 145px);
  background:
    radial-gradient(circle at 78% 34%, var(--accent-soft), transparent 24%),
    transparent;
}

.eyebrow {
  color: var(--accent);
}

.page-lead {
  color: var(--text-muted);
}

.card-placeholder {
  width: 180px;
  aspect-ratio: 0.64;
  margin: 56px 0 0 auto;
  display: grid;
  place-items: center;
  border: 1px solid var(--accent);
  border-radius: 90px 90px 14px 14px;
  box-shadow: inset 0 0 0 8px var(--bg), inset 0 0 0 9px var(--border-strong), 0 0 44px var(--accent-soft);
  color: var(--accent);
  font-size: 2.2rem;
}
</style>
