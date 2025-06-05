<template>
  <aside 
    :class="[
      'fixed top-0 left-0 h-screen bg-white border-r border-gray-200 shadow-lg z-30 transition-transform duration-300 ease-in-out',
      isOpen ? 'translate-x-0' : '-translate-x-full'
    ]"
    class="w-64"
  >
    <!-- Sidebar Header -->
    <div class="p-4 flex items-center justify-between border-b border-gray-200">
      <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7" />
        </svg>
        <span>Kubernetes</span>
      </h1>
      
      <!-- Close button -->
      <button 
        @click="$emit('close')" 
        class="text-gray-500 hover:text-gray-700 focus:outline-none"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Navigation Links -->
    <nav class="p-4 space-y-1 flex-1 overflow-y-auto">
      <RouterLink 
        v-for="link in links" :key="link.to" :to="link.to"
        class="flex items-center gap-3 p-3 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors"
        :class="{ 'bg-blue-50 text-blue-600': $route.path === link.to }"
        @click="$emit('close')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
        </svg>
        <span class="font-medium">{{ link.text }}</span>
      </RouterLink>
    </nav>

    <!-- Sidebar Footer -->
    <div class="p-4 mt-auto border-t border-gray-200">
      <div class="text-xs text-gray-500 mb-2">Cluster API Version</div>
      <div class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium text-gray-700">
        v{{ clusterVersion }}
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { inject } from 'vue'

// Props
const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['close'])

// Inject shared state
const clusterInfo = inject('clusterInfo')
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
