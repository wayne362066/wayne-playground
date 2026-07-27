import AuthView from './views/AuthView.vue'

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
]
