<template>
  <aside 
    ref="sidebarRef"
    :class="[
      'fixed top-0 left-0 h-screen bg-white border-r border-gray-200 shadow-lg z-40 transition-transform duration-300 ease-in-out flex flex-col',
      isOpen ? 'translate-x-0' : '-translate-x-full',
      mobileView ? 'w-80' : 'w-64'
    ]"
    role="navigation"
    aria-label="Main Navigation"
    tabindex="0"
    @keydown.esc="closeSidebar"
  >
    <!-- Sidebar Header - Fixed at top -->
    <div class="p-4 flex items-center justify-between border-b border-gray-200 bg-white sticky top-0 z-10">
      <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7" />
        </svg>
        <span>Resources</span>
      </h1>
      
      <!-- Close button -->
      <button 
        @click="closeSidebar" 
        class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded p-1"
        aria-label="Close sidebar"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Navigation Links - Scrollable area -->
    <nav class="p-4 space-y-1 overflow-y-auto flex-grow">
      <RouterLink 
        v-for="link in links" :key="link.to" :to="link.to"
        class="flex items-center gap-3 p-3 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
        :class="{ 'bg-blue-50 text-blue-600': isActivePath(link.to) }"
        @click="closeSidebar"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
        </svg>
        <span class="font-medium">{{ link.text }}</span>
      </RouterLink>
    </nav>

    <!-- Sidebar Footer - Fixed at bottom -->
    <div class="p-4 border-t border-gray-200 bg-white sticky bottom-0 z-10">
      <div class="text-xs text-gray-500 mb-2">Cluster API Version</div>
      <div class="bg-gray-100 px-3 py-1 rounded-full text-sm font-medium text-gray-700">
        v{{ clusterVersion }}
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue'
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

// Refs
const sidebarRef = ref(null)
const mobileView = ref(window.innerWidth < 768)

// Inject shared state
const clusterInfo = inject('clusterInfo')
const route = useRoute()

// Computed values
const clusterVersion = computed(() => clusterInfo?.value?.versions?.[0]?.split('v')[1] || '1.0')

// Close sidebar and emit close event
const closeSidebar = () => {
  emit('close')
}

// Check if the current route path matches the link path
const isActivePath = (path) => {
  return route.path === path || 
         (path !== '/dashboard' && route.path.startsWith(path))
}

// Handle window resize
const handleResize = () => {
  mobileView.value = window.innerWidth < 768
  
  // Auto-close sidebar on small screens when resizing
  if (window.innerWidth < 768 && props.isOpen) {
    closeSidebar()
  }
}

// Handle click outside
const handleClickOutside = (event) => {
  if (sidebarRef.value && 
      !sidebarRef.value.contains(event.target) && 
      props.isOpen && 
      !event.target.closest('button[aria-label="Toggle sidebar"]')) {
    closeSidebar()
  }
}

// Toggle body scroll lock
const toggleBodyScrollLock = (lock) => {
  if (lock) {
    // Prevent scrolling on the body when sidebar is open
    document.body.style.overflow = 'hidden'
    document.body.style.position = 'fixed'
    document.body.style.width = '100%'
    // Store current scroll position
    document.body.dataset.scrollY = window.scrollY.toString()
  } else {
    // Restore scrolling
    document.body.style.overflow = ''
    document.body.style.position = ''
    document.body.style.width = ''
    // Restore scroll position
    if (document.body.dataset.scrollY) {
      window.scrollTo(0, parseInt(document.body.dataset.scrollY || '0'))
    }
  }
}

// Set up event listeners
onMounted(() => {
  window.addEventListener('resize', handleResize)
  document.addEventListener('click', handleClickOutside)
  
  // Initial check
  handleResize()
  
  // Handle sidebar open/close effects
  watch(() => props.isOpen, (isOpen) => {
    // Toggle body scroll lock on mobile only
    if (window.innerWidth < 768) {
      toggleBodyScrollLock(isOpen)
    }
    
    // Auto-focus sidebar when opened for accessibility
    if (isOpen && sidebarRef.value) {
      setTimeout(() => {
        sidebarRef.value.focus()
      }, 300) // After transition completes
    }
  })
})

// Clean up event listeners
onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
  document.removeEventListener('click', handleClickOutside)
  
  // Make sure to unlock body scroll when component is destroyed
  toggleBodyScrollLock(false)
})

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
