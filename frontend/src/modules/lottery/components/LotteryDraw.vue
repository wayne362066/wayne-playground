<script setup>
defineProps({
  draw: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
})
</script>

<template>
  <article class="draw">
    <span class="draw-index">{{ String(index + 1).padStart(2, '0') }}</span>
    <div class="numbers" aria-label="第一區號碼">
      <span v-for="number in draw.zone_one" :key="number" class="ball">{{ number }}</span>
    </div>
    <span class="divider" aria-hidden="true" />
    <div class="zone-two">
      <small>第二區</small>
      <span class="ball special">{{ draw.zone_two }}</span>
    </div>
  </article>
</template>

<style scoped>
.draw {
  padding: 18px 20px;
  display: grid;
  grid-template-columns: 32px 1fr 1px auto;
  align-items: center;
  gap: 18px;
  border-bottom: 1px solid #e0dfd7;
}

.draw:last-child {
  border-bottom: 0;
}

.draw-index {
  color: #909992;
  font-size: 0.72rem;
  font-weight: 800;
}

.numbers {
  display: flex;
  flex-wrap: wrap;
  gap: 9px;
}

.ball {
  width: 42px;
  height: 42px;
  display: inline-grid;
  place-items: center;
  border: 1px solid #cdd3cc;
  border-radius: 50%;
  background: #fff;
  font-weight: 800;
}

.divider {
  width: 1px;
  height: 36px;
  background: #d5d5cb;
}

.zone-two {
  display: flex;
  align-items: center;
  gap: 10px;
}

.zone-two small {
  color: #7c867f;
}

.ball.special {
  color: white;
  border-color: #d2773f;
  background: #d2773f;
}

@media (max-width: 680px) {
  .draw {
    grid-template-columns: 24px 1fr;
  }

  .divider {
    display: none;
  }

  .zone-two {
    grid-column: 2;
  }

  .ball {
    width: 38px;
    height: 38px;
  }
}
</style>
