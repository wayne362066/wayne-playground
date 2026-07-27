import LotteryView from './views/LotteryView.vue'

export default [
  {
    path: '/lottery',
    name: 'lottery',
    component: LotteryView,
    meta: { permission: 'modules.lottery.view' },
  },
]
