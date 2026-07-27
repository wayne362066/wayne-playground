<script setup>
const props = defineProps({
  module: {
    type: Object,
    required: true,
  },
})

const iconMap = {
  dice: '⚄',
  sparkles: '✦',
  flask: '⌁',
  wish: '♡',
}

const statusLabels = {
  active: '可使用',
  maintenance: '維護中',
  disabled: '已停用',
  coming_soon: 'Coming soon',
}
</script>

<template>
  <article class="module-card">
    <div class="card-top">
      <span class="module-icon" aria-hidden="true">{{ iconMap[props.module.icon] || '◇' }}</span>
      <span class="status" :class="props.module.status">{{ statusLabels[props.module.status] }}</span>
    </div>
    <div>
      <p class="number">0{{ props.module.sort_order }}</p>
      <h2>{{ props.module.name }}</h2>
      <p>{{ props.module.description }}</p>
    </div>
    <RouterLink
      v-if="props.module.status === 'active'"
      :to="props.module.route"
      :aria-label="`開啟${props.module.name}`"
    >
      開啟實驗 <span aria-hidden="true">↗</span>
    </RouterLink>
    <span v-else class="disabled-link">正在準備中</span>
  </article>
</template>

<style scoped>
.module-card {
  min-height: 340px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  backdrop-filter: blur(14px);
  transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease, background 180ms ease;
}

.module-card:hover {
  transform: translateY(-4px);
  border-color: var(--border-strong);
  background: var(--surface-hover);
  box-shadow: var(--shadow);
}

.card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.module-icon {
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 2rem;
}

.status {
  padding: 6px 9px;
  border-radius: 999px;
  color: var(--success);
  background: var(--accent-soft);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.status.coming_soon {
  color: var(--warning);
  background: var(--warning-soft);
}

.number {
  margin: 0 0 10px;
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.75rem;
  font-weight: 800;
}

h2 {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 650;
  letter-spacing: -0.035em;
}

div > p:last-child {
  margin: 14px 0 0;
  color: var(--text-muted);
  line-height: 1.65;
}

a,
.disabled-link {
  font-size: 0.9rem;
  font-weight: 800;
  text-decoration: none;
}

a {
  color: var(--accent);
}

.disabled-link {
  color: var(--text-faint);
}
</style>
