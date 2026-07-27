import TarotView from './views/TarotView.vue'

export default [
  {
    path: '/tarot',
    name: 'tarot',
    component: TarotView,
    meta: { permission: 'modules.tarot.view' },
  },
]
