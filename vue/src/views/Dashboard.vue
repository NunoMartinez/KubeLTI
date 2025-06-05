<template>
  <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-6 h-full w-full">
    <!-- Dashboard Header with Status -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
      <div class="flex flex-col md:flex-row md:items-center mb-4 md:mb-0">
        <div class="flex items-center mb-2 md:mb-0">
          <div class="bg-blue-600 p-2 rounded-lg mr-3">
            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-gray-800 mr-4">Cluster Dashboard</h1>
        </div>
        
        <div class="flex items-center">
          <div v-if="loading" class="flex items-center px-3 py-1 bg-gray-100 rounded-full text-gray-600">
            <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium">Syncing...</span>
          </div>
          <div v-else-if="error" class="flex items-center px-3 py-1 bg-red-100 rounded-full text-red-700">
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span class="text-sm font-medium truncate max-w-xs">{{ error }}</span>
          </div>
          <div v-else class="flex items-center px-3 py-1 bg-green-100 rounded-full text-green-700">
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="text-sm font-medium">Updated {{ lastUpdatedText }}</span>
          </div>
        </div>
      </div>
      
      <div class="flex items-center space-x-3">
        <div class="flex items-center bg-white rounded-lg shadow-sm p-1">
          <span class="text-sm text-gray-500 px-2">Refresh</span>
          <div class="flex border-l border-gray-200">
            <button 
              @click="setRefreshInterval(0)"
              :class="[
                'px-3 py-1 text-sm font-medium transition-colors',
                autoRefreshInterval === 0 ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'
              ]"
            >
              Off
            </button>
            <button 
              @click="setRefreshInterval(30000)"
              :class="[
                'px-3 py-1 text-sm font-medium transition-colors',
                autoRefreshInterval === 30000 ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'
              ]"
            >
              30s
            </button>
            <button 
              @click="setRefreshInterval(60000)"
              :class="[
                'px-3 py-1 text-sm font-medium transition-colors',
                autoRefreshInterval === 60000 ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'
              ]"
            >
              1m
            </button>
          </div>
        </div>
        
        <button 
          @click="refreshData"
          class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm transition-colors"
          :disabled="loading"
          :class="{ 'opacity-70 cursor-not-allowed': loading }"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          Refresh Now
        </button>
      </div>
    </div>

    <!-- Cluster Health Summary -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Cluster Health</h2>
        <div class="flex items-center">
          <div class="w-3 h-3 rounded-full mr-2" :class="clusterHealthStatus.color"></div>
          <span class="text-sm font-medium" :class="clusterHealthStatus.textColor">{{ clusterHealthStatus.label }}</span>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <MetricCard 
          title="CPU Usage" 
          :value="metrics.cpu.used" 
          :max="metrics.cpu.total"
          unit="cores"
          icon="cpu"
        />
        <MetricCard 
          title="Memory Usage" 
          :value="metrics.memory.used" 
          :max="metrics.memory.total"
          unit="GB"
          icon="memory"
        />
        <MetricCard 
          title="Running Pods" 
          :value="metrics.pods.running" 
          :max="metrics.pods.total"
          icon="pods"
        />
      </div>
    </div>
    
    <!-- Nodes Status Panel -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
      <div class="p-6 border-b border-gray-200 flex justify-between items-center">
        <div class="flex items-center">
          <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"></path>
          </svg>
          <h2 class="text-xl font-semibold text-gray-800">Nodes Status</h2>
        </div>
        <div class="flex items-center space-x-3">
          <div class="flex items-center">
            <div class="w-3 h-3 rounded-full bg-green-500 mr-1"></div>
            <span class="text-xs text-gray-600">{{ onlineNodesCount }} Online</span>
          </div>
          <div class="flex items-center">
            <div class="w-3 h-3 rounded-full bg-red-500 mr-1"></div>
            <span class="text-xs text-gray-600">{{ offlineNodesCount }} Offline</span>
          </div>
          <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
            {{ nodes.length }} Total
          </span>
        </div>
      </div>
      
      <div v-if="nodes.length === 0 && !loading" class="p-8 text-center">
        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
          <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <p class="text-gray-500">No nodes available in this cluster</p>
        <button 
          @click="refreshData" 
          class="mt-4 text-blue-600 hover:text-blue-800 text-sm font-medium"
        >
          Refresh Data
        </button>
      </div>
      
      <NodesStatus v-else-if="!loading" :nodes="nodes" />
      
      <div v-else class="p-8 text-center">
        <div class="animate-pulse flex flex-col items-center">
          <div class="w-full h-8 bg-gray-200 rounded mb-4"></div>
          <div class="w-full h-8 bg-gray-200 rounded mb-4"></div>
          <div class="w-full h-8 bg-gray-200 rounded"></div>
        </div>
      </div>
    </div>

    <!-- Resource Utilization Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Pods Distribution Panel -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center">
            <svg class="h-5 w-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            <h2 class="text-xl font-semibold text-gray-800">Pods per Node</h2>
          </div>
          <span class="text-sm text-gray-500">Total: {{ metrics.pods.running }} pods</span>
        </div>
        
        <div v-if="nodes.length === 0 && !loading" class="py-12 text-center">
          <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <p class="text-gray-500">No data available</p>
        </div>
        
        <div v-else-if="loading" class="py-12 animate-pulse">
          <div class="h-40 bg-gray-200 rounded"></div>
        </div>
        
        <div v-else class="bg-gray-50 p-4 rounded-lg">
          <BarChart :data="podDistributionData" :options="chartOptions" />
        </div>
      </div>
      
      <!-- Pod Capacity Usage -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center">
            <svg class="h-5 w-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <h2 class="text-xl font-semibold text-gray-800">Pod Capacity Usage</h2>
          </div>
          <div class="flex items-center space-x-4">
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-blue-500 mr-1"></div>
              <span class="text-xs text-gray-600">Normal</span>
            </div>
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-yellow-500 mr-1"></div>
              <span class="text-xs text-gray-600">Warning</span>
            </div>
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-full bg-red-500 mr-1"></div>
              <span class="text-xs text-gray-600">Critical</span>
            </div>
          </div>
        </div>
        
        <div v-if="nodes.length === 0 && !loading" class="py-12 text-center">
          <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <p class="text-gray-500">No data available</p>
        </div>
        
        <div v-else-if="loading" class="py-6 animate-pulse space-y-4">
          <div class="h-6 bg-gray-200 rounded w-1/3"></div>
          <div class="h-4 bg-gray-200 rounded w-full"></div>
          <div class="h-4 bg-gray-200 rounded w-full"></div>
          <div class="h-4 bg-gray-200 rounded w-full"></div>
        </div>
        
        <div v-else class="space-y-5">
          <div
            v-for="node in nodes"
            :key="node.name"
            class="flex items-center"
          >
            <div class="w-1/4 flex items-center">
              <div class="w-2 h-2 rounded-full mr-2" :class="node.status === 'Online' ? 'bg-green-500' : 'bg-red-500'"></div>
              <span class="text-sm font-medium text-gray-700 truncate pr-2">
                {{ node.name }}
              </span>
            </div>
            <div class="w-3/4 flex items-center">
              <div class="w-full mr-3">
                <div class="h-4 bg-gray-200 rounded-full overflow-hidden">
                  <div
                    class="h-full transition-all duration-500 ease-in-out"
                    :class="getCapacityColorClass(podPercentage(node))"
                    :style="{ width: podPercentage(node) + '%' }"
                  ></div>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-xs font-medium text-gray-700 whitespace-nowrap">
                  {{ node.podCount }}/{{ node.podCapacity }}
                </span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full" :class="getUsageTextClass(podPercentage(node))">
                  {{ podPercentage(node) }}%
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- System Info Panel -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <div class="flex items-center mb-6">
        <svg class="h-5 w-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h2 class="text-xl font-semibold text-gray-800">System Information</h2>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 flex flex-col">
          <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Kubernetes Version</h3>
          <div class="flex items-center mt-auto">
            <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
            <p class="text-lg font-semibold text-gray-800">{{ clusterVersion || 'Unknown' }}</p>
          </div>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 flex flex-col">
          <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Cluster Uptime</h3>
          <div class="flex items-center mt-auto">
            <svg class="h-5 w-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-lg font-semibold text-gray-800">{{ clusterUptime || 'Unknown' }}</p>
          </div>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 flex flex-col">
          <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Total Resources</h3>
          <div class="flex items-center mt-auto">
            <svg class="h-5 w-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
            </svg>
            <p class="text-lg font-semibold text-gray-800">
              {{ metrics.cpu.total }} cores, {{ metrics.memory.total }} GB
            </p>
          </div>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 flex flex-col">
          <h3 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Last Refreshed</h3>
          <div class="flex items-center mt-auto">
            <svg class="h-5 w-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-lg font-semibold text-gray-800">{{ lastUpdatedFull || 'Never' }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted, watch } from 'vue'
import axios from 'axios'
import MetricCard from '@/components/MetricCard.vue'
import NodesStatus from '@/components/NodesStatus.vue'
import BarChart from '@/components/BarChart.vue'

// Data state
const metrics = ref({
  cpu: { used: 0, total: 0 },
  memory: { used: 0, total: 0 },
  pods: { running: 0, total: 0 }
})
const nodes = ref([])
const loading = ref(true)
const error = ref(null)
const lastUpdated = ref(null)
const clusterVersion = ref(null)
const clusterUptime = ref(null)

// Auto-refresh state
const autoRefreshInterval = ref(0) // 0 means disabled
const refreshTimer = ref(null)

// Chart options
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      backgroundColor: 'rgba(255, 255, 255, 0.9)',
      titleColor: '#1F2937',
      bodyColor: '#4B5563',
      borderColor: '#E5E7EB',
      borderWidth: 1,
      padding: 10,
      cornerRadius: 6,
      displayColors: false,
      callbacks: {
        title: (tooltipItems) => {
          return tooltipItems[0].label;
        },
        label: (context) => {
          return `Pods: ${context.raw}`;
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(156, 163, 175, 0.1)'
      },
      ticks: {
        precision: 0
      }
    },
    x: {
      grid: {
        display: false
      }
    }
  }
}

// Computed properties
const lastUpdatedText = computed(() => {
  if (!lastUpdated.value) return ''
  
  const now = new Date()
  const diff = now - lastUpdated.value
  
  if (diff < 60000) {
    return 'just now'
  } else if (diff < 3600000) {
    return `${Math.floor(diff / 60000)} minute${Math.floor(diff / 60000) !== 1 ? 's' : ''} ago`
  } else {
    return `${Math.floor(diff / 3600000)} hour${Math.floor(diff / 3600000) !== 1 ? 's' : ''} ago`
  }
})

const lastUpdatedFull = computed(() => {
  if (!lastUpdated.value) return ''
  return lastUpdated.value.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
})

const podDistributionData = computed(() => ({
  labels: nodes.value.map(n => n.name),
  datasets: [{
    label: 'Pods',
    data: nodes.value.map(n => n.podCount),
    backgroundColor: nodes.value.map(n => n.status === 'Online' ? '#3B82F6' : '#9CA3AF'),
    borderRadius: 6,
    maxBarThickness: 40
  }]
}))

const onlineNodesCount = computed(() => {
  return nodes.value.filter(node => node.status === 'Online').length
})

const offlineNodesCount = computed(() => {
  return nodes.value.filter(node => node.status !== 'Online').length
})

const clusterHealthStatus = computed(() => {
  // Calculate overall health based on metrics and node status
  if (error.value) {
    return {
      label: 'Error',
      color: 'bg-red-500',
      textColor: 'text-red-700'
    }
  }
  
  if (loading.value) {
    return {
      label: 'Checking...',
      color: 'bg-gray-400',
      textColor: 'text-gray-700'
    }
  }
  
  // Check if any nodes are offline
  if (offlineNodesCount.value > 0) {
    return {
      label: 'Warning',
      color: 'bg-yellow-500',
      textColor: 'text-yellow-700'
    }
  }
  
  // Check resource usage
  const cpuPercentage = metrics.value.cpu.total > 0 
    ? (metrics.value.cpu.used / metrics.value.cpu.total) * 100 
    : 0
  
  const memoryPercentage = metrics.value.memory.total > 0 
    ? (metrics.value.memory.used / metrics.value.memory.total) * 100 
    : 0
  
  if (cpuPercentage > 90 || memoryPercentage > 90) {
    return {
      label: 'Critical',
      color: 'bg-red-500',
      textColor: 'text-red-700'
    }
  }
  
  if (cpuPercentage > 75 || memoryPercentage > 75) {
    return {
      label: 'Warning',
      color: 'bg-yellow-500',
      textColor: 'text-yellow-700'
    }
  }
  
  return {
    label: 'Healthy',
    color: 'bg-green-500',
    textColor: 'text-green-700'
  }
})

// Methods
const podPercentage = (node) => {
  if (!node.podCapacity || node.podCapacity === 0) return 0
  return Math.round((node.podCount / node.podCapacity) * 100)
}

const getCapacityColorClass = (percentage) => {
  if (percentage >= 90) return 'bg-red-500'
  if (percentage >= 75) return 'bg-yellow-500'
  return 'bg-blue-500'
}

const getUsageTextClass = (percentage) => {
  if (percentage >= 90) return 'bg-red-100 text-red-800'
  if (percentage >= 75) return 'bg-yellow-100 text-yellow-800'
  return 'bg-blue-100 text-blue-800'
}

const setRefreshInterval = (interval) => {
  // Clear existing timer if any
  if (refreshTimer.value) {
    clearInterval(refreshTimer.value)
    refreshTimer.value = null
  }
  
  // Set new interval
  autoRefreshInterval.value = interval
  
  // Start new timer if interval > 0
  if (interval > 0) {
    refreshTimer.value = setInterval(() => {
      refreshData()
    }, interval)
  }
}

async function fetchData() {
  try {
    loading.value = true
    error.value = null
    
    const [metricsRes, nodesRes, clusterRes] = await Promise.all([
      axios.get('/kube/metrics'),
      axios.get('/kube/nodes'),
      axios.get('/kube/api')
    ])
    
    metrics.value = metricsRes.data
    nodes.value = nodesRes.data
    
    // Extract cluster info
    if (clusterRes.data) {
      clusterVersion.value = clusterRes.data.versions?.[0] || 'Unknown'
      clusterUptime.value = formatUptime(clusterRes.data.uptime)
    }
    
    lastUpdated.value = new Date()
  } catch (err) {
    error.value = err.response?.data?.error || err.message
    console.error('Error fetching data:', err)
  } finally {
    loading.value = false
  }
}

function formatUptime(seconds) {
  if (!seconds) return 'Unknown'
  
  const days = Math.floor(seconds / 86400)
  const hours = Math.floor((seconds % 86400) / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  
  if (days > 0) {
    return `${days}d ${hours}h ${minutes}m`
  } else if (hours > 0) {
    return `${hours}h ${minutes}m`
  } else {
    return `${minutes}m`
  }
}

function refreshData() {
  fetchData()
}

// Lifecycle hooks
onMounted(() => {
  fetchData()
})

onUnmounted(() => {
  // Clean up auto-refresh interval
  if (refreshTimer.value) {
    clearInterval(refreshTimer.value)
  }
})
</script>