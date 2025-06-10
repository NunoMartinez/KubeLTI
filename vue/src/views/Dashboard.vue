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
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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
        <MetricCard 
          title="Disk Usage" 
          :value="metrics.disk?.used || 0" 
          :max="metrics.disk?.total || 0"
          unit="GB"
          icon="disk"
        />
      </div>
    </div>
    
    <!-- Resource Distribution Overview -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
          <svg class="h-5 w-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
          </svg>
          <h2 class="text-xl font-semibold text-gray-800">Resources Overview</h2>
        </div>
        <div class="flex items-center">
          <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
            {{ Object.values(resources).reduce((a, b) => a + b, 0) }} Total Resources
          </span>
        </div>
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="flex flex-col">
          <h3 class="text-sm font-medium text-gray-500 mb-3">Resource Distribution</h3>
          <div class="bg-gray-50 p-4 rounded-lg flex-grow flex items-center justify-center">
            <div v-if="loading" class="py-8 animate-pulse">
              <div class="h-40 w-40 bg-gray-200 rounded-full mx-auto"></div>
            </div>
            <div v-else-if="Object.values(resources).every(count => count === 0)" class="py-8 text-center">
              <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <p class="text-gray-500">No resource data available</p>
            </div>
            <div v-else class="h-56 w-full">
              <PieChart :data="resourcesChartData" :options="{
                plugins: {
                  tooltip: {
                    callbacks: {
                      label: (context) => `${context.label}: ${context.raw} (${Math.round(context.raw / Object.values(resources).reduce((a, b) => a + b, 0) * 100)}%)`
                    }
                  }
                }
              }" />
            </div>
          </div>
        </div>
        
        <div class="flex flex-col">
          <h3 class="text-sm font-medium text-gray-500 mb-3">Resource Counts</h3>
          <div class="bg-gray-50 p-4 rounded-lg flex-grow">
            <div v-if="loading" class="animate-pulse space-y-3">
              <div class="h-8 bg-gray-200 rounded"></div>
              <div class="h-8 bg-gray-200 rounded"></div>
              <div class="h-8 bg-gray-200 rounded"></div>
            </div>
            <div v-else class="space-y-3 h-full flex flex-col justify-center">
              <div v-for="(count, key, index) in resources" :key="key" 
                class="flex items-center justify-between p-3 rounded-lg" 
                :class="`bg-opacity-10 border border-opacity-20`"
                :style="{
                  backgroundColor: resourcesChartData.datasets[0].backgroundColor[index] + '15',
                  borderColor: resourcesChartData.datasets[0].backgroundColor[index] + '30'
                }"
              >
                <div class="flex items-center">
                  <div class="w-3 h-3 rounded-full mr-2" :style="{ backgroundColor: resourcesChartData.datasets[0].backgroundColor[index] }"></div>
                  <span class="font-medium">{{ key.charAt(0).toUpperCase() + key.slice(1) }}</span>
                </div>
                <span class="text-lg font-semibold">{{ count }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Nodes Status Panel -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
      <div class="p-6 border-b border-gray-200 flex flex-col md:flex-row md:justify-between md:items-center">
        <div class="flex items-center mb-3 md:mb-0">
          <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"></path>
          </svg>
          <h2 class="text-xl font-semibold text-gray-800">Nodes Status</h2>
        </div>
        <div class="flex flex-wrap gap-3">
          <div class="flex items-center">
            <div class="w-3 h-3 rounded-full bg-green-500 mr-1"></div>
            <span class="text-xs text-gray-600">{{ onlineNodesCount }} Online</span>
          </div>
          <div class="flex items-center">
            <div class="w-3 h-3 rounded-full bg-red-500 mr-1"></div>
            <span class="text-xs text-gray-600">{{ offlineNodesCount }} Offline</span>
          </div>
          <div class="flex items-center">
            <svg class="h-4 w-4 text-blue-600 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            <span class="text-xs text-blue-700">{{ masterNodesCount }} Master</span>
          </div>
          <div class="flex items-center">
            <svg class="h-4 w-4 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
            </svg>
            <span class="text-xs text-gray-600">{{ workerNodesCount }} Worker</span>
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
        <div class="flex flex-col space-y-2 mb-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <svg class="h-5 w-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
              </svg>
              <h2 class="text-xl font-semibold text-gray-800">Pods per Node</h2>
            </div>
            <span class="text-sm text-gray-500">Total: {{ metrics.pods.running }} pods</span>
          </div>
          
          <div class="flex items-center justify-end space-x-4 text-xs">
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-sm bg-blue-500 border-2 border-blue-600 mr-1"></div>
              <span class="text-gray-600">Master Node</span>
            </div>
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-sm bg-green-500 mr-1"></div>
              <span class="text-gray-600">Worker Node</span>
            </div>
            <div class="flex items-center">
              <div class="w-3 h-3 rounded-sm bg-gray-400 mr-1"></div>
              <span class="text-gray-600">Offline Node</span>
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
            class="flex items-center p-2 rounded-lg"
            :class="{'bg-blue-50': node.role === 'Master'}"
          >
            <div class="w-1/4 flex items-center">
              <div class="flex items-center justify-center w-6 h-6 rounded-full mr-2" 
                :class="node.role === 'Master' ? 'bg-blue-100' : 'bg-gray-100'">
                <svg v-if="node.role === 'Master'" class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <svg v-else class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
              </div>
              <div class="flex flex-col">
                <span class="text-sm font-medium truncate pr-2" 
                  :class="node.role === 'Master' ? 'text-blue-700' : 'text-gray-700'">
                  {{ node.name }}
                </span>
                <span class="text-xs text-gray-500">
                  {{ node.role }}
                </span>
              </div>
            </div>
            <div class="w-3/4 flex items-center">
              <div class="w-full mr-3">
                <div class="h-4 bg-gray-200 rounded-full overflow-hidden">
                  <div
                    class="h-full transition-all duration-500 ease-in-out"
                    :class="[
                      getCapacityColorClass(podPercentage(node)),
                      node.role === 'Master' ? 'opacity-90' : 'opacity-100'
                    ]"
                    :style="{ width: podPercentage(node) + '%' }"
                  ></div>
                </div>
                <div class="flex justify-between mt-1">
                  <span class="text-xs text-gray-500">0</span>
                  <span class="text-xs text-gray-500">{{ node.podCapacity }}</span>
                </div>
              </div>
              <div class="flex flex-col items-end">
                <span class="text-xs font-medium whitespace-nowrap" 
                  :class="node.role === 'Master' ? 'text-blue-700' : 'text-gray-700'">
                  {{ node.podCount }}/{{ node.podCapacity }}
                </span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full mt-1" 
                  :class="getUsageTextClass(podPercentage(node))">
                  {{ podPercentage(node) }}%
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Storage Volumes Panel -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
      <div class="p-6 border-b border-gray-200 flex justify-between items-center">
        <div class="flex items-center">
          <svg class="h-5 w-5 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a2 2 0 012-2h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm2 0v14h12V5H6zm3 8h6m-6-4h6"></path>
          </svg>
          <h2 class="text-xl font-semibold text-gray-800">Storage Volumes</h2>
        </div>
        <span class="bg-amber-100 text-amber-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
          {{ metrics.disk?.volumes?.length || 0 }} Volumes
        </span>
      </div>
      
      <div class="p-6">
        <div v-if="loading" class="space-y-4">
          <div class="animate-pulse h-8 bg-gray-200 rounded w-1/4 mb-4"></div>
          <div class="animate-pulse h-4 bg-gray-200 rounded w-full"></div>
          <div class="animate-pulse h-4 bg-gray-200 rounded w-full"></div>
          <div class="animate-pulse h-4 bg-gray-200 rounded w-full"></div>
        </div>
        
        <div v-else-if="!metrics.disk?.volumes?.length" class="py-8 text-center">
          <div class="mx-auto w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mb-4">
            <svg class="h-8 w-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a2 2 0 012-2h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm2 0v14h12V5H6zm3 8h6m-6-4h6"></path>
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 mb-1">No Persistent Volumes Found</h3>
          <p class="text-gray-500">There are no persistent volumes configured in the cluster.</p>
        </div>
        
        <div v-else>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Capacity</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Storage Class</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Claim</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="volume in metrics.disk.volumes" :key="volume.name">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <svg class="h-4 w-4 text-amber-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a2 2 0 012-2h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm2 0v14h12V5H6zm3 8h6m-6-4h6"></path>
                      </svg>
                      <span class="text-sm font-medium text-gray-900">{{ volume.name }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ volume.capacity }} GB
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getVolumeStatusClass(volume.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ volume.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ volume.storageClass }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ volume.claim }}
                  </td>
                </tr>
              </tbody>
            </table>
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
        
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200 flex flex-col">
          <h3 class="text-xs font-medium text-blue-600 uppercase tracking-wider mb-2">Cluster Uptime</h3>
          <div v-if="loading" class="animate-pulse h-6 bg-blue-200 rounded w-3/4 mt-1"></div>
          <div v-else-if="!clusterUptime" class="flex items-center mt-auto">
            <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-lg font-medium text-gray-500 italic">Not available</p>
          </div>
          <div v-else class="flex flex-col mt-auto">
            <div class="flex items-center">
              <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <p class="text-lg font-semibold text-blue-700">{{ clusterUptime }}</p>
            </div>
            <p class="text-xs text-blue-500 mt-1 ml-7">Since master node creation</p>
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
import PieChart from '@/components/PieChart.vue'

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
const resources = ref({
  pods: 0,
  services: 0,
  deployments: 0,
  nodes: 0,
  namespaces: 0,
  ingresses: 0
})

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
    backgroundColor: nodes.value.map(n => {
      if (n.status !== 'Online') return '#9CA3AF'; // Offline nodes are gray
      return n.role === 'Master' ? '#3B82F6' : '#10B981'; // Blue for master, green for worker
    }),
    borderWidth: nodes.value.map(n => n.role === 'Master' ? 2 : 0),
    borderColor: nodes.value.map(n => n.role === 'Master' ? '#2563EB' : 'transparent'),
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

const masterNodesCount = computed(() => {
  return nodes.value.filter(node => node.role === 'Master').length
})

const workerNodesCount = computed(() => {
  return nodes.value.filter(node => node.role === 'Worker').length
})

const resourcesChartData = computed(() => ({
  labels: ['Pods', 'Services', 'Deployments', 'Nodes', 'Namespaces', 'Ingresses'],
  datasets: [{
    label: 'Resource Count',
    data: [
      resources.value.pods,
      resources.value.services,
      resources.value.deployments,
      resources.value.nodes,
      resources.value.namespaces,
      resources.value.ingresses
    ],
    backgroundColor: [
      '#10B981', // Green for Pods
      '#3B82F6', // Blue for Services
      '#6366F1', // Indigo for Deployments
      '#F59E0B', // Amber for Nodes
      '#EC4899', // Pink for Namespaces
      '#8B5CF6'  // Purple for Ingresses
    ],
    borderColor: '#fff',
    borderWidth: 2
  }]
}))

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

const getVolumeStatusClass = (status) => {
  switch (status) {
    case 'Bound':
      return 'bg-green-100 text-green-800'
    case 'Available':
      return 'bg-blue-100 text-blue-800'
    case 'Released':
      return 'bg-yellow-100 text-yellow-800'
    case 'Failed':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
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
    
    const [metricsRes, nodesRes, clusterRes, podsRes, servicesRes, deploymentsRes, namespacesRes, ingressesRes] = await Promise.all([
      axios.get('/kube/metrics'),
      axios.get('/kube/nodes'),
      axios.get('/kube/api'),
      axios.get('/kube/pods'),
      axios.get('/kube/services'),
      axios.get('/kube/deployments'),
      axios.get('/kube/namespaces'),
      axios.get('/kube/ingresses').catch(() => ({ data: [] })) // Handle case if ingresses endpoint is not available
    ])
    
    metrics.value = metricsRes.data
    nodes.value = nodesRes.data
    
    // Update resource counts
    resources.value = {
      pods: podsRes.data.length || 0,
      services: servicesRes.data.length || 0,
      deployments: deploymentsRes.data.length || 0,
      nodes: nodesRes.data.length || 0,
      namespaces: namespacesRes.data.length || 0,
      ingresses: ingressesRes.data?.length || 0
    }
    
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
  
  // Format with leading zeros for better readability
  const formattedHours = hours.toString().padStart(2, '0')
  const formattedMinutes = minutes.toString().padStart(2, '0')
  
  if (days > 0) {
    if (days === 1) {
      return `${days} day ${formattedHours}:${formattedMinutes}`
    }
    return `${days} days ${formattedHours}:${formattedMinutes}`
  } else if (hours > 0) {
    return `${formattedHours}:${formattedMinutes}`
  } else if (minutes > 0) {
    return `${formattedMinutes} minutes`
  } else {
    return `Just started`
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