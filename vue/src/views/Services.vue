<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const services = ref([])

onMounted(async () => {
  try {
    const res = await axios.get('/kube/services')
    services.value = res.data
  } catch (err) {
    console.error('Failed to load services:', err)
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-8">
    <h1 class="text-2xl font-bold mb-4">Services</h1>
    <div class="overflow-auto bg-white rounded shadow border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Namespace</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cluster IP</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ports</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="service in services" :key="service.name">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ service.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ service.namespace }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ service.type }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ service.clusterIP }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ service.ports }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
