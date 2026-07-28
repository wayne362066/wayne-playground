import { createRouter, createWebHistory } from 'vue-router'
import authRoutes from '../../modules/auth/routes'
import accessRoutes from '../../modules/access/routes'
import homeRoutes from '../../modules/home/routes'
import lotteryRoutes from '../../modules/lottery/routes'
import tarotRoutes from '../../modules/tarot/routes'
import wishRoutes from '../../modules/wishes/routes'
import NotFoundView from '../views/NotFoundView.vue'
import { useAuthStore } from '../../modules/auth/stores/authStore'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    ...homeRoutes,
    ...authRoutes,
    ...accessRoutes,
    ...lotteryRoutes,
    ...tarotRoutes,
    ...wishRoutes,
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: NotFoundView,
    },
  ],
  scrollBehavior: () => ({ top: 0 }),
})

export function installAccessGuard(pinia) {
  router.beforeEach((to) => {
    const authStore = useAuthStore(pinia)
    const permission = to.meta.permission

    if (to.meta.requiresAuth && !authStore.user) {
      return { name: 'login' }
    }

    if (permission && !authStore.can(permission)) {
      return { name: 'home' }
    }

    return true
  })
}

export default router
