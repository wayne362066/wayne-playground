<script setup>
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '../../auth/stores/authStore'
import { useLotteryStore } from '../stores/lotteryStore'

const modes = [
  {
    value: 'single',
    number: '01',
    title: '模擬一期',
    description: '開出一組獎號，統計本期所有注數的獎項與損益。',
  },
  {
    value: 'until_profit',
    number: '02',
    title: '直到單期獲利',
    description: '逐期重抽，找出第一次當期獎金高於當期成本的結果。',
  },
  {
    value: 'until_jackpot',
    number: '03',
    title: '直到中頭獎',
    description: '依真實組合機率抽樣，估算要經過幾期才出現頭獎。',
  },
  {
    value: 'selected',
    number: '04',
    title: '自選號碼',
    description: '固定一組自選號碼，累計指定期數內的中獎結果與損益。',
  },
]

const ticketCount = ref(10)
const periodCount = ref(100)
const selectedZoneOne = ref([])
const selectedZoneTwo = ref(null)
const selectedMode = ref('single')
const localError = ref('')
const store = useLotteryStore()
const authStore = useAuthStore()
const { simulation, simulatedAt, loading, error } = storeToRefs(store)

const selectedModeInfo = computed(
  () => modes.find((mode) => mode.value === selectedMode.value),
)

const isSelectedMode = computed(() => selectedMode.value === 'selected')
const zoneOneNumbers = Array.from({ length: 38 }, (_, index) => index + 1)
const zoneTwoNumbers = Array.from({ length: 8 }, (_, index) => index + 1)

const winningPrizes = computed(
  () => simulation.value?.prizes.filter((prize) => prize.count > 0) || [],
)

const formattedTime = computed(() => {
  if (!simulatedAt.value) return ''
  return new Intl.DateTimeFormat('zh-TW', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(simulatedAt.value))
})

const resultTone = computed(() => {
  if (!simulation.value?.completed) return 'pending'
  return simulation.value.net_profit >= 0 ? 'positive' : 'negative'
})

function formatNumber(value) {
  return new Intl.NumberFormat('zh-TW').format(value ?? 0)
}

function formatMoney(value) {
  return new Intl.NumberFormat('zh-TW', {
    style: 'currency',
    currency: 'TWD',
    maximumFractionDigits: 0,
  }).format(value ?? 0)
}

function toggleZoneOne(number) {
  if (selectedZoneOne.value.includes(number)) {
    selectedZoneOne.value = selectedZoneOne.value.filter((selected) => selected !== number)
    return
  }

  if (selectedZoneOne.value.length < 6) {
    selectedZoneOne.value = [...selectedZoneOne.value, number].sort((a, b) => a - b)
  }
}

function submit() {
  if (isSelectedMode.value) {
    const normalizedPeriods = Number(periodCount.value)

    if (selectedZoneOne.value.length !== 6) {
      localError.value = '第一區必須選擇 6 個號碼。'
      return
    }

    if (!Number.isInteger(selectedZoneTwo.value)) {
      localError.value = '第二區必須選擇 1 個號碼。'
      return
    }

    if (!Number.isInteger(normalizedPeriods) || normalizedPeriods < 1 || normalizedPeriods > 1000000) {
      localError.value = '模擬期數必須是 1 到 1,000,000 的整數。'
      return
    }

    localError.value = ''
    store.simulateSelected(selectedZoneOne.value, selectedZoneTwo.value, normalizedPeriods)
    return
  }

  const normalizedCount = Number(ticketCount.value)
  if (!Number.isInteger(normalizedCount) || normalizedCount < 1 || normalizedCount > 10000) {
    localError.value = '每期購買注數必須是 1 到 10,000 的整數。'
    return
  }

  localError.value = ''
  store.simulate(normalizedCount, selectedMode.value)
}
</script>

<template>
  <section class="lottery-page">
    <div class="container page-section">
      <header class="lottery-hero">
        <div class="hero-copy">
          <p class="eyebrow">POWER LOTTERY / 6 + 1</p>
          <h1 class="page-title">如果一直買，<br><em>會發生什麼？</em></h1>
        </div>
        <div class="hero-note">
          <span class="note-rule" />
          <p>
            用程式重現威力彩開獎：第一區 1–38 選 6，第二區 1–8 選 1。
            每注以 NT$100 計算，獎金沿用原始測試設定。
            抽獎金額參考威力彩第114016期。
          </p>
          <small>純機率模擬，不構成投注建議。</small>
          <RouterLink
            v-if="authStore.can('lottery.duel')"
            class="duel-entry"
            to="/lottery/duels"
          >
            <span>開啟 1v1 對戰</span>
            <span aria-hidden="true">→</span>
          </RouterLink>
        </div>
      </header>

      <form
        v-if="authStore.can('lottery.simulate')"
        class="simulator panel"
        @submit.prevent="submit"
      >
        <div class="step-heading">
          <span>STEP 01</span>
          <div>
            <h2>選擇模擬方式</h2>
            <p>四種方式，共用同一套中獎規則。</p>
          </div>
        </div>

        <div class="mode-grid">
          <label
            v-for="mode in modes"
            :key="mode.value"
            class="mode-card"
            :class="{ active: selectedMode === mode.value }"
          >
            <input v-model="selectedMode" type="radio" name="mode" :value="mode.value">
            <span class="mode-number">{{ mode.number }}</span>
            <strong>{{ mode.title }}</strong>
            <span class="mode-description">{{ mode.description }}</span>
            <span class="radio-mark" aria-hidden="true" />
          </label>
        </div>

        <div v-if="isSelectedMode" class="custom-picker">
          <section>
            <div class="picker-heading">
              <strong>第一區</strong>
              <span>1–38 選 6（已選 {{ selectedZoneOne.length }} 個）</span>
            </div>
            <div class="number-picker" aria-label="選擇第一區號碼">
              <button
                v-for="number in zoneOneNumbers"
                :key="number"
                type="button"
                class="number-choice"
                :class="{ selected: selectedZoneOne.includes(number) }"
                :disabled="selectedZoneOne.length === 6 && !selectedZoneOne.includes(number)"
                :aria-pressed="selectedZoneOne.includes(number)"
                @click="toggleZoneOne(number)"
              >
                {{ String(number).padStart(2, '0') }}
              </button>
            </div>
          </section>

          <section>
            <div class="picker-heading">
              <strong>第二區</strong>
              <span>1–8 選 1</span>
            </div>
            <div class="number-picker zone-two-picker" aria-label="選擇第二區號碼">
              <button
                v-for="number in zoneTwoNumbers"
                :key="number"
                type="button"
                class="number-choice special-choice"
                :class="{ selected: selectedZoneTwo === number }"
                :aria-pressed="selectedZoneTwo === number"
                @click="selectedZoneTwo = number"
              >
                {{ String(number).padStart(2, '0') }}
              </button>
            </div>
          </section>
        </div>

        <div class="ticket-control">
          <div>
            <label :for="isSelectedMode ? 'period-count' : 'ticket-count'">
              {{ isSelectedMode ? '模擬期數' : '每期購買注數' }}
            </label>
            <span>{{ isSelectedMode ? '1–1,000,000 期' : '1–10,000 注' }}</span>
          </div>
          <div class="input-row">
            <div class="number-input">
              <input
                v-if="isSelectedMode"
                id="period-count"
                v-model.number="periodCount"
                type="number"
                min="1"
                max="1000000"
                step="1"
                inputmode="numeric"
              >
              <input
                v-else
                id="ticket-count"
                v-model.number="ticketCount"
                type="number"
                min="1"
                max="10000"
                step="1"
                inputmode="numeric"
              >
              <span>{{ isSelectedMode ? '期' : '注 / 期' }}</span>
            </div>
            <button type="submit" :disabled="loading">
              <span>{{ loading ? '計算中…' : '開始模擬' }}</span>
              <span aria-hidden="true">{{ loading ? '·' : '→' }}</span>
            </button>
          </div>
          <p class="selection-hint">{{ selectedModeInfo.description }}</p>
          <p v-if="localError || error" class="form-error" role="alert">
            {{ localError || error }}
          </p>
        </div>
      </form>
      <div v-else class="notice">
        你可以檢視這個模組，但目前沒有執行模擬的權限。
      </div>

      <section v-if="simulation" class="result-section" aria-live="polite">
        <div class="result-heading">
          <div>
            <p class="eyebrow">SIMULATION RESULT</p>
            <h2>這次的機率旅程</h2>
          </div>
          <span>{{ formattedTime }}</span>
        </div>

        <div class="result-grid">
          <article class="result-summary panel" :class="resultTone">
            <div class="summary-topline">
              <span>{{ modes.find((mode) => mode.value === simulation.mode)?.title }}</span>
              <span v-if="simulation.mode === 'selected'">固定 1 注 · 累計</span>
              <span v-else>{{ formatNumber(simulation.ticket_count) }} 注 / 期</span>
            </div>
            <p class="result-message">{{ simulation.message }}</p>
            <div class="attempt-count">
              <strong>{{ formatNumber(simulation.attempts) }}</strong>
              <span>期</span>
            </div>
            <p v-if="simulation.mode === 'until_profit'" class="attempt-note">
              {{ simulation.completed
                ? `前面經過 ${formatNumber(simulation.losing_rounds)} 個未獲利期`
                : `為控制運算量，本次最多模擬 ${formatNumber(simulation.maximum_attempts)} 期`
              }}
            </p>
            <p v-if="simulation.calculation_method === 'geometric_distribution'" class="attempt-note">
              依每注 1 / 22,085,448 的頭獎機率進行幾何分布抽樣，不以無上限迴圈占用伺服器。
            </p>
            <p v-if="simulation.mode === 'selected'" class="attempt-note">
              每期固定購買同一組號碼 1 注；自選號碼與電腦選號的中獎機率相同。
            </p>
          </article>

          <article class="winning-draw panel">
            <div class="card-label">
              {{ simulation.mode === 'selected' ? '你的固定號碼' : '本次中獎號碼' }}
            </div>
            <div
              class="balls"
              :aria-label="simulation.mode === 'selected' ? '自選第一區號碼' : '第一區中獎號碼'"
            >
              <span
                v-for="number in simulation.mode === 'selected'
                  ? simulation.selected_numbers.zone_one
                  : simulation.winning_numbers"
                :key="number"
                class="ball"
              >{{ String(number).padStart(2, '0') }}</span>
            </div>
            <div class="special-row">
              <span>第二區</span>
              <span class="ball special">
                {{ String(simulation.mode === 'selected'
                  ? simulation.selected_numbers.zone_two
                  : simulation.winning_special).padStart(2, '0') }}
              </span>
            </div>
          </article>
        </div>

        <div class="money-grid">
          <article>
            <span>總獎金</span>
            <strong>{{ formatMoney(simulation.total_prize_money) }}</strong>
          </article>
          <article>
            <span>{{ ['until_jackpot', 'selected'].includes(simulation.mode) ? '累計花費' : '本期花費' }}</span>
            <strong>{{ formatMoney(simulation.cost) }}</strong>
          </article>
          <article :class="resultTone">
            <span>淨損益</span>
            <strong>{{ simulation.net_profit > 0 ? '+' : '' }}{{ formatMoney(simulation.net_profit) }}</strong>
          </article>
        </div>

        <article class="prize-panel panel">
          <div class="prize-heading">
            <div>
              <span>PRIZE BREAKDOWN</span>
              <h3>中獎明細</h3>
            </div>
            <strong>
              {{ formatNumber(simulation.total_prize_count) }}
              <small>{{ simulation.mode === 'selected' ? '次中獎' : '注中獎' }}</small>
            </strong>
          </div>

          <div v-if="winningPrizes.length" class="prize-table">
            <div v-for="prize in winningPrizes" :key="prize.key" class="prize-row">
              <strong>{{ prize.label }}</strong>
              <span>{{ formatNumber(prize.count) }} {{ simulation.mode === 'selected' ? '次' : '注' }}</span>
              <span>每注 {{ formatMoney(prize.unit_prize) }}</span>
              <strong>{{ formatMoney(prize.amount) }}</strong>
            </div>
          </div>
          <div v-else class="no-prize">
            <span aria-hidden="true">—</span>
            <p>{{ simulation.mode === 'selected' ? '指定期數內沒有中獎。' : '這一期沒有任何中獎注數。' }}</p>
          </div>
        </article>
      </section>

      <div v-else class="pre-result">
        <span>結果會顯示在這裡</span>
        <div class="pre-result-line" />
      </div>
    </div>
  </section>
</template>

<style scoped>
.lottery-page {
  overflow: hidden;
  background:
    radial-gradient(circle at 7% 15%, var(--accent-soft), transparent 28%),
    transparent;
}

.lottery-hero {
  display: grid;
  grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.65fr);
  align-items: end;
  gap: 80px;
}

.page-title em {
  color: var(--accent);
  font-style: normal;
  font-weight: inherit;
}

.hero-note {
  padding-bottom: 6px;
}

.note-rule {
  width: 42px;
  height: 2px;
  display: block;
  margin-bottom: 20px;
  background: var(--accent);
}

.hero-note p {
  margin: 0;
  color: var(--text-muted);
  line-height: 1.75;
}

.hero-note small {
  display: block;
  margin-top: 14px;
  color: var(--text-faint);
}

.duel-entry {
  margin-top: 22px;
  padding: 12px 15px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: 1px solid var(--accent);
  border-radius: 10px;
  color: var(--accent);
  background: var(--accent-soft);
  font-size: 0.82rem;
  font-weight: 750;
  text-decoration: none;
  transition: transform 160ms ease, background 160ms ease;
}

.duel-entry:hover {
  transform: translateY(-2px);
}

.simulator {
  margin-top: 56px;
  padding: 34px;
}

.step-heading {
  display: flex;
  align-items: flex-start;
  gap: 20px;
}

.step-heading > span {
  margin-top: 5px;
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.7rem;
  font-weight: 900;
  letter-spacing: 0.14em;
}

.step-heading h2,
.result-heading h2,
.prize-heading h3 {
  margin: 0;
  font-weight: 650;
  letter-spacing: -0.035em;
}

.step-heading h2 {
  font-size: 1.7rem;
}

.step-heading p {
  margin: 5px 0 0;
  color: var(--text-muted);
  font-size: 0.88rem;
}

.mode-grid {
  margin-top: 28px;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.mode-card {
  position: relative;
  min-height: 180px;
  padding: 22px;
  display: flex;
  flex-direction: column;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  background: var(--surface);
  cursor: pointer;
  transition: border-color 160ms ease, background 160ms ease, transform 160ms ease;
}

.mode-card:hover {
  transform: translateY(-2px);
}

.mode-card.active {
  color: var(--text);
  border-color: var(--accent);
  background: var(--accent-soft);
  box-shadow: inset 0 0 0 1px var(--accent);
}

.mode-card input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.mode-number {
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.78rem;
}

.active .mode-number {
  color: var(--accent);
}

.mode-card strong {
  margin-top: 24px;
  font-size: 1.05rem;
}

.mode-description {
  margin-top: 8px;
  color: var(--text-muted);
  font-size: 0.82rem;
  line-height: 1.55;
}

.active .mode-description {
  color: var(--text-muted);
}

.radio-mark {
  position: absolute;
  top: 20px;
  right: 20px;
  width: 18px;
  height: 18px;
  border: 1px solid var(--border-strong);
  border-radius: 50%;
}

.active .radio-mark {
  border: 5px solid var(--accent);
}

.custom-picker {
  margin-top: 28px;
  padding: 24px;
  display: grid;
  gap: 24px;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  background: var(--surface-strong);
}

.picker-heading {
  margin-bottom: 14px;
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
}

.picker-heading strong {
  font-size: 0.9rem;
}

.picker-heading span {
  color: var(--text-faint);
  font-size: 0.78rem;
}

.number-picker {
  display: grid;
  grid-template-columns: repeat(10, 44px);
  gap: 8px;
}

.zone-two-picker {
  grid-template-columns: repeat(8, 44px);
}

button.number-choice {
  width: 100%;
  min-height: 0;
  aspect-ratio: 1;
  padding: 0;
  display: grid;
  justify-content: center;
  place-items: center;
  border: 1px solid var(--border-strong);
  border-radius: 50%;
  color: var(--text);
  background: var(--surface);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
}

button.number-choice:hover:not(:disabled) {
  border-color: var(--accent);
}

button.number-choice.selected {
  color: var(--accent-contrast);
  border-color: var(--accent);
  background: var(--accent);
}

button.number-choice:disabled {
  cursor: not-allowed;
  opacity: 0.35;
}

button.number-choice.special-choice.selected {
  box-shadow: 0 0 0 3px var(--accent-soft);
}

.ticket-control {
  margin-top: 32px;
  padding-top: 28px;
  border-top: 1px solid var(--border);
}

.ticket-control > div:first-child {
  display: flex;
  justify-content: space-between;
}

.ticket-control label {
  font-size: 0.88rem;
  font-weight: 800;
}

.ticket-control > div:first-child span {
  color: var(--text-faint);
  font-size: 0.8rem;
}

.input-row {
  margin-top: 10px;
  display: grid;
  grid-template-columns: 1fr 210px;
  gap: 12px;
}

.number-input {
  min-width: 0;
  display: flex;
  align-items: center;
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-md);
  background: var(--surface-strong);
}

.number-input:focus-within {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-soft);
}

.number-input input {
  min-width: 0;
  flex: 1;
  padding: 15px 16px;
  border: 0;
  outline: 0;
  background: transparent;
  font-weight: 800;
}

.number-input span {
  padding-right: 16px;
  color: var(--text-muted);
  font-size: 0.82rem;
}

button {
  padding: 0 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border: 0;
  border-radius: var(--radius-md);
  color: var(--accent-contrast);
  background: var(--accent);
  font-weight: 800;
}

button:disabled {
  cursor: wait;
  opacity: 0.65;
}

.selection-hint {
  margin: 10px 0 0;
  color: var(--text-faint);
  font-size: 0.78rem;
}

.form-error {
  margin: 10px 0 0;
  color: var(--danger);
  font-size: 0.86rem;
}

.result-section {
  margin-top: 72px;
}

.result-heading {
  margin-bottom: 18px;
  display: flex;
  align-items: end;
  justify-content: space-between;
}

.result-heading h2 {
  font-size: 2.4rem;
}

.result-heading > span {
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.8rem;
}

.result-grid {
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  gap: 16px;
}

.result-summary,
.winning-draw {
  min-height: 300px;
  padding: 28px;
}

.result-summary {
  color: var(--on-result);
  background: var(--result-positive);
}

.result-summary.negative {
  background: var(--result-negative);
}

.result-summary.pending {
  background: var(--result-pending);
}

.summary-topline {
  display: flex;
  justify-content: space-between;
  color: rgba(255, 255, 255, 0.67);
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.04em;
}

.result-message {
  margin: 38px 0 0;
  color: rgba(255, 255, 255, 0.8);
}

.attempt-count {
  margin-top: 8px;
  display: flex;
  align-items: baseline;
  gap: 10px;
}

.attempt-count strong {
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: clamp(3.3rem, 7vw, 5.5rem);
  font-weight: 500;
  letter-spacing: -0.05em;
  line-height: 1;
}

.attempt-count span {
  color: rgba(255, 255, 255, 0.7);
}

.attempt-note {
  max-width: 520px;
  margin: 18px 0 0;
  color: rgba(255, 255, 255, 0.68);
  font-size: 0.78rem;
  line-height: 1.6;
}

.winning-draw {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.card-label {
  color: var(--text-muted);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.balls {
  display: flex;
  flex-wrap: wrap;
  gap: 9px;
}

.ball {
  width: 47px;
  height: 47px;
  display: grid;
  place-items: center;
  border: 1px solid var(--border-strong);
  border-radius: 50%;
  background: var(--surface-strong);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-weight: 700;
}

.special-row {
  padding-top: 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid var(--border);
  color: var(--text-muted);
  font-size: 0.8rem;
}

.ball.special {
  color: var(--accent-contrast);
  border-color: var(--accent);
  background: var(--accent);
}

.money-grid {
  margin-top: 16px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1px;
  overflow: hidden;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--border);
}

.money-grid article {
  padding: 22px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: var(--surface);
}

.money-grid span {
  color: var(--text-muted);
  font-size: 0.75rem;
}

.money-grid strong {
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: clamp(1.25rem, 2.8vw, 2rem);
  font-weight: 500;
}

.money-grid .positive strong {
  color: var(--success);
}

.money-grid .negative strong {
  color: var(--danger);
}

.prize-panel {
  margin-top: 16px;
  padding: 28px;
}

.prize-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
}

.prize-heading span {
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.68rem;
  font-weight: 900;
  letter-spacing: 0.12em;
}

.prize-heading h3 {
  margin-top: 6px;
  font-size: 1.8rem;
}

.prize-heading > strong {
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 2rem;
  font-weight: 500;
}

.prize-heading small {
  color: var(--text-muted);
  font-family: inherit;
  font-size: 0.72rem;
  font-weight: 500;
}

.prize-table {
  margin-top: 22px;
  border-top: 1px solid var(--border);
}

.prize-row {
  padding: 16px 0;
  display: grid;
  grid-template-columns: 1fr 0.8fr 1.4fr 1fr;
  gap: 16px;
  border-bottom: 1px solid var(--border);
  font-size: 0.86rem;
}

.prize-row > *:last-child {
  text-align: right;
}

.prize-row span {
  color: var(--text-muted);
}

.no-prize {
  min-height: 130px;
  display: grid;
  place-content: center;
  justify-items: center;
  color: var(--text-faint);
}

.no-prize span {
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 2rem;
}

.no-prize p {
  margin: 8px 0 0;
}

.pre-result {
  margin-top: 52px;
  display: flex;
  align-items: center;
  gap: 16px;
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.75rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.pre-result-line {
  height: 1px;
  flex: 1;
  background: var(--border);
}

@media (max-width: 800px) {
  .lottery-hero,
  .result-grid {
    grid-template-columns: 1fr;
    gap: 28px;
  }

  .mode-grid {
    grid-template-columns: 1fr;
  }

  .mode-card {
    min-height: 145px;
  }

  .number-picker {
    grid-template-columns: repeat(8, 42px);
  }

  .money-grid {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 801px) and (max-width: 1100px) {
  .mode-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 580px) {
  .simulator,
  .result-summary,
  .winning-draw,
  .prize-panel {
    padding: 22px;
  }

  .input-row {
    grid-template-columns: 1fr;
  }

  .custom-picker {
    padding: 18px;
  }

  .picker-heading {
    align-items: flex-start;
    flex-direction: column;
    gap: 4px;
  }

  .number-picker,
  .zone-two-picker {
    grid-template-columns: repeat(5, 40px);
  }

  button {
    min-height: 50px;
  }

  .result-heading {
    align-items: flex-start;
    flex-direction: column;
    gap: 8px;
  }

  .result-summary,
  .winning-draw {
    min-height: 270px;
  }

  .prize-row {
    grid-template-columns: 1fr auto;
  }

  .prize-row span:nth-child(3) {
    display: none;
  }
}
</style>
