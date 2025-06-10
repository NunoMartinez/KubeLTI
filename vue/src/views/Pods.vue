<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import EditPodModal from '@/components/EditPodModal.vue'

const pods = ref([])
const namespaces = ref([])
const loading = ref(false)
const notification = ref({ message: '', type: '' })
const showEditModal = ref(false)
const currentPod = ref(null)

const form = ref({
  namespace: 'default',
  name: '',
  containerName: '',
  image: '',
  port: ''
})

// Fetch initial data
onMounted(async () => {
  await Promise.all([
    fetchPods(),
    fetchNamespaces()
  ])
})

async function fetchPods() {
  loading.value = true
  try {
    const res = await axios.get('/kube/pods')
    pods.value = res.data
  } catch (e) {
    showNotification('Failed to fetch pods', 'error')
  } finally {
    loading.value = false
  }
}

async function fetchNamespaces() {
  try {
    const res = await axios.get('/kube/namespaces')
    namespaces.value = res.data
  } catch (e) {
    showNotification('Failed to fetch namespaces', 'error')
  }
}

function editPod(pod) {
  currentPod.value = pod
  showEditModal.value = true
}

function onPodUpdated() {
  showNotification('Pod updated successfully')
  fetchPods()
}

function showNotification(msg, type = 'success') {
  notification.value = { message: msg, type }
  setTimeout(() => notification.value.message = '', 3000)
}

async function createPod() {
  try {
    await axios.post('/kube/pods', form.value)
    showNotification('Pod created successfully!')
    await fetchPods()
    resetForm()
  } catch (err) {
    showNotification(err.response?.data?.error || 'Creation failed', 'error')
  }
}

async function deletePod(namespace, name) {
  if (!confirm(`Delete pod ${name} in ${namespace}?`)) return
  
  try {
    await axios.delete(`/kube/pods/${namespace}/${name}`)
    showNotification('Pod deleted')
    await fetchPods()
  } catch (err) {
    showNotification('Deletion failed', 'error')
  }
}

function resetForm() {
  form.value = {
    namespace: 'default',
    name: '',
    containerName: '',
    image: '',
    port: ''
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
      <div class="flex items-center mb-4 md:mb-0">
        <div class="bg-green-600 p-2 rounded-lg mr-3">
          <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Kubernetes Pods</h1>
      </div>
      
      <div class="flex items-center">
        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
          {{ pods.length }} Total Pods
        </span>
      </div>
    </div>

    <!-- Notification -->
    <div v-if="notification.message" 
         :class="`p-4 rounded-lg mb-6 shadow-sm ${notification.type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'}`">
      <div class="flex items-center">
        <svg v-if="notification.type === 'success'" class="h-5 w-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <svg v-else class="h-5 w-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        {{ notification.message }}
      </div>
    </div>

    <!-- Create Pod Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
      <div class="flex items-center mb-4">
        <svg class="h-5 w-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <h2 class="text-lg font-semibold text-gray-800">Create New Pod</h2>
      </div>
      
      <form @submit.prevent="createPod" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Namespace Select -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Namespace</label>
          <div class="relative">
            <select 
              v-model="form.namespace" 
              class="w-full border border-gray-300 rounded-lg p-2 pr-8 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all appearance-none"
            >
              <option v-for="ns in namespaces" :value="ns.name">{{ ns.name }}</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
              <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Pod Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pod Name</label>
          <input 
            v-model="form.name" 
            type="text" 
            required 
            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" 
            placeholder="my-pod"
          >
        </div>

        <!-- Container Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Container Name</label>
          <input 
            v-model="form.containerName" 
            type="text" 
            required 
            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" 
            placeholder="main-container"
          >
        </div>

        <!-- Image -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
          <input 
            v-model="form.image" 
            type="text" 
            required 
            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" 
            placeholder="nginx:latest"
          >
        </div>

        <!-- Port -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Port (optional)</label>
          <input 
            v-model.number="form.port" 
            type="number" 
            min="1" 
            max="65535"
            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all"
            placeholder="80"
          >
        </div>

        <div class="md:col-span-2">
          <button 
            type="submit" 
            class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors shadow-sm flex items-center"
          >
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Create Pod
          </button>
        </div>
      </form>
    </div>

    <!-- Pods Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center">
          <svg class="h-5 w-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
          <h2 class="text-xl font-semibold text-gray-800">Pod List</h2>
        </div>
      </div>
      
      <!-- Loading state -->
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-pulse flex flex-col items-center">
          <div class="w-full h-8 bg-gray-200 rounded mb-4"></div>
          <div class="w-full h-8 bg-gray-200 rounded mb-4"></div>
          <div class="w-full h-8 bg-gray-200 rounded"></div>
        </div>
      </div>
      
      <!-- Empty state -->
      <div v-else-if="pods.length === 0" class="p-8 text-center">
        <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
          <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">No Pods Found</h3>
        <p class="text-gray-500">Create your first pod to get started.</p>
      </div>
      
      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Namespace</th>
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Node</th>
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Containers</th>
              <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="pod in pods" :key="`${pod.namespace}-${pod.name}`" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-7 w-7 rounded-md bg-green-100 flex items-center justify-center mr-2">
                    <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                  </div>
                  <div class="text-sm font-medium text-gray-900">{{ pod.name }}</div>
                </div>
              </td>
              <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                {{ pod.namespace }}
              </td>
              <td class="px-3 py-3 whitespace-nowrap">
                <span 
                  class="px-2 py-0.5 text-xs font-medium rounded-full"
                  :class="{
                    'bg-green-100 text-green-800': pod.status === 'Running',
                    'bg-yellow-100 text-yellow-800': pod.status === 'Pending',
                    'bg-red-100 text-red-800': pod.status === 'Failed',
                    'bg-gray-100 text-gray-800': !['Running', 'Pending', 'Failed'].includes(pod.status)
                  }"
                >
                  {{ pod.status }}
                </span>
              </td>
              <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                {{ pod.node }}
              </td>
              <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                {{ pod.ip || 'N/A' }}
              </td>
              <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                <div v-if="pod.containers && pod.containers.length" class="flex flex-wrap gap-1">
                  <span 
                    v-for="container in pod.containers" 
                    :key="container"
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
                  >
                    {{ container }}
                  </span>
                </div>
                <span v-else>N/A</span>
              </td>
              <td class="px-3 py-3 whitespace-nowrap text-center text-sm font-medium">
                <div class="flex justify-center space-x-1">
                  <button
                    @click="editPod(pod)"
                    class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded-md transition-colors"
                    title="Edit Pod"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </button>
                  <button
                    @click="deletePod(pod.namespace, pod.name)"
                    class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-2 py-1 rounded-md transition-colors"
                    title="Delete Pod"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <EditPodModal 
    v-if="currentPod"
    :pod="currentPod"
    :show="showEditModal"
    @close="showEditModal = false"
    @updated="onPodUpdated"
  />
</template>