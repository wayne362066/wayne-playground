<script setup>
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import LotteryDraw from '../components/LotteryDraw.vue'
import { useLotteryStore } from '../stores/lotteryStore'

const count = ref(1)
const store = useLotteryStore()
const { draws, generatedAt, loading, error } = storeToRefs(store)

const formattedTime = computed(() => {
  if (!generatedAt.value) return ''
  return new Intl.DateTimeFormat('zh-TW', {
    dateStyle: 'medium',
    timeStyle: 'medium',
  }).format(new Date(generatedAt.value))
})

function submit() {
  const normalizedCount = Number(count.value)
  if (!Number.isInteger(normalizedCount) || normalizedCount < 1 || normalizedCount > 100) {
    return
  }
  store.generate(normalizedCount)
}
</script>

<template>
  <section class="container page-section">
    <div class="lottery-intro">
      <div>
        <p class="eyebrow">Lottery / Power 6+1</p>
        <h1 class="page-title">把隨機交給<br>程式。</h1>
      </div>
      <p class="page-lead">
        第一區從 1–38 隨機取出 6 個不重複號碼，第二區從 1–8 取出 1 個號碼。純模擬，不代表任何中獎機率。
      </p>
    </div>

    <form class="generator panel" @submit.prevent="submit">
      <label for="count">
        模擬組數
        <span>最多 100 組</span>
      </label>
      <div class="controls">
        <input id="count" v-model.number="count" type="number" min="1" max="100" step="1">
        <button type="submit" :disabled="loading">
          {{ loading ? '產生中…' : draws.length ? '重新產生' : '產生號碼' }}
        </button>
      </div>
      <p v-if="error" class="form-error" role="alert">{{ error }}</p>
    </form>

    <section v-if="draws.length" class="results">
      <div class="results-heading">
        <h2>模擬結果</h2>
        <span>{{ draws.length }} 組 · {{ formattedTime }}</span>
      </div>
      <div class="panel draws-list">
        <LotteryDraw
          v-for="(draw, index) in draws"
          :key="`${generatedAt}-${index}`"
          :draw="draw"
          :index="index"
        />
      </div>
    </section>
    <div v-else class="empty-state">
      <span aria-hidden="true">⚄</span>
      <p>設定組數後，開始你的第一次模擬。</p>
    </div>
  </section>
</template>

<style scoped>
.lottery-intro {
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(280px, 0.75fr);
  align-items: end;
  gap: 72px;
}

.generator {
  margin-top: 48px;
  padding: 24px;
}

label {
  display: flex;
  justify-content: space-between;
  color: #283a31;
  font-size: 0.88rem;
  font-weight: 800;
}

label span {
  color: #7f8983;
  font-weight: 500;
}

.controls {
  margin-top: 12px;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
}

input {
  min-width: 0;
  padding: 15px 16px;
  border: 1px solid #cbd0ca;
  border-radius: 12px;
  color: #17221d;
  background: #fff;
  outline: none;
}

input:focus {
  border-color: #2b7456;
  box-shadow: 0 0 0 3px rgba(43, 116, 86, 0.12);
}

button {
  padding: 0 24px;
  border: 0;
  border-radius: 12px;
  color: white;
  background: #1f6348;
  font-weight: 800;
}

button:disabled {
  cursor: wait;
  opacity: 0.6;
}

.form-error {
  margin: 12px 0 0;
  color: #a23d37;
  font-size: 0.88rem;
}

.results {
  margin-top: 48px;
}

.results-heading {
  margin-bottom: 14px;
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 16px;
}

.results-heading h2 {
  margin: 0;
  font-family: Georgia, serif;
  font-size: 2rem;
  font-weight: 500;
}

.results-heading span {
  color: #77817a;
  font-size: 0.8rem;
}

.draws-list {
  overflow: hidden;
}

.empty-state {
  min-height: 230px;
  display: grid;
  place-content: center;
  justify-items: center;
  color: #808a83;
  text-align: center;
}

.empty-state span {
  color: #a5aca7;
  font-family: Georgia, serif;
  font-size: 2.6rem;
}

@media (max-width: 760px) {
  .lottery-intro {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .controls {
    grid-template-columns: 1fr;
  }

  button {
    min-height: 50px;
  }

  .results-heading {
    align-items: flex-start;
    flex-direction: column;
    gap: 6px;
  }
}
</style>
