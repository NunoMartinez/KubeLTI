import { createRouter, createWebHistory } from 'vue-router'
import HomeComponent from '@/components/HomeComponent.vue'
import LaravelTester from '@/components/LaravelTester.vue'
import WebSocketTester from '@/components/WebSocketTester.vue'
import Login from '@/components/Login.vue'
import Dashboard from '@/views/Dashboard.vue'
import { useAuthStore } from '@/stores/auth'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Pods from '@/views/Pods.vue'
import Services from '@/views/Services.vue'
import Namespaces from '@/views/Namespaces.vue'
import Deployments from '@/views/Deployments.vue'
import Ingresses from '@/views/Ingresses.vue'
import Nodes from '@/views/Nodes.vue'



const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: DashboardLayout,
      children: [
        {
          path: '', // this would be http://localhost:5173/
          component: HomeComponent
        },
        {
          path: 'dashboard', // http://localhost:5173/dashboard
          name: 'dashboard',
          component: Dashboard,
          meta: { requiresAuth: true }
        },
        {
          path: '/pods',
          component: Pods,
          meta: { requiresAuth: true }
        },
        {
          path: '/services',
          component: Services,
          meta: { requiresAuth: true }
        },
        {
          path: '/namespaces',
          component: Namespaces,
          meta: { requiresAuth: true }
        },
        {
          path: '/deployments',
          component: Deployments,
          meta: { requiresAuth: true }
        },

        {
          path: '/ingresses',
          component: Ingresses,
          meta: { requiresAuth: true }
        },
        {
          path: '/nodes',
          component: Nodes,
          meta: { requiresAuth: true }
        },

        {
          path: 'testers/laravel',
          component: LaravelTester
        },
        {
          path: 'testers/websocket',
          component: WebSocketTester
        }
      ]
    },
    {
      path: '/login',
      name: 'login',
      component: Login
    }
  ]
})


router.beforeEach((to, from, next) => {
  const auth = useAuthStore()
  const isLoggedIn = !!auth.user

  if (to.meta.requiresAuth && !isLoggedIn) {
    next('/login')
  } else if (to.path === '/login' && isLoggedIn) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
