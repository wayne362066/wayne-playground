<template>
  <section class="container page-section">
    <p class="eyebrow">Open workspace</p>
    <h1 class="page-title">Lab</h1>
    <p class="page-lead">這裡會放各種開發實驗與測試功能。</p>
    <div class="lab-grid">
      <div v-for="index in 6" :key="index" class="lab-cell">
        <span>{{ String(index).padStart(2, '0') }}</span>
        <p>{{ index === 1 ? '下一個實驗，從這裡開始。' : '保留給未來的想法' }}</p>
      </div>
    </div>
  </section>
</template>

<style scoped>
.lab-grid {
  margin-top: 56px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  border-top: 1px solid var(--border);
  border-left: 1px solid var(--border);
}

.lab-cell {
  min-height: 170px;
  padding: 18px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border-right: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  background: var(--surface);
}

.lab-cell span {
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.7rem;
  font-weight: 800;
}

.lab-cell p {
  max-width: 160px;
  margin: 0;
  color: var(--text-muted);
  line-height: 1.5;
}

.lab-cell:not(:first-child) {
  opacity: 0.55;
}

@media (max-width: 680px) {
  .lab-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
