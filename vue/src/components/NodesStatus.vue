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
        <tr 
          v-for="node in nodes" 
          :key="node.name"
          :class="{'bg-blue-50': node.role === 'Master'}"
        >
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
              <div class="mr-2">
                <svg v-if="node.role === 'Master'" class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <svg v-else class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
              </div>
              <span class="text-sm font-medium" :class="node.role === 'Master' ? 'text-blue-900' : 'text-gray-900'">
                {{ node.name }}
              </span>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <span :class="statusClass(node.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
              {{ node.status }}
            </span>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <span 
              class="px-2.5 py-1 text-xs font-medium rounded-md" 
              :class="roleClass(node.role)"
            >
              {{ node.role }}
            </span>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
              <div class="w-full mr-2">
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div 
                    class="h-full" 
                    :class="node.role === 'Master' ? 'bg-blue-600' : 'bg-green-500'" 
                    :style="{ width: podPercentage(node) + '%' }"
                  ></div>
                </div>
              </div>
              <span class="text-sm text-gray-600">
                {{ node.podCount }}/{{ node.podCapacity }}
              </span>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
              <svg class="h-4 w-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
              </svg>
              <span class="text-sm font-medium" :class="node.role === 'Master' ? 'text-blue-700' : 'text-gray-700'">
                {{ node.cpu }}
              </span>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
              <svg class="h-4 w-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
              </svg>
              <span class="text-sm font-medium" :class="node.role === 'Master' ? 'text-blue-700' : 'text-gray-700'">
                {{ node.memory }}
              </span>
            </div>
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

const roleClass = (role) => {
  return {
    'Master': 'bg-blue-100 text-blue-800 border border-blue-200',
    'Worker': 'bg-gray-100 text-gray-800 border border-gray-200'
  }[role] || 'bg-gray-100 text-gray-800 border border-gray-200'
}

const podPercentage = (node) => {
  if (!node.podCapacity || node.podCapacity === 0) return 0
  return Math.round((node.podCount / node.podCapacity) * 100)
}
</script>