import { createRouter, createWebHistory } from 'vue-router'
import homeRoutes from '../../modules/home/routes'
import lotteryRoutes from '../../modules/lottery/routes'
import tarotRoutes from '../../modules/tarot/routes'
import labRoutes from '../../modules/lab/routes'
import NotFoundView from '../views/NotFoundView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    ...homeRoutes,
    ...lotteryRoutes,
    ...tarotRoutes,
    ...labRoutes,
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: NotFoundView,
    },
  ],
  scrollBehavior: () => ({ top: 0 }),
})

export default router
