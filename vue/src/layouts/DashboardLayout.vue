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
      <!-- Top navbar with hamburger and user dropdown -->
      <div class="bg-white border-b border-gray-200 flex items-center justify-between px-4 py-3 shadow-sm">
        <div class="flex items-center">
          <button 
            @click="toggleSidebar" 
            class="text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
            aria-label="Toggle sidebar"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <h1 class="ml-4 text-lg font-semibold text-gray-800">Kubernetes LTI</h1>
        </div>
        
        <!-- User profile dropdown -->
        <div class="relative">
          <button 
            @click="toggleUserDropdown" 
            class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full p-1"
            aria-expanded="false"
            :aria-label="isUserDropdownOpen ? 'Close user menu' : 'Open user menu'"
          >
            <img 
              :src="auth.userPhotoUrl" 
              alt="User avatar" 
              class="h-8 w-8 rounded-full object-cover border border-gray-300"
            />
            <span class="hidden md:inline-block font-medium">{{ auth.userFirstLastName }}</span>
            <svg 
              xmlns="http://www.w3.org/2000/svg" 
              class="h-5 w-5" 
              :class="{ 'transform rotate-180': isUserDropdownOpen }"
              fill="none" 
              viewBox="0 0 24 24" 
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          
          <!-- Dropdown menu -->
          <div 
            v-if="isUserDropdownOpen" 
            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 ring-1 ring-black ring-opacity-5 z-50"
            role="menu"
          >
            <div class="px-4 py-2 border-b border-gray-100">
              <p class="text-sm font-medium text-gray-900">{{ auth.userName }}</p>
              <p class="text-xs text-gray-500 truncate">{{ auth.userEmail }}</p>
            </div>
            <router-link 
              to="/profile" 
              class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" 
              role="menuitem"
              @click="isUserDropdownOpen = false"
            >
              Your Profile
            </router-link>
            <!-- Admin only - Create User link -->
            <router-link 
              v-if="auth.userType === 'A'"
              to="/users/create" 
              class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" 
              role="menuitem"
              @click="isUserDropdownOpen = false"
            >
              Create User
            </router-link>
            <a 
              href="#" 
              class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" 
              role="menuitem"
              @click.prevent="handleLogout"
            >
              Sign out
            </a>
          </div>
        </div>
      </div>
      
      <!-- Page content -->
      <div class="flex-1 overflow-auto">
        <router-view />
      </div>
    </div>
    
    <!-- Overlay to close dropdown when clicking outside -->
    <div 
      v-if="isUserDropdownOpen" 
      @click="isUserDropdownOpen = false"
      class="fixed inset-0 z-40"
    ></div>
  </div>
</template>

<script setup>
import Sidebar from '@/components/Sidebar.vue'
import { provide, ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const router = useRouter()
const auth = useAuthStore()

// Sidebar state - always closed by default
const isSidebarOpen = ref(false)

// User dropdown state
const isUserDropdownOpen = ref(false)

// Toggle sidebar function
const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const closeSidebar = () => {
  isSidebarOpen.value = false
}

// Toggle user dropdown
const toggleUserDropdown = () => {
  isUserDropdownOpen.value = !isUserDropdownOpen.value
}

// Handle logout
const handleLogout = async () => {
  isUserDropdownOpen.value = false
  try {
    await auth.logout()
    // First try the router navigation
    router.push('/login')
    
    // As a fallback, use direct browser navigation after a short delay
    setTimeout(() => {
      if (window.location.pathname !== '/login') {
        console.log('Fallback redirect to login')
        window.location.href = '/login'
      }
    }, 100)
  } catch (error) {
    console.error('Logout error:', error)
    // If anything fails, force redirect
    window.location.href = '/login'
  }
}

// Close dropdown when pressing Escape key
const handleEscapeKey = (event) => {
  if (event.key === 'Escape') {
    isUserDropdownOpen.value = false
  }
}

// Add and remove event listeners
onMounted(() => {
  document.addEventListener('keydown', handleEscapeKey)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleEscapeKey)
})

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
