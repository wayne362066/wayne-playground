<script setup>
import { computed, nextTick, ref } from 'vue'
import { drawTarotCards } from '../data/tarotDeck'
import { tarotDomains } from '../data/tarotQuestions'
import { interpretTarotReading } from '../services/tarotInterpreter'

const selectedDomain = ref(null)
const currentQuestionIndex = ref(0)
const answers = ref({})
const stage = ref('questions')
const reading = ref(null)
const resultSection = ref(null)

const currentQuestion = computed(
  () => selectedDomain.value?.questions[currentQuestionIndex.value],
)

const totalSteps = computed(() => (selectedDomain.value?.questions.length || 3) + 1)
const currentStep = computed(() => (
  selectedDomain.value ? currentQuestionIndex.value + 2 : 1
))
const progress = computed(() => {
  if (stage.value === 'ready') return 100
  return (currentStep.value / totalSteps.value) * 100
})

const answeredLabels = computed(() => {
  if (!selectedDomain.value) return []

  return selectedDomain.value.questions
    .map((question) => question.options.find(
      (option) => option.value === answers.value[question.id],
    )?.label)
    .filter(Boolean)
})

function selectDomain(domain) {
  selectedDomain.value = domain
  currentQuestionIndex.value = 0
  answers.value = {}
}

function selectAnswer(option) {
  const question = currentQuestion.value
  answers.value = {
    ...answers.value,
    [question.id]: option.value,
  }

  if (currentQuestionIndex.value === selectedDomain.value.questions.length - 1) {
    stage.value = 'ready'
    return
  }

  currentQuestionIndex.value += 1
}

function goBack() {
  if (stage.value === 'ready') {
    stage.value = 'questions'
    currentQuestionIndex.value = selectedDomain.value.questions.length - 1
    return
  }

  if (currentQuestionIndex.value > 0) {
    currentQuestionIndex.value -= 1
    return
  }

  selectedDomain.value = null
  answers.value = {}
}

async function drawCards() {
  const cards = drawTarotCards(3)
  reading.value = interpretTarotReading({
    domain: selectedDomain.value,
    answers: answers.value,
    cards,
  })
  stage.value = 'result'

  await nextTick()
  resultSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function restart() {
  selectedDomain.value = null
  currentQuestionIndex.value = 0
  answers.value = {}
  reading.value = null
  stage.value = 'questions'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<template>
  <section class="tarot-page">
    <div class="container page-section">
      <header class="tarot-hero">
        <div>
          <p class="eyebrow">Tarot / A quiet reflection</p>
          <h1 class="page-title">把此刻的疑問，<br><em>交給三張牌。</em></h1>
        </div>
        <p class="page-lead">
          不預設命運，也不替你做決定。透過幾個簡單選項整理問題，
          再從牌面找出此刻值得留意的方向。
        </p>
      </header>

      <section v-if="stage !== 'result'" class="reading-flow panel">
        <div class="flow-progress" aria-label="問答進度">
          <div class="progress-meta">
            <span>STEP {{ String(stage === 'ready' ? totalSteps : currentStep).padStart(2, '0') }}</span>
            <span>{{ stage === 'ready' ? '準備抽牌' : `${currentStep} / ${totalSteps}` }}</span>
          </div>
          <div class="progress-track">
            <span :style="{ width: `${progress}%` }" />
          </div>
        </div>

        <div v-if="stage === 'questions'" class="question-panel">
          <template v-if="!selectedDomain">
            <p class="question-kicker">先從一個方向開始</p>
            <h2>你現在想問什麼？</h2>
            <p class="question-helper">選擇最接近此刻疑問的領域。</p>

            <div class="option-grid domain-grid">
              <button
                v-for="(domain, index) in tarotDomains"
                :key="domain.value"
                class="option-button domain-button"
                type="button"
                @click="selectDomain(domain)"
              >
                <span class="domain-card-number">0{{ index + 1 }}</span>
                <span class="domain-card-symbol" aria-hidden="true">
                  <i />
                  <span class="option-icon">{{ domain.icon }}</span>
                </span>
                <span class="domain-card-copy">
                  <strong>{{ domain.label }}</strong>
                  <small>{{ domain.description }}</small>
                </span>
                <span class="domain-card-action">
                  選擇此牌 <span aria-hidden="true">→</span>
                </span>
              </button>
            </div>
          </template>

          <template v-else>
            <div class="selected-context">
              <span>{{ selectedDomain.icon }}</span>
              <strong>{{ selectedDomain.label }}</strong>
              <span v-for="label in answeredLabels" :key="label">{{ label }}</span>
            </div>
            <p class="question-kicker">{{ selectedDomain.label }} · 問題 {{ currentQuestionIndex + 1 }}</p>
            <h2>{{ currentQuestion.prompt }}</h2>
            <p class="question-helper">{{ currentQuestion.helper }}</p>

            <div class="option-grid">
              <button
                v-for="option in currentQuestion.options"
                :key="option.value"
                class="option-button"
                type="button"
                @click="selectAnswer(option)"
              >
                <span>
                  <strong>{{ option.label }}</strong>
                  <small>{{ option.insight }}</small>
                </span>
                <span class="option-arrow" aria-hidden="true">→</span>
              </button>
            </div>

            <button class="back-button" type="button" @click="goBack">
              ← 回到上一題
            </button>
          </template>
        </div>

        <div v-else class="draw-panel">
          <div class="draw-copy">
            <p class="question-kicker">{{ selectedDomain.label }} · 問答完成</p>
            <h2>先停一下，想著你的問題。</h2>
            <p>
              接下來會抽出「此刻、核心影響、行動方向」三張牌。
              結果會依照你的選項、牌面位置與正逆位組合。
            </p>

            <div class="answer-summary">
              <span v-for="label in answeredLabels" :key="label">{{ label }}</span>
            </div>

            <div class="draw-actions">
              <button class="draw-button" type="button" @click="drawCards">
                <span>洗牌並抽出三張牌</span>
                <span aria-hidden="true">✦</span>
              </button>
              <button class="back-button" type="button" @click="goBack">
                ← 修改上一題
              </button>
            </div>
          </div>

          <div class="deck-preview" aria-hidden="true">
            <div class="deck-card deck-card-back">
              <span>✦</span>
              <i />
            </div>
            <div class="deck-card deck-card-middle" />
            <div class="deck-card deck-card-bottom" />
          </div>
        </div>
      </section>

      <section v-else ref="resultSection" class="reading-result">
        <header class="result-header">
          <div>
            <p class="eyebrow">Your three-card reading</p>
            <h2>{{ reading.headline }}</h2>
          </div>
          <button type="button" @click="restart">重新提問 ↻</button>
        </header>

        <article class="reading-intro panel">
          <span class="intro-mark" aria-hidden="true">“</span>
          <p>{{ reading.opening }}</p>
          <p>{{ reading.synthesis }}</p>
        </article>

        <div class="card-reading-grid">
          <article
            v-for="(card, index) in reading.cards"
            :key="card.id"
            class="reading-card"
            :style="{ '--reveal-delay': `${index * 120}ms` }"
          >
            <div class="tarot-card" :class="{ reversed: card.orientation === 'reversed' }">
              <span class="card-number">
                {{ card.arcana === 'major' ? String(card.number).padStart(2, '0') : card.positionLabel }}
              </span>
              <span class="card-symbol" aria-hidden="true">{{ card.symbol }}</span>
              <strong>{{ card.name }}</strong>
              <span class="card-orientation">{{ card.orientationLabel }}</span>
            </div>
            <div class="card-copy">
              <div class="position-label">
                <span>0{{ index + 1 }}</span>
                <strong>{{ card.positionLabel }}</strong>
              </div>
              <p>{{ card.interpretation }}</p>
              <div class="card-action">
                <span>給你的提醒</span>
                <p>{{ card.action }}</p>
              </div>
            </div>
          </article>
        </div>

        <article class="action-panel panel">
          <div>
            <p class="eyebrow">Bring it into daily life</p>
            <h3>把牌面帶回生活</h3>
          </div>
          <ol>
            <li v-for="action in reading.actions" :key="action">{{ action }}</li>
          </ol>
        </article>

        <p class="reading-disclaimer">{{ reading.disclaimer }}</p>
      </section>
    </div>
  </section>
</template>

<style scoped>
.tarot-page {
  overflow: hidden;
  min-height: calc(100vh - 145px);
  background:
    radial-gradient(circle at 86% 16%, var(--accent-soft), transparent 24%),
    radial-gradient(circle at 8% 58%, var(--warning-soft), transparent 21%);
}

.tarot-hero {
  margin-bottom: 64px;
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(280px, 0.65fr);
  align-items: end;
  gap: 72px;
}

.page-title em {
  color: var(--accent);
  font-style: normal;
}

.reading-flow {
  position: relative;
  overflow: hidden;
  min-height: 600px;
}

.reading-flow::before {
  position: absolute;
  top: -120px;
  right: -90px;
  width: 320px;
  height: 320px;
  border: 1px solid var(--border);
  border-radius: 50%;
  content: "";
  pointer-events: none;
}

.flow-progress {
  position: relative;
  z-index: 1;
  padding: 22px 28px 0;
}

.progress-meta {
  margin-bottom: 10px;
  display: flex;
  justify-content: space-between;
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.progress-track {
  height: 3px;
  overflow: hidden;
  border-radius: 3px;
  background: var(--bg-subtle);
}

.progress-track span {
  height: 100%;
  display: block;
  border-radius: inherit;
  background: var(--accent);
  transition: width 320ms ease;
}

.question-panel {
  position: relative;
  z-index: 1;
  max-width: 940px;
  margin: 0 auto;
  padding: 68px 52px 64px;
}

.question-kicker {
  margin: 0 0 12px;
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.question-panel h2,
.draw-copy h2,
.result-header h2 {
  margin: 0;
  font-size: clamp(2rem, 4vw, 3.4rem);
  font-weight: 650;
  line-height: 1.1;
  letter-spacing: -0.045em;
}

.question-helper,
.draw-copy > p:not(.question-kicker) {
  margin: 16px 0 0;
  color: var(--text-muted);
  line-height: 1.75;
}

.option-grid {
  margin-top: 36px;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.domain-grid {
  margin-top: 46px;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 28px 24px;
}

.option-button {
  min-height: 116px;
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  color: var(--text);
  background: var(--bg-elevated);
  text-align: left;
  transition: border-color 180ms ease, transform 180ms ease, background 180ms ease;
}

.option-button:hover {
  transform: translateY(-2px);
  border-color: var(--accent);
  background: var(--accent-soft);
}

.option-button > span:first-child:not(.option-icon) {
  display: grid;
  gap: 8px;
}

.option-button strong {
  font-size: 1.05rem;
}

.option-button small {
  display: block;
  color: var(--text-muted);
  font-size: 0.82rem;
  line-height: 1.55;
}

.option-arrow {
  flex: 0 0 auto;
  color: var(--accent);
  font-size: 1.3rem;
}

.domain-button {
  position: relative;
  overflow: hidden;
  min-height: 286px;
  padding: 22px 20px 20px;
  align-items: stretch;
  flex-direction: column;
  justify-content: space-between;
  gap: 0;
  border-color: var(--border-strong);
  border-radius: 92px 92px 18px 18px;
  background:
    radial-gradient(circle at 50% 36%, var(--accent-soft), transparent 33%),
    var(--bg-elevated);
  box-shadow:
    inset 0 0 0 7px var(--bg-elevated),
    inset 0 0 0 8px var(--border);
  text-align: center;
  transform-origin: center bottom;
  transition:
    border-color 220ms ease,
    box-shadow 220ms ease,
    transform 220ms ease,
    background 220ms ease;
}

.domain-button::before,
.domain-button::after {
  position: absolute;
  border: 1px solid var(--border);
  border-radius: 50%;
  content: "";
  pointer-events: none;
}

.domain-button::before {
  top: 34px;
  left: 50%;
  width: 126px;
  height: 126px;
  transform: translateX(-50%);
}

.domain-button::after {
  top: 51px;
  left: 50%;
  width: 92px;
  height: 92px;
  transform: translateX(-50%);
}

.domain-button:hover {
  z-index: 1;
  transform: translateY(-9px) rotate(-1deg);
  border-color: var(--accent);
  background:
    radial-gradient(circle at 50% 36%, var(--accent-soft), transparent 42%),
    var(--bg-elevated);
  box-shadow:
    inset 0 0 0 7px var(--bg-elevated),
    inset 0 0 0 8px var(--accent-soft),
    0 24px 46px rgba(12, 52, 59, 0.14);
}

.domain-button:nth-child(even):hover {
  transform: translateY(-9px) rotate(1deg);
}

.domain-card-number {
  position: relative;
  z-index: 1;
  align-self: flex-start;
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.12em;
}

.domain-card-symbol {
  position: relative;
  z-index: 1;
  width: 104px;
  height: 104px;
  margin: 2px auto 8px;
  display: grid;
  place-items: center;
}

.domain-card-symbol > i {
  position: absolute;
  inset: 9px;
  border: 1px solid var(--accent);
  transform: rotate(45deg);
}

.domain-card-copy {
  position: relative;
  z-index: 1;
  display: grid;
  gap: 8px;
}

.domain-card-copy strong {
  font-size: 1.25rem;
  letter-spacing: 0.04em;
}

.domain-card-copy small {
  min-height: 2.7em;
}

.domain-card-action {
  position: relative;
  z-index: 1;
  margin-top: 18px;
  padding-top: 13px;
  display: flex;
  justify-content: space-between;
  border-top: 1px solid var(--border);
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.06em;
}

.option-icon {
  position: relative;
  z-index: 1;
  width: 52px;
  height: 52px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  color: var(--accent);
  background: var(--bg-elevated);
  font-family: Georgia, serif;
  font-size: 1.55rem;
}

.selected-context,
.answer-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.selected-context {
  margin-bottom: 38px;
}

.selected-context span,
.selected-context strong,
.answer-summary span {
  padding: 7px 10px;
  border: 1px solid var(--border);
  border-radius: 999px;
  color: var(--text-muted);
  background: var(--surface);
  font-size: 0.76rem;
}

.selected-context strong {
  color: var(--accent);
  border-color: var(--accent);
}

.back-button {
  margin-top: 28px;
  padding: 8px 0;
  border: 0;
  color: var(--text-muted);
  background: transparent;
  font-size: 0.86rem;
  font-weight: 750;
}

.back-button:hover {
  color: var(--accent);
}

.draw-panel {
  position: relative;
  z-index: 1;
  min-height: 550px;
  padding: 58px 8%;
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(260px, 0.65fr);
  align-items: center;
  gap: 80px;
}

.draw-copy {
  max-width: 600px;
}

.answer-summary {
  margin-top: 28px;
}

.draw-actions {
  margin-top: 34px;
  display: flex;
  align-items: center;
  gap: 24px;
}

.draw-actions .back-button {
  margin: 0;
}

.draw-button {
  min-width: 240px;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  border: 1px solid var(--accent);
  border-radius: var(--radius-md);
  color: var(--accent-contrast);
  background: var(--accent);
  font-weight: 800;
  box-shadow: 0 16px 32px var(--accent-soft);
}

.draw-button:hover {
  background: var(--accent-strong);
}

.deck-preview {
  position: relative;
  width: 210px;
  height: 330px;
  margin: auto;
  perspective: 900px;
}

.deck-card {
  position: absolute;
  inset: 0;
  border: 1px solid var(--accent);
  border-radius: 90px 90px 16px 16px;
  background: var(--bg-elevated);
}

.deck-card-back {
  z-index: 3;
  display: grid;
  place-items: center;
  box-shadow:
    inset 0 0 0 8px var(--bg-elevated),
    inset 0 0 0 9px var(--border-strong),
    0 28px 56px rgba(11, 50, 58, 0.15);
  color: var(--accent);
  font-size: 2.4rem;
  transform: rotate(2deg);
}

.deck-card-back i {
  position: absolute;
  width: 112px;
  height: 112px;
  border: 1px solid var(--border);
  border-radius: 50%;
}

.deck-card-middle {
  z-index: 2;
  transform: translate(-10px, 7px) rotate(-3deg);
}

.deck-card-bottom {
  z-index: 1;
  transform: translate(-18px, 13px) rotate(-6deg);
}

.reading-result {
  scroll-margin-top: 100px;
}

.result-header {
  margin-bottom: 32px;
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 32px;
}

.result-header h2 {
  max-width: 780px;
}

.result-header button {
  flex: 0 0 auto;
  padding: 11px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  background: var(--surface);
  font-weight: 750;
}

.reading-intro {
  position: relative;
  overflow: hidden;
  padding: 42px clamp(28px, 6vw, 72px);
}

.reading-intro p {
  position: relative;
  z-index: 1;
  max-width: 850px;
  margin: 0;
  font-size: 1.04rem;
  line-height: 1.9;
}

.reading-intro p + p {
  margin-top: 14px;
  color: var(--text-muted);
}

.intro-mark {
  position: absolute;
  top: -30px;
  right: 30px;
  color: var(--accent-soft);
  font-family: Georgia, serif;
  font-size: 12rem;
  line-height: 1;
}

.card-reading-grid {
  margin-top: 28px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
}

.reading-card {
  padding: 22px;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  animation: reveal-card 600ms both;
  animation-delay: var(--reveal-delay);
}

.tarot-card {
  position: relative;
  width: min(100%, 238px);
  aspect-ratio: 0.67;
  margin: 4px auto 28px;
  padding: 22px 18px;
  display: flex;
  align-items: center;
  flex-direction: column;
  justify-content: space-between;
  border: 1px solid var(--accent);
  border-radius: 90px 90px 14px 14px;
  background:
    radial-gradient(circle at 50% 42%, var(--accent-soft), transparent 38%),
    var(--bg-elevated);
  box-shadow: inset 0 0 0 7px var(--bg-elevated), inset 0 0 0 8px var(--border);
}

.tarot-card.reversed .card-symbol {
  transform: rotate(180deg);
}

.card-number,
.card-orientation {
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.card-symbol {
  color: var(--accent);
  font-family: Georgia, serif;
  font-size: clamp(3.2rem, 6vw, 5rem);
  transition: transform 300ms ease;
}

.tarot-card strong {
  font-size: 1.12rem;
  letter-spacing: 0.04em;
}

.position-label {
  display: flex;
  align-items: center;
  gap: 10px;
}

.position-label span {
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.7rem;
  font-weight: 800;
}

.card-copy > p,
.card-action p {
  color: var(--text-muted);
  font-size: 0.91rem;
  line-height: 1.75;
}

.card-action {
  margin-top: 22px;
  padding-top: 18px;
  border-top: 1px solid var(--border);
}

.card-action span {
  color: var(--accent);
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.card-action p {
  margin-bottom: 0;
}

.action-panel {
  margin-top: 28px;
  padding: 34px clamp(24px, 5vw, 54px);
  display: grid;
  grid-template-columns: minmax(220px, 0.65fr) 1fr;
  gap: 52px;
}

.action-panel h3 {
  margin: 0;
  font-size: 1.8rem;
  letter-spacing: -0.035em;
}

.action-panel ol {
  margin: 0;
  padding: 0;
  display: grid;
  gap: 12px;
  list-style: none;
  counter-reset: action;
}

.action-panel li {
  position: relative;
  padding: 13px 16px 13px 50px;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  background: var(--bg-elevated);
  line-height: 1.6;
  counter-increment: action;
}

.action-panel li::before {
  position: absolute;
  top: 14px;
  left: 17px;
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.72rem;
  font-weight: 800;
  content: "0" counter(action);
}

.reading-disclaimer {
  max-width: 760px;
  margin: 24px auto 0;
  color: var(--text-faint);
  font-size: 0.78rem;
  line-height: 1.65;
  text-align: center;
}

@keyframes reveal-card {
  from {
    opacity: 0;
    transform: translateY(24px) rotateY(8deg);
  }
}

@media (max-width: 900px) {
  .tarot-hero,
  .draw-panel {
    grid-template-columns: 1fr;
  }

  .tarot-hero {
    gap: 16px;
  }

  .draw-panel {
    gap: 54px;
  }

  .deck-preview {
    order: -1;
    width: 160px;
    height: 250px;
  }

  .card-reading-grid {
    grid-template-columns: 1fr;
  }

  .reading-card {
    display: grid;
    grid-template-columns: minmax(180px, 0.55fr) 1fr;
    align-items: center;
    gap: 28px;
  }

  .tarot-card {
    margin-bottom: 4px;
  }
}

@media (max-width: 760px) {
  .domain-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .tarot-hero {
    margin-bottom: 38px;
  }

  .reading-flow {
    min-height: 0;
  }

  .flow-progress {
    padding: 18px 18px 0;
  }

  .question-panel,
  .draw-panel {
    padding: 42px 18px 36px;
  }

  .option-grid:not(.domain-grid),
  .reading-card,
  .action-panel {
    grid-template-columns: 1fr;
  }

  .option-button {
    min-height: 96px;
  }

  .domain-grid {
    margin-top: 36px;
    gap: 18px 14px;
  }

  .domain-button {
    min-height: 248px;
    padding: 18px 14px 16px;
    border-radius: 68px 68px 16px 16px;
  }

  .domain-button::before {
    top: 30px;
    width: 104px;
    height: 104px;
  }

  .domain-button::after {
    top: 45px;
    width: 74px;
    height: 74px;
  }

  .domain-card-symbol {
    width: 82px;
    height: 82px;
    margin-bottom: 5px;
  }

  .domain-card-symbol > i {
    inset: 8px;
  }

  .domain-card-copy strong {
    font-size: 1.08rem;
  }

  .domain-card-copy small {
    min-height: 3.8em;
    font-size: 0.75rem;
  }

  .domain-card-action {
    margin-top: 12px;
    padding-top: 10px;
    font-size: 0.61rem;
  }

  .draw-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .draw-actions .back-button {
    align-self: flex-start;
  }

  .result-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .reading-card {
    gap: 12px;
  }

  .tarot-card {
    width: min(76vw, 250px);
  }

  .action-panel {
    gap: 24px;
  }
}
</style>
