<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { realtime } from '../../../core/realtime/echo'
import { useAuthStore } from '../../auth/stores/authStore'
import { useDuelStore } from '../stores/duelStore'

const modes = [
  { value: 'single', title: '模擬一期', short: '比本期獎金' },
  { value: 'until_profit', title: '直到單期獲利', short: '比首次獲利期數' },
  { value: 'until_jackpot', title: '直到中頭獎', short: '比首次頭獎期數' },
]

const statuses = {
  waiting: '等待對手',
  ready: '等待雙方準備',
  running: '開獎計算中',
  finished: '本局結束',
}

const authStore = useAuthStore()
const store = useDuelStore()
const { rooms, current, loading, error } = storeToRefs(store)
const nickname = ref(authStore.user?.display_name || '')
const selectedMode = ref('single')
const ticketCount = ref(10)
const connection = ref('connecting')
let echo = null
let lobbyChannel = null
let roomChannel = null
let heartbeatTimer = null
let refreshTimer = null

const selfSeat = computed(() => current.value?.self_seat)
const opponentSeat = computed(() => selfSeat.value === 'seat_1' ? 'seat_2' : 'seat_1')
const selfPlayer = computed(() => current.value?.players?.[selfSeat.value])
const opponent = computed(() => current.value?.players?.[opponentSeat.value])
const selectedModeInfo = computed(
  () => modes.find((mode) => mode.value === selectedMode.value),
)
const roomMode = computed(
  () => modes.find((mode) => mode.value === current.value?.mode),
)
const canReady = computed(
  () => current.value?.status === 'ready'
    && selfPlayer.value?.active
    && !selfPlayer.value?.ready,
)
const canRematch = computed(
  () => current.value?.status === 'finished'
    && selfPlayer.value?.active
    && opponent.value?.active
    && !current.value.rematch_votes.includes(selfSeat.value),
)

watch(
  () => authStore.user?.display_name,
  (displayName) => {
    if (displayName) nickname.value = displayName
  },
)

watch(
  () => current.value?.id,
  (roomId, previousRoomId) => {
    if (previousRoomId && echo) {
      echo.leave(`lottery.duel.${previousRoomId}`)
    }
    roomChannel = null
    stopHeartbeat()

    if (roomId) {
      subscribeRoom(roomId)
      heartbeatTimer = window.setInterval(() => store.heartbeat(), 5000)
    }
  },
)

onMounted(async () => {
  await Promise.all([store.restore(), store.loadRooms()])
  connectRealtime()
  if (current.value?.id) {
    subscribeRoom(current.value.id)
  }
  refreshTimer = window.setInterval(() => store.loadRooms(), 30000)
})

onBeforeUnmount(() => {
  stopHeartbeat()
  if (refreshTimer) window.clearInterval(refreshTimer)
  if (echo && current.value?.id) echo.leave(`lottery.duel.${current.value.id}`)
  if (echo) echo.leaveChannel('lottery.duel.lobby')
})

function connectRealtime() {
  echo = realtime()
  if (!echo) {
    connection.value = 'polling'
    return
  }

  lobbyChannel = echo
    .channel('lottery.duel.lobby')
    .listen('.lottery.duel.lobby.changed', () => store.loadRooms())

  connection.value = 'connected'
}

function subscribeRoom(roomId) {
  if (!echo || roomChannel) return

  roomChannel = echo
    .join(`lottery.duel.${roomId}`)
    .here(() => {
      connection.value = 'connected'
    })
    .joining(() => store.refreshCurrent())
    .leaving(() => store.refreshCurrent())
    .error(() => {
      connection.value = 'reconnecting'
    })
    .listen('.lottery.duel.room.changed', async (event) => {
      if (event.event?.startsWith('closed:')) {
        await store.restore()
        await store.loadRooms()
        return
      }

      if (!current.value || event.version > current.value.version) {
        await store.refreshCurrent()
      }
    })
}

function stopHeartbeat() {
  if (heartbeatTimer) window.clearInterval(heartbeatTimer)
  heartbeatTimer = null
}

async function createRoom() {
  await store.create({
    nickname: authStore.user ? null : nickname.value,
    ticket_count: Number(ticketCount.value),
    mode: selectedMode.value,
  })
}

async function joinRoom(roomId) {
  await store.join(roomId, {
    nickname: authStore.user ? null : nickname.value,
  })
}

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

function resultFor(seat) {
  return current.value?.result?.players?.[seat]
}

function outcomeLabel(seat) {
  const outcome = resultFor(seat)?.outcome
  return { win: '勝利', loss: '落敗', draw: '平手' }[outcome] || ''
}

function finishReason() {
  return {
    player_left: '對手離開遊戲',
    disconnect_timeout: '對手斷線超過 15 秒',
    simulation: '模擬結果',
  }[current.value?.result?.reason] || ''
}
</script>

<template>
  <section class="duel-page">
    <div class="container page-section">
      <header class="duel-hero">
        <div>
          <RouterLink class="back-link" to="/lottery">← 返回威力彩</RouterLink>
          <p class="eyebrow">POWER LOTTERY / LIVE DUEL</p>
          <h1 class="page-title">兩個人的<br><em>機率對決</em></h1>
        </div>
        <div class="hero-status">
          <span class="live-dot" :class="connection" />
          <div>
            <strong>公開 1v1 房間</strong>
            <p>雙方準備後由伺服器開獎，斷線保留 15 秒。</p>
          </div>
        </div>
      </header>

      <p v-if="error" class="duel-error" role="alert">{{ error }}</p>

      <template v-if="!current">
        <section class="duel-layout">
          <form class="panel create-panel" @submit.prevent="createRoom">
            <div class="section-kicker">CREATE ROOM</div>
            <h2>建立公開房間</h2>
            <p>房間建立後會立即出現在大廳，30 分鐘無操作自動關閉。</p>

            <label v-if="!authStore.user" class="field">
              <span>你的暱稱</span>
              <input
                v-model.trim="nickname"
                maxlength="40"
                autocomplete="nickname"
                placeholder="輸入暱稱"
                required
              >
            </label>
            <div v-else class="member-name">
              <span>使用身分</span>
              <strong>{{ authStore.user.display_name }}</strong>
            </div>

            <div class="mode-options">
              <label
                v-for="mode in modes"
                :key="mode.value"
                :class="{ selected: selectedMode === mode.value }"
              >
                <input v-model="selectedMode" type="radio" :value="mode.value">
                <strong>{{ mode.title }}</strong>
                <small>{{ mode.short }}</small>
              </label>
            </div>

            <label class="field">
              <span>每期購買注數</span>
              <div class="count-input">
                <input
                  v-model.number="ticketCount"
                  type="number"
                  min="1"
                  max="10000"
                  required
                >
                <small>注 / 期</small>
              </div>
            </label>

            <p class="selection">{{ selectedModeInfo.short }} · 雙方注數相同</p>
            <button class="primary-button" type="submit" :disabled="loading">
              {{ loading ? '建立中…' : '建立 1v1 房間' }}
            </button>
          </form>

          <section class="panel lobby-panel">
            <div class="lobby-heading">
              <div>
                <div class="section-kicker">OPEN ROOMS</div>
                <h2>公開房間</h2>
              </div>
              <button class="text-button" type="button" @click="store.loadRooms">重新整理</button>
            </div>

            <div v-if="rooms.length" class="room-list">
              <article v-for="room in rooms" :key="room.id" class="room-card">
                <div class="room-owner">
                  <span class="avatar">{{ room.host_nickname.slice(0, 1).toUpperCase() }}</span>
                  <div>
                    <strong>{{ room.host_nickname }}</strong>
                    <small>等待挑戰者</small>
                  </div>
                </div>
                <div class="room-rule">
                  <strong>{{ modes.find((mode) => mode.value === room.mode)?.title }}</strong>
                  <span>{{ formatNumber(room.ticket_count) }} 注 / 期</span>
                </div>
                <button
                  class="join-button"
                  type="button"
                  :disabled="loading || (!authStore.user && !nickname)"
                  @click="joinRoom(room.id)"
                >
                  加入
                </button>
              </article>
            </div>
            <div v-else class="empty-lobby">
              <span>目前沒有等待中的房間</span>
              <p>成為第一位開房的玩家。</p>
            </div>
          </section>
        </section>
      </template>

      <template v-else>
        <section class="panel active-room">
          <div class="room-topline">
            <div>
              <span class="room-code">ROOM {{ current.id.slice(-8).toUpperCase() }}</span>
              <h2>{{ statuses[current.status] }}</h2>
            </div>
            <div class="rule-badges">
              <span>{{ roomMode?.title }}</span>
              <span>{{ formatNumber(current.ticket_count) }} 注 / 期</span>
              <span>第 {{ current.game_number || 1 }} 局</span>
            </div>
          </div>

          <div class="versus-grid">
            <article
              v-for="seat in ['seat_1', 'seat_2']"
              :key="seat"
              class="player-card"
              :class="[
                resultFor(seat)?.outcome,
                { self: seat === selfSeat, empty: !current.players[seat] },
              ]"
            >
              <template v-if="current.players[seat]">
                <div class="player-label">
                  <span>{{ seat === selfSeat ? 'YOU' : 'OPPONENT' }}</span>
                  <span
                    class="presence"
                    :class="{ offline: !current.players[seat].connected }"
                  >
                    {{ current.players[seat].connected ? '連線中' : '等待重連' }}
                  </span>
                </div>
                <div class="player-avatar">{{ current.players[seat].nickname.slice(0, 1) }}</div>
                <h3>{{ current.players[seat].nickname }}</h3>
                <span
                  v-if="current.status === 'ready'"
                  class="ready-state"
                  :class="{ done: current.players[seat].ready }"
                >
                  {{ current.players[seat].ready ? '已準備' : '尚未準備' }}
                </span>
                <strong v-if="current.status === 'finished'" class="outcome">
                  {{ outcomeLabel(seat) }}
                </strong>
                <div v-if="resultFor(seat)?.attempts" class="player-result">
                  <template v-if="current.mode === 'single'">
                    <span>本期獎金</span>
                    <strong>{{ formatMoney(resultFor(seat).total_prize_money) }}</strong>
                    <small>淨損益 {{ formatMoney(resultFor(seat).net_profit) }}</small>
                  </template>
                  <template v-else>
                    <span>{{ resultFor(seat).completed ? '達成期數' : '運算上限' }}</span>
                    <strong>{{ formatNumber(resultFor(seat).attempts) }} 期</strong>
                    <small v-if="!resultFor(seat).completed">本局未達成條件</small>
                  </template>
                </div>
              </template>
              <template v-else>
                <div class="waiting-ring" />
                <h3>等待玩家加入</h3>
                <span>房間已顯示於公開大廳</span>
              </template>
            </article>

            <div class="versus-mark">VS</div>
          </div>

          <div v-if="current.status === 'running'" class="running-state">
            <span class="spinner" />
            <div>
              <strong>伺服器正在模擬開獎</strong>
              <p>完成後會透過 WebSocket 同步雙方結果。</p>
            </div>
          </div>

          <div v-if="current.status === 'finished'" class="finish-summary">
            <span>{{ finishReason() }}</span>
            <p v-if="current.result.winner === 'draw'">這一局平手。</p>
            <p v-else-if="current.result.winner === selfSeat">你贏得這一局。</p>
            <p v-else>這一局由對手獲勝。</p>
          </div>

          <div class="room-actions">
            <button
              v-if="canReady"
              class="primary-button"
              type="button"
              :disabled="loading"
              @click="store.ready"
            >
              我準備好了
            </button>
            <span v-else-if="current.status === 'ready' && selfPlayer?.ready" class="waiting-copy">
              已準備，等待對手
            </span>

            <button
              v-if="canRematch"
              class="primary-button"
              type="button"
              :disabled="loading"
              @click="store.rematch"
            >
              再來一局
            </button>
            <span
              v-else-if="current.status === 'finished' && current.rematch_votes.includes(selfSeat)"
              class="waiting-copy"
            >
              已邀請，等待對手同意
            </span>

            <button
              class="leave-button"
              type="button"
              :disabled="loading"
              @click="store.leave"
            >
              離開房間
            </button>
          </div>
        </section>

        <p class="disconnect-note">
          上一頁、重新整理或關閉網頁會保留座位 15 秒；遊戲中超時未回來將判負。
        </p>
      </template>
    </div>
  </section>
</template>

<style scoped>
.duel-page {
  min-height: 100vh;
  overflow: hidden;
  background:
    radial-gradient(circle at 85% 10%, color-mix(in srgb, var(--accent) 14%, transparent), transparent 28%),
    radial-gradient(circle at 10% 75%, color-mix(in srgb, #f6b94a 10%, transparent), transparent 25%);
}

.duel-hero {
  display: grid;
  grid-template-columns: 1fr minmax(280px, 0.55fr);
  align-items: end;
  gap: 72px;
}

.back-link {
  display: inline-block;
  margin-bottom: 32px;
  color: var(--text-muted);
  font-size: 0.85rem;
  text-decoration: none;
}

.duel-hero em {
  color: var(--accent);
  font-style: normal;
}

.hero-status {
  padding: 22px;
  display: flex;
  align-items: flex-start;
  gap: 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  background: var(--surface);
}

.hero-status p {
  margin: 5px 0 0;
  color: var(--text-muted);
  font-size: 0.84rem;
  line-height: 1.6;
}

.live-dot {
  width: 9px;
  height: 9px;
  flex: 0 0 auto;
  margin-top: 6px;
  border-radius: 50%;
  background: #e7a93e;
  box-shadow: 0 0 0 5px color-mix(in srgb, #e7a93e 15%, transparent);
}

.live-dot.connected {
  background: #48a779;
  box-shadow: 0 0 0 5px color-mix(in srgb, #48a779 15%, transparent);
}

.duel-error {
  margin: 28px 0 0;
  padding: 13px 16px;
  border: 1px solid color-mix(in srgb, #d75d5d 35%, var(--border));
  border-radius: 10px;
  color: #b94848;
  background: color-mix(in srgb, #d75d5d 8%, var(--surface));
}

.duel-layout {
  margin-top: 48px;
  display: grid;
  grid-template-columns: minmax(300px, 0.72fr) minmax(0, 1.28fr);
  gap: 20px;
  align-items: start;
}

.create-panel,
.lobby-panel,
.active-room {
  padding: 30px;
}

.section-kicker,
.room-code {
  color: var(--accent);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.13em;
}

.create-panel h2,
.lobby-panel h2,
.active-room h2 {
  margin: 6px 0 0;
  font-size: 1.65rem;
  letter-spacing: -0.035em;
}

.create-panel > p {
  margin: 8px 0 25px;
  color: var(--text-muted);
  font-size: 0.86rem;
  line-height: 1.6;
}

.field {
  margin-top: 20px;
  display: grid;
  gap: 8px;
  color: var(--text-muted);
  font-size: 0.78rem;
  font-weight: 700;
}

.field input {
  width: 100%;
  padding: 13px 14px;
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--text);
  background: var(--surface);
  font: inherit;
}

.member-name {
  padding: 14px 0;
  display: flex;
  justify-content: space-between;
  color: var(--text-muted);
  font-size: 0.82rem;
}

.member-name strong {
  color: var(--text);
}

.mode-options {
  margin-top: 18px;
  display: grid;
  gap: 8px;
}

.mode-options label {
  padding: 13px 14px;
  display: grid;
  grid-template-columns: 1fr auto;
  border: 1px solid var(--border);
  border-radius: 10px;
  cursor: pointer;
}

.mode-options label.selected {
  border-color: var(--accent);
  background: var(--accent-soft);
}

.mode-options input {
  position: absolute;
  opacity: 0;
}

.mode-options small {
  color: var(--text-muted);
}

.count-input {
  position: relative;
}

.count-input small {
  position: absolute;
  top: 50%;
  right: 13px;
  color: var(--text-faint);
  transform: translateY(-50%);
}

.selection {
  color: var(--text-faint) !important;
  font-size: 0.75rem !important;
}

.primary-button,
.join-button,
.leave-button,
.text-button {
  border: 0;
  border-radius: 10px;
  cursor: pointer;
  font: inherit;
  font-weight: 750;
}

.primary-button {
  width: 100%;
  padding: 14px 18px;
  color: white;
  background: var(--accent);
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.lobby-heading,
.room-topline,
.room-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
}

.text-button {
  padding: 8px;
  color: var(--accent);
  background: transparent;
}

.room-list {
  margin-top: 24px;
  display: grid;
  gap: 10px;
}

.room-card {
  padding: 16px;
  display: grid;
  grid-template-columns: minmax(150px, 1fr) minmax(145px, 0.75fr) auto;
  align-items: center;
  gap: 18px;
  border: 1px solid var(--border);
  border-radius: 12px;
}

.room-owner {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar {
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  color: var(--accent);
  background: var(--accent-soft);
  font-weight: 800;
}

.room-owner div,
.room-rule {
  display: grid;
  gap: 3px;
}

.room-owner small,
.room-rule span {
  color: var(--text-muted);
  font-size: 0.74rem;
}

.join-button {
  padding: 10px 17px;
  color: var(--accent);
  background: var(--accent-soft);
}

.empty-lobby {
  min-height: 250px;
  display: grid;
  place-content: center;
  text-align: center;
  color: var(--text-muted);
}

.empty-lobby p {
  margin: 6px 0 0;
  color: var(--text-faint);
  font-size: 0.82rem;
}

.active-room {
  margin-top: 48px;
}

.rule-badges {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.rule-badges span {
  padding: 7px 10px;
  border: 1px solid var(--border);
  border-radius: 999px;
  color: var(--text-muted);
  font-size: 0.72rem;
}

.versus-grid {
  position: relative;
  margin-top: 30px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.player-card {
  min-height: 310px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--border);
  border-radius: 16px;
  background: color-mix(in srgb, var(--surface) 92%, transparent);
  text-align: center;
}

.player-card.self {
  border-color: color-mix(in srgb, var(--accent) 48%, var(--border));
}

.player-card.win {
  border-color: #48a779;
  background: color-mix(in srgb, #48a779 8%, var(--surface));
}

.player-card.loss {
  opacity: 0.76;
}

.player-label {
  width: 100%;
  display: flex;
  justify-content: space-between;
  color: var(--text-faint);
  font-family: "SFMono-Regular", Consolas, monospace;
  font-size: 0.65rem;
  letter-spacing: 0.1em;
}

.presence {
  color: #38875f;
}

.presence.offline {
  color: #ca8335;
}

.player-avatar {
  width: 72px;
  height: 72px;
  margin: 30px 0 14px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  color: var(--accent);
  background: var(--accent-soft);
  font-size: 1.7rem;
  font-weight: 800;
}

.player-card h3 {
  margin: 0;
  font-size: 1.25rem;
}

.ready-state,
.outcome {
  margin-top: 13px;
  color: var(--text-faint);
  font-size: 0.8rem;
}

.ready-state.done,
.outcome {
  color: var(--accent);
}

.outcome {
  font-size: 1.25rem;
}

.player-result {
  margin-top: 22px;
  display: grid;
  gap: 4px;
}

.player-result span,
.player-result small {
  color: var(--text-muted);
  font-size: 0.72rem;
}

.player-result strong {
  font-size: 1.35rem;
}

.versus-mark {
  position: absolute;
  z-index: 2;
  top: 50%;
  left: 50%;
  width: 46px;
  height: 46px;
  display: grid;
  place-items: center;
  border: 1px solid var(--border);
  border-radius: 50%;
  color: var(--text);
  background: var(--surface);
  font-size: 0.75rem;
  font-weight: 900;
  transform: translate(-50%, -50%);
}

.waiting-ring,
.spinner {
  width: 42px;
  height: 42px;
  border: 2px solid var(--border);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.player-card.empty h3 {
  margin-top: 18px;
}

.player-card.empty span {
  margin-top: 6px;
  color: var(--text-muted);
  font-size: 0.8rem;
}

.running-state,
.finish-summary {
  margin-top: 18px;
  padding: 17px 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  border-radius: 12px;
  background: var(--accent-soft);
  text-align: center;
}

.spinner {
  width: 25px;
  height: 25px;
}

.running-state p,
.finish-summary p {
  margin: 3px 0 0;
  color: var(--text-muted);
  font-size: 0.8rem;
}

.finish-summary {
  display: grid;
  gap: 2px;
}

.room-actions {
  margin-top: 24px;
  justify-content: flex-end;
}

.room-actions .primary-button {
  width: auto;
  min-width: 160px;
}

.leave-button {
  padding: 13px 18px;
  color: var(--text-muted);
  background: transparent;
  border: 1px solid var(--border);
}

.waiting-copy {
  color: var(--text-muted);
  font-size: 0.82rem;
}

.disconnect-note {
  margin: 13px 0 0;
  color: var(--text-faint);
  font-size: 0.75rem;
  text-align: center;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 850px) {
  .duel-hero,
  .duel-layout {
    grid-template-columns: 1fr;
    gap: 24px;
  }

  .room-card {
    grid-template-columns: 1fr auto;
  }

  .room-rule {
    display: none;
  }
}

@media (max-width: 620px) {
  .create-panel,
  .lobby-panel,
  .active-room {
    padding: 20px;
  }

  .room-topline {
    align-items: flex-start;
    flex-direction: column;
  }

  .rule-badges {
    justify-content: flex-start;
  }

  .versus-grid {
    grid-template-columns: 1fr;
  }

  .player-card {
    min-height: 250px;
  }

  .versus-mark {
    top: 50%;
  }

  .room-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .room-actions .primary-button,
  .leave-button {
    width: 100%;
  }
}
</style>
