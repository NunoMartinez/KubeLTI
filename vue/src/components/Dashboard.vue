<script setup>
import { ref, onMounted, inject } from 'vue'
import axios from 'axios'

const clusterInfo = ref({})
const token = localStorage.getItem('token')
const serverBaseUrl = inject('serverBaseUrl')



onMounted(async () => {
  try {
   const response = await axios.get('/kube/api')

    clusterInfo.value = response.data
  } catch (error) {
    console.error('Failed to load cluster info:', error)
  }
})
</script>

<template>
  <div class="space-y-4">
    <h1 class="text-2xl font-bold">Kubernetes Dashboard</h1>
    <div class="p-4 bg-white shadow rounded-md">
      <p><strong>API Versions:</strong> {{ clusterInfo.versions?.join(', ') }}</p>
      <p><strong>Server Address:</strong> {{ clusterInfo.serverAddressByClientCIDRs?.[0]?.serverAddress }}</p>
    </div>
    <div class="grid grid-cols-2 gap-4">
      <RouterLink to="/nodes" class="dashboard-link">Manage Nodes</RouterLink>
      <RouterLink to="/namespaces" class="dashboard-link">Manage Namespaces</RouterLink>
      <RouterLink to="/pods" class="dashboard-link">Manage Pods</RouterLink>
      <RouterLink to="/deployments" class="dashboard-link">Manage Deployments</RouterLink>
      <RouterLink to="/services" class="dashboard-link">Manage Services</RouterLink>
      <RouterLink to="/ingress" class="dashboard-link">Manage Ingress</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.dashboard-link {
  @apply p-4 bg-blue-100 rounded shadow hover:bg-blue-200 transition-all text-center font-medium;
}
</style>
