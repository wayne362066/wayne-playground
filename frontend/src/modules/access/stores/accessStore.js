import { defineStore } from 'pinia'
import {
  createRole,
  deleteRole,
  fetchAccessManagement,
  updateRole,
  updateUserRoles,
} from '../services/accessService'

function errorMessage(error, fallback) {
  const errors = error.response?.data?.errors
  const firstValidationError = errors
    ? Object.values(errors).flat().find(Boolean)
    : null

  return firstValidationError || error.response?.data?.message || fallback
}

export const useAccessStore = defineStore('access-management', {
  state: () => ({
    roles: [],
    permissions: [],
    users: [],
    audits: [],
    loading: false,
    saving: false,
    error: '',
  }),
  actions: {
    async load() {
      this.loading = true
      this.error = ''

      try {
        const data = await fetchAccessManagement()
        this.roles = data.roles
        this.permissions = data.permissions
        this.users = data.users
        this.audits = data.audits
      } catch (error) {
        this.error = errorMessage(error, '權限資料載入失敗。')
      } finally {
        this.loading = false
      }
    },
    async createRole(payload) {
      return this.runMutation(
        () => createRole(payload),
        '角色建立失敗。',
      )
    },
    async saveRole(role) {
      return this.runMutation(
        () => updateRole(role.id, {
          name: role.name,
          description: role.description || null,
          permission_keys: role.permission_keys,
        }),
        '角色權限儲存失敗。',
      )
    },
    async removeRole(role) {
      return this.runMutation(
        () => deleteRole(role.id),
        '角色刪除失敗。',
      )
    },
    async saveUser(user) {
      return this.runMutation(
        () => updateUserRoles(user.id, user.role_keys),
        '使用者角色儲存失敗。',
      )
    },
    async runMutation(callback, fallback) {
      this.saving = true
      this.error = ''

      try {
        await callback()
        await this.load()
        return true
      } catch (error) {
        this.error = errorMessage(error, fallback)
        return false
      } finally {
        this.saving = false
      }
    },
  },
})
