<template>
  <div class="w-64 bg-white border-r border-gray-200 p-4 flex flex-col">
    <div class="mb-8">
      <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14..." />
        </svg>
        Kubernetes
      </h1>
    </div>

    <nav class="space-y-1 flex-1">
      <RouterLink 
        v-for="link in links" :key="link.to" :to="link.to"
        class="flex items-center gap-3 p-3 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors"
        :class="{ 'bg-blue-50 text-blue-600': $route.path === link.to }"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
        </svg>
        <span class="font-medium">{{ link.text }}</span>
      </RouterLink>
    </nav>

    <div class="mt-auto pt-4 border-t border-gray-200">
      <div class="text-xs text-gray-500 mb-2">Cluster API Version</div>
      <div class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium text-gray-700">
        v{{ clusterVersion }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { inject } from 'vue'

const clusterInfo = inject('clusterInfo') // shared from parent
const route = useRoute()

const clusterVersion = computed(() => clusterInfo?.value?.versions?.[0]?.split('v')[1] || '1.0')

const links = [
  { to: '/dashboard', text: 'Cluster Overview', icon: 'M4 5a1...' },
  { to: '/nodes', text: 'Nodes', icon: 'Server' },
  { to: '/namespaces', text: 'Namespaces', icon: 'M4 6a2...' },
  { to: '/pods', text: 'Pods', icon: 'M20 7l-8-4...' },
  { to: '/deployments', text: 'Deployments', icon: 'M9 17v-2...' },
  { to: '/services', text: 'Services', icon: 'M11 4a2...' },
  { to: '/ingresses', text: 'Ingress', icon: 'M13.828 10.172...' }
]
</script>
