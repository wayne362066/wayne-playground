import LotteryView from './views/LotteryView.vue'
import LotteryDuelView from './views/LotteryDuelView.vue'

export default [
  {
    path: '/lottery',
    name: 'lottery',
    component: LotteryView,
    meta: { permission: 'modules.lottery.view' },
  },
  {
    path: '/lottery/duels',
    name: 'lottery-duels',
    component: LotteryDuelView,
    meta: { permission: 'lottery.duel' },
  },
]
