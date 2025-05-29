<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pods</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CPU</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Memory</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr v-for="node in nodes" :key="node.name">
          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
            {{ node.name }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <span :class="statusClass(node.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
              {{ node.status }}
            </span>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ node.role }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
              <div class="w-full mr-2">
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div 
                    class="h-full bg-blue-500" 
                    :style="{ width: podPercentage(node) + '%' }"
                  ></div>
                </div>
              </div>
              <span class="text-sm text-gray-600">
                {{ node.podCount }}/{{ node.podCapacity }}
              </span>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ node.cpu }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ node.memory }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
const props = defineProps({
  nodes: {
    type: Array,
    required: true
  }
})

const statusClass = (status) => {
  return {
    'Online': 'bg-green-100 text-green-800',
    'Offline': 'bg-red-100 text-red-800'
  }[status] || 'bg-gray-100 text-gray-800'
}

const podPercentage = (node) => {
  if (!node.podCapacity || node.podCapacity === 0) return 0
  return Math.round((node.podCount / node.podCapacity) * 100)
}
</script>