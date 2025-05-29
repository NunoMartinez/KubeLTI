<template>
  <div class="min-h-screen bg-gray-50 p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Cluster Dashboard</h1>
    <button 
    @click="refreshData"
    class="flex items-center text-blue-600 hover:text-blue-800"
  >
    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
    </svg>
    Refresh
  </button>
    <!-- Metrics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <MetricCard 
        title="CPU Usage" 
        :value="metrics.cpu.used" 
        :max="metrics.cpu.total"
        unit="cores"
      />
      <MetricCard 
        title="Memory Usage" 
        :value="metrics.memory.used" 
        :max="metrics.memory.total"
        unit="GB"
      />
      <MetricCard 
        title="Running Pods" 
        :value="metrics.pods.running" 
        :max="metrics.pods.total"
      />
    </div>
    
    
    <!-- Nodes Status -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
      <h2 class="text-xl font-semibold p-6 border-b">Nodes Status</h2>
      <NodesStatus :nodes="nodes" />
    </div>

     <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h2 class="text-xl font-semibold mb-4">Pods Distribution</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
          <h3 class="text-lg font-medium mb-3">Pods per Node</h3>
          <BarChart :data="podDistributionData" />
        </div>
       <div class="flex flex-col h-full">
  <h3 class="text-lg font-medium mb-3">Pod Capacity Usage</h3>
  <div class="flex-1 flex items-center">
    <div class="space-y-4 w-full">
      <div
        v-for="node in nodes"
        :key="node.name"
        class="flex items-center"
      >
        <span class="w-1/3 text-sm font-medium text-gray-600">
          {{ node.name }}
        </span>
        <div class="w-2/3 flex items-center">
          <div class="w-full mr-2">
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
              <div
                class="h-full bg-blue-500"
                :style="{ width: podPercentage(node) + '%' }"
              ></div>
            </div>
          </div>
          <span class="text-xs text-gray-500 w-12 text-right">
            {{ podPercentage(node) }}%
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import MetricCard from '@/components/MetricCard.vue'
import NodesStatus from '@/components/NodesStatus.vue'
import BarChart from '@/components/BarChart.vue'

const metrics = ref({
  cpu: { used: 0, total: 0 },
  memory: { used: 0, total: 0 },
  pods: { running: 0, total: 0 }
})

const nodes = ref([])
const loading = ref(true)
const error = ref(null)

const podDistributionData = computed(() => ({
  labels: nodes.value.map(n => n.name),
  datasets: [{
    label: 'Pods',
    data: nodes.value.map(n => n.podCount),
    backgroundColor: '#3B82F6',
  }]
}))

const podPercentage = (node) => {
  if (!node.podCapacity || node.podCapacity === 0) return 0
  return Math.round((node.podCount / node.podCapacity) * 100)
}

async function fetchData() {
  try {
    loading.value = true
    error.value = null
    
    const [metricsRes, nodesRes] = await Promise.all([
      axios.get('/kube/metrics'),
      axios.get('/kube/nodes')
    ])
    
    metrics.value = metricsRes.data
    nodes.value = nodesRes.data
  } catch (err) {
    error.value = err.response?.data?.error || err.message
    console.error('Error fetching data:', err)
  } finally {
    loading.value = false
  }
}

function refreshData() {
  fetchData()
}

onMounted(() => {
  fetchData()
})
</script>