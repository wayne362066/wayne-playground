import AccessManagementView from './views/AccessManagementView.vue'

export default [
  {
    path: '/admin/access',
    name: 'access-management',
    component: AccessManagementView,
    meta: { permission: 'access.manage' },
  },
]
