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
  color: #eee7d5;
  background: #252838;
}

.eyebrow {
  color: #c8ae75;
}

.page-lead {
  color: #bcbcc4;
}

.card-placeholder {
  width: 180px;
  aspect-ratio: 0.64;
  margin: 56px 0 0 auto;
  display: grid;
  place-items: center;
  border: 1px solid #8d805e;
  border-radius: 90px 90px 16px 16px;
  box-shadow: inset 0 0 0 8px #252838, inset 0 0 0 9px #665d4a;
  color: #d0b574;
  font-size: 2.2rem;
}
</style>
