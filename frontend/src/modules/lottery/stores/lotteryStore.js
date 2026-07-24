import { defineStore } from 'pinia'
import { generatePowerLottery } from '../services/lotteryService'

export const useLotteryStore = defineStore('lottery', {
  state: () => ({
    draws: [],
    generatedAt: '',
    loading: false,
    error: '',
  }),
  actions: {
    async generate(count) {
      this.loading = true
      this.error = ''

      try {
        const result = await generatePowerLottery(count)
        this.draws = result.draws
        this.generatedAt = result.generated_at
      } catch (error) {
        const validationMessage = error.response?.data?.errors?.count?.[0]
        this.error = validationMessage || error.response?.data?.message || '號碼產生失敗，請稍後再試。'
      } finally {
        this.loading = false
      }
    },
  },
})
