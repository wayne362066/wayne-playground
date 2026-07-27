import { defineStore } from 'pinia'
import {
  fetchCurrentAccount,
  fetchCurrentPermissions,
  loginAccount,
  logoutAccount,
  registerAccount,
} from '../services/authService'

function errorMessage(error, fallback) {
  const errors = error.response?.data?.errors
  const firstValidationError = errors
    ? Object.values(errors).flat().find(Boolean)
    : null

  return firstValidationError || error.response?.data?.message || fallback
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    permissions: [],
    loading: false,
    initialized: false,
    error: '',
  }),
  actions: {
    async restore() {
      this.loading = true
      this.error = ''

      try {
        const [user, permissions] = await Promise.all([
          fetchCurrentAccount(),
          fetchCurrentPermissions(),
        ])
        this.user = user
        this.permissions = permissions
      } catch {
        this.user = null
        this.permissions = []
      } finally {
        this.loading = false
        this.initialized = true
      }
    },
    async register(payload) {
      return this.runAuthentication(
        () => registerAccount(payload),
        '帳戶建立失敗，請稍後再試。',
      )
    },
    async login(payload) {
      return this.runAuthentication(
        () => loginAccount(payload),
        '登入失敗，請稍後再試。',
      )
    },
    async logout() {
      this.loading = true
      this.error = ''

      try {
        await logoutAccount()
        this.user = null
        this.permissions = await fetchCurrentPermissions()
        return true
      } catch (error) {
        this.error = errorMessage(error, '登出失敗，請稍後再試。')
        return false
      } finally {
        this.loading = false
      }
    },
    async runAuthentication(callback, fallbackMessage) {
      this.loading = true
      this.error = ''

      try {
        this.user = await callback()
        this.permissions = this.user?.permissions || []
        return true
      } catch (error) {
        this.error = errorMessage(error, fallbackMessage)
        return false
      } finally {
        this.loading = false
      }
    },
    can(permission) {
      return this.permissions.includes(permission)
    },
  },
})
