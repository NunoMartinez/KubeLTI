<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Overlay when sidebar is open -->
    <div 
      v-if="isSidebarOpen" 
      @click="closeSidebar"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 z-20 transition-opacity duration-300"
    ></div>
    
    <!-- Sidebar - always hidden by default, shown only when toggled -->
    <Sidebar 
      :is-open="isSidebarOpen" 
      @close="closeSidebar"
    />
    
    <!-- Main Content - always takes full width -->
    <div class="min-h-screen flex flex-col">
      <!-- Top navbar with hamburger -->
      <div class="bg-white border-b border-gray-200 flex items-center px-4 py-3 shadow-sm">
        <button 
          @click="toggleSidebar" 
          class="text-gray-600 hover:text-gray-900 focus:outline-none"
          aria-label="Toggle sidebar"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <h1 class="ml-4 text-lg font-semibold text-gray-800">Kubernetes LTI</h1>
      </div>
      
      <!-- Page content -->
      <div class="flex-1 overflow-auto">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import Sidebar from '@/components/Sidebar.vue'
import { provide, ref, onMounted } from 'vue'
import axios from 'axios'

// Sidebar state - always closed by default
const isSidebarOpen = ref(false)

// Toggle sidebar function
const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const closeSidebar = () => {
  isSidebarOpen.value = false
}

// Cluster info
const clusterInfo = ref({})
provide('clusterInfo', clusterInfo)

onMounted(async () => {
  try {
    const res = await axios.get('/kube/api')
    clusterInfo.value = res.data
  } catch (e) {
    console.error('Cluster info failed to load:', e)
  }
})
</script>
