import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './core/router'
import { useAuthStore } from './modules/auth/stores/authStore'
import './style.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

useAuthStore(pinia)
  .restore()
  .finally(() => app.mount('#app'))
