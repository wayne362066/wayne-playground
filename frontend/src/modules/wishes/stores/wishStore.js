import { defineStore } from 'pinia'
import {
  createWish,
  deleteWish,
  fetchManagedWishes,
  fetchWishes,
  restoreWish,
  updateWish,
} from '../services/wishService'

function errorMessage(error, fallback) {
  const errors = error.response?.data?.errors
  const firstValidationError = errors
    ? Object.values(errors).flat().find(Boolean)
    : null

  return firstValidationError || error.response?.data?.message || fallback
}

export const useWishStore = defineStore('wishes', {
  state: () => ({
    items: [],
    loading: false,
    saving: false,
    error: '',
    managementMode: false,
  }),
  actions: {
    async load(managementMode = this.managementMode) {
      this.loading = true
      this.error = ''
      this.managementMode = managementMode

      try {
        this.items = managementMode
          ? await fetchManagedWishes()
          : await fetchWishes()
      } catch (error) {
        this.error = errorMessage(error, '願望載入失敗，請稍後再試。')
      } finally {
        this.loading = false
      }
    },
    async create(payload) {
      return this.runMutation(
        () => createWish(payload),
        '願望送出失敗，請稍後再試。',
      )
    },
    async update(id, payload) {
      return this.runMutation(
        () => updateWish(id, payload),
        '願望更新失敗，請稍後再試。',
      )
    },
    async remove(id) {
      return this.runMutation(
        () => deleteWish(id),
        '願望封存失敗，請稍後再試。',
      )
    },
    async restore(id) {
      return this.runMutation(
        () => restoreWish(id),
        '願望恢復失敗，請稍後再試。',
      )
    },
    async runMutation(callback, fallbackMessage) {
      this.saving = true
      this.error = ''

      try {
        await callback()
        await this.load(this.managementMode)
        return true
      } catch (error) {
        this.error = errorMessage(error, fallbackMessage)
        return false
      } finally {
        this.saving = false
      }
    },
  },
})
