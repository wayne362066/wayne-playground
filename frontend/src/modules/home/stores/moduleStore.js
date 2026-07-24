import { defineStore } from 'pinia'
import { fetchModules } from '../services/moduleService'

export const useModuleStore = defineStore('modules', {
  state: () => ({
    items: [],
    loading: false,
    error: '',
  }),
  actions: {
    async load() {
      this.loading = true
      this.error = ''

      try {
        this.items = await fetchModules()
      } catch (error) {
        this.error = error.response?.data?.message || '目前無法載入模組，請稍後再試。'
      } finally {
        this.loading = false
      }
    },
  },
})
