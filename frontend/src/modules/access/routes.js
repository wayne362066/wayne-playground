import AccessManagementView from './views/AccessManagementView.vue'
import CreateRoleView from './views/CreateRoleView.vue'

export default [
  {
    path: '/admin/access',
    name: 'access-management',
    component: AccessManagementView,
    meta: { permission: 'access.manage' },
  },
  {
    path: '/admin/access/roles/create',
    name: 'access-role-create',
    component: CreateRoleView,
    meta: { permission: 'access.manage' },
  },
]
