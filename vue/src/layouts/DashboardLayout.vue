<template>
  <div class="min-h-screen bg-transparent flex justify-center" style="background-image: url('/bg.jpg'); background-size: cover; background-position: center;">
    <!-- Overlay when sidebar is open -->
    <transition name="fade">
      <div 
        v-if="isSidebarOpen" 
        @click="closeSidebar"
        class="fixed inset-0 bg-gray-800 bg-opacity-60 z-30 backdrop-blur-sm"
        aria-hidden="true"
        role="presentation"
      ></div>
    </transition>
    
    <!-- Sidebar - always hidden by default, shown only when toggled -->
    <Sidebar 
      :is-open="isSidebarOpen" 
      @close="closeSidebar"
    />
    
    <!-- Main Content - fixed width with margins on sides -->
    <div class="min-h-screen flex flex-col w-full max-w-7xl relative">
      <!-- Top navbar with hamburger and user dropdown -->
      <div class="bg-white border-b border-gray-200 flex items-center justify-between px-4 py-3 shadow-sm relative z-30">
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
          <router-link to="/dashboard" class="ml-4 text-lg font-semibold text-gray-800 hover:text-blue-600 transition-colors">
            Kubernetes LTI
          </router-link>
        </div>
        
        <!-- User profile dropdown - Completely rebuilt -->
        <div class="relative inline-block text-left">
          <button 
            @click="toggleUserDropdown" 
            class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full p-1"
            type="button"
            aria-haspopup="true"
            :aria-expanded="isUserDropdownOpen"
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
          
          <div 
            v-show="isUserDropdownOpen" 
            class="absolute right-0 mt-2 w-48 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
            @click.stop
          >
            <div class="px-4 py-2 border-b border-gray-100">
              <p class="text-sm font-medium text-gray-900">{{ auth.userName }}</p>
              <p class="text-xs text-gray-500 truncate">{{ auth.userEmail }}</p>
            </div>
            <div class="py-1">
              <router-link 
                to="/profile" 
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" 
                @click="isUserDropdownOpen = false"
              >
                Your Profile
              </router-link>
              <router-link 
                v-if="auth.userType === 'A'"
                to="/users/create" 
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                @click="isUserDropdownOpen = false"
              >
                Create User
              </router-link>
              <button
                type="button"
                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                @click="handleLogout"
              >
                Sign out
              </button>
            </div>
          </div>
        </div>
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
import { provide, ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

// Add global CSS for transitions
const style = document.createElement('style')
style.textContent = `
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
`
document.head.appendChild(style)

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

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  const dropdown = document.querySelector('.relative.inline-block.text-left')
  // If clicking outside the dropdown and dropdown is open, close it
  if (dropdown && !dropdown.contains(event.target) && isUserDropdownOpen.value) {
    isUserDropdownOpen.value = false
  }
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
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleEscapeKey)
  document.removeEventListener('click', handleClickOutside)
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
