import { createApp } from 'vue'
import { createPinia } from 'pinia'
import axios from 'axios'

import Toast, { POSITION } from 'vue-toastification'
import 'vue-toastification/dist/index.css'

import '@/assets/base.css'

import App from './App.vue'
import router from './router'

const app = createApp(App)

app.use(router)

app.use(createPinia())
const apiDomain = import.meta.env.VITE_API_DOMAIN
const wsConnection = import.meta.env.VITE_WS_CONNECTION

console.log('api domain', apiDomain)
console.log('ws connection', wsConnection)

axios.defaults.baseURL = `http://${apiDomain}/api`


app.provide('serverBaseUrl', apiDomain)

app.mount('#app')

// Import the CSS


const options = {
  position: POSITION.TOP_RIGHT,
  timeout: 3000,
  closeOnClick: true,
  pauseOnFocusLoss: true,
  pauseOnHover: true,
  draggable: true,
  draggablePercent: 0.6,
  showCloseButtonOnHover: false,
  hideProgressBar: false,
  closeButton: 'button',
  icon: true,
  rtl: false
}
app.use(Toast, options)






// Toast configuration

