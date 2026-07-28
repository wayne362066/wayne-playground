import { defineStore } from 'pinia'
import {
  createDuelRoom,
  fetchCurrentDuelRoom,
  fetchDuelRoom,
  fetchDuelRooms,
  heartbeatDuelRoom,
  joinDuelRoom,
  leaveDuelRoom,
  readyDuelRoom,
  rematchDuelRoom,
} from '../services/duelService'

function message(error, fallback) {
  const validation = error.response?.data?.errors
    ? Object.values(error.response.data.errors).flat().find(Boolean)
    : null

  return validation || error.response?.data?.message || fallback
}

export const useDuelStore = defineStore('lottery-duel', {
  state: () => ({
    rooms: [],
    current: null,
    loading: false,
    error: '',
  }),
  actions: {
    async loadRooms() {
      try {
        this.rooms = await fetchDuelRooms()
        this.error = ''
      } catch (error) {
        this.error = message(error, '公開房間載入失敗。')
      }
    },
    async restore() {
      try {
        this.current = await fetchCurrentDuelRoom()
        this.error = ''
      } catch (error) {
        this.error = message(error, '無法恢復目前房間。')
      }
    },
    async refreshCurrent() {
      if (!this.current?.id) return

      try {
        this.current = await fetchDuelRoom(this.current.id)
        this.error = ''
      } catch (error) {
        if (error.response?.status === 404) {
          this.current = null
          await this.loadRooms()
          return
        }
        this.error = message(error, '房間狀態同步失敗。')
      }
    },
    async create(payload) {
      return this.run(
        async () => {
          this.current = await createDuelRoom(payload)
          await this.loadRooms()
        },
        '建立房間失敗。',
      )
    },
    async join(roomId, payload) {
      return this.run(
        async () => {
          this.current = await joinDuelRoom(roomId, payload)
          await this.loadRooms()
        },
        '加入房間失敗。',
      )
    },
    async ready() {
      return this.run(
        async () => {
          this.current = await readyDuelRoom(this.current.id)
        },
        '準備狀態更新失敗。',
      )
    },
    async heartbeat() {
      if (!this.current?.id) return

      try {
        const room = await heartbeatDuelRoom(this.current.id)
        if (room.version > this.current.version) {
          this.current = room
        }
      } catch (error) {
        if ([403, 404, 409].includes(error.response?.status)) {
          await this.restore()
        }
      }
    },
    async leave() {
      return this.run(
        async () => {
          await leaveDuelRoom(this.current.id)
          this.current = null
          await this.loadRooms()
        },
        '離開房間失敗。',
      )
    },
    async rematch() {
      return this.run(
        async () => {
          this.current = await rematchDuelRoom(this.current.id)
        },
        '再來一局狀態更新失敗。',
      )
    },
    async run(callback, fallback) {
      this.loading = true
      this.error = ''

      try {
        await callback()
        return true
      } catch (error) {
        this.error = message(error, fallback)
        return false
      } finally {
        this.loading = false
      }
    },
  },
})
