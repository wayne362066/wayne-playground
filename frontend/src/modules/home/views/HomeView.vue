<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import ModuleCard from '../components/ModuleCard.vue'
import { useModuleStore } from '../stores/moduleStore'

const moduleStore = useModuleStore()
const { items, loading, error } = storeToRefs(moduleStore)

onMounted(() => moduleStore.load())
</script>

<template>
  <section class="hero">
    <div class="container hero-grid">
      <div>
        <p class="eyebrow">Personal playground / 2026</p>
        <h1 class="page-title">一些實用工具，<br>一些好奇心。</h1>
      </div>
      <p class="page-lead">
        這裡收集 side projects、技術實驗與偶爾冒出的點子。每個模組都是一個可以慢慢長大的小作品。
      </p>
    </div>
  </section>

  <section class="modules-section">
    <div class="container">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Modules</p>
          <h2>現在可以玩什麼？</h2>
        </div>
        <span v-if="!loading && !error">{{ items.length }} 個模組</span>
      </div>

      <div v-if="loading" class="module-grid" aria-live="polite">
        <div v-for="index in 3" :key="index" class="skeleton" />
      </div>
      <div v-else-if="error" class="notice error">
        {{ error }}
        <button type="button" @click="moduleStore.load()">重新載入</button>
      </div>
      <div v-else class="module-grid">
        <ModuleCard v-for="module in items" :key="module.key" :module="module" />
      </div>
    </div>
  </section>
</template>

<style scoped>
.hero {
  padding: 96px 0 88px;
  border-bottom: 1px solid #d8d7cd;
}

.hero-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.6fr) minmax(280px, 0.7fr);
  align-items: end;
  gap: 72px;
}

.modules-section {
  padding: 72px 0 104px;
}

.section-heading {
  margin-bottom: 28px;
  display: flex;
  align-items: end;
  justify-content: space-between;
}

.section-heading h2 {
  margin: 0;
  font-family: Georgia, serif;
  font-size: clamp(1.9rem, 4vw, 2.8rem);
  font-weight: 500;
}

.section-heading > span {
  color: #737e77;
  font-size: 0.85rem;
}

.module-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.skeleton {
  min-height: 340px;
  border-radius: 20px;
  background: linear-gradient(110deg, #e7e5dc 8%, #f1efe7 18%, #e7e5dc 33%);
  background-size: 200% 100%;
  animation: shine 1.4s linear infinite;
}

.notice button {
  margin-left: 12px;
  border: 0;
  color: #8b302d;
  background: transparent;
  font-weight: 800;
  text-decoration: underline;
}

@keyframes shine {
  to { background-position-x: -200%; }
}

@media (max-width: 800px) {
  .hero {
    padding: 64px 0;
  }

  .hero-grid {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .module-grid {
    grid-template-columns: 1fr;
  }

  .module-card {
    min-height: 300px;
  }
}
</style>
