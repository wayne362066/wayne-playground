import AuthView from './views/AuthView.vue'
import ProfileView from './views/ProfileView.vue'

export default [
  {
    path: '/login',
    name: 'login',
    component: AuthView,
  },
  {
    path: '/register',
    name: 'register',
    component: AuthView,
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true },
  },
]
