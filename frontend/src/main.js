import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router, { installAccessGuard } from './core/router'
import { setForbiddenHandler } from './core/api/http'
import { useAuthStore } from './modules/auth/stores/authStore'
import './style.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)

useAuthStore(pinia)
  .restore()
  .finally(() => {
    installAccessGuard(pinia)
    setForbiddenHandler(() => {
      if (router.currentRoute.value.name !== 'home') {
        router.replace({ name: 'home' })
      }
    })
    app.use(router)
    app.mount('#app')
  })
