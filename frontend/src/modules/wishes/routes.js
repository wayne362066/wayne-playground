import WishesView from './views/WishesView.vue'

export default [
  {
    path: '/wishes',
    name: 'wishes',
    component: WishesView,
    meta: { permission: 'modules.wishes.view' },
  },
]
