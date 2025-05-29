<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const nodes = ref([])

onMounted(async () => {
  try {
    const res = await axios.get('/kube/nodes')
    nodes.value = res.data
  } catch (e) {
    console.error('Failed to fetch nodes', e)
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-8">
    <h1 class="text-2xl font-bold mb-6">Kubernetes Nodes</h1>

    <div class="bg-white rounded-lg shadow overflow-auto border">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Role</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">CPU</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Memory</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kubelet Version</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="node in nodes" :key="node.name">
            <td class="px-6 py-4 text-sm text-gray-900">{{ node.name }}</td>
            <td class="px-6 py-4 text-sm" :class="node.status === 'Online' ? 'text-green-600' : 'text-red-600'">
              {{ node.status }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ node.role }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ node.cpu }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ node.memory }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ node.kubeletVersion }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
