<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const ingresses = ref([])

onMounted(async () => {
  try {
    const res = await axios.get('/kube/ingresses')
    ingresses.value = res.data
  } catch (e) {
    console.error('Failed to fetch ingresses', e)
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-8">
    <h1 class="text-2xl font-bold mb-4">Ingresses</h1>
    <div class="bg-white rounded shadow border overflow-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Namespace</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Class</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Hosts</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="ing in ingresses" :key="ing.name + ing.namespace">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ ing.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ ing.namespace }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ ing.class }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">
              <ul>
                <li v-for="host in ing.hosts" :key="host">{{ host }}</li>
              </ul>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ new Date(ing.created_at).toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
