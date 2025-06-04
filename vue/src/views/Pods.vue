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
  <div class="min-h-screen bg-gray-50 p-8 max-w-6xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold">Pods</h1>

    <!-- Notification -->
    <div v-if="notification.message" 
         :class="`p-4 rounded mb-4 ${notification.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`">
      {{ notification.message }}
    </div>

    <!-- Create Pod Form -->
    <div class="bg-white p-6 rounded shadow border border-gray-200">
      <h2 class="text-lg font-medium mb-4">Create New Pod</h2>
      <form @submit.prevent="createPod" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <!-- Namespace Select -->
        <div>
          <label class="block text-sm font-medium mb-1">Namespace</label>
          <select v-model="form.namespace" class="w-full border rounded p-2">
            <option v-for="ns in namespaces" :value="ns.name">{{ ns.name }}</option>
          </select>
        </div>

        <!-- Pod Name -->
        <div>
          <label class="block text-sm font-medium mb-1">Pod Name</label>
          <input v-model="form.name" type="text" required 
                 class="w-full border rounded p-2" placeholder="my-pod">
        </div>

        <!-- Container Name -->
        <div>
          <label class="block text-sm font-medium mb-1">Container Name</label>
          <input v-model="form.containerName" type="text" required 
                 class="w-full border rounded p-2" placeholder="main-container">
        </div>

        <!-- Image -->
        <div>
          <label class="block text-sm font-medium mb-1">Image</label>
          <input v-model="form.image" type="text" required 
                 class="w-full border rounded p-2" placeholder="nginx:latest">
        </div>

        <!-- Port -->
        <div>
          <label class="block text-sm font-medium mb-1">Port (optional)</label>
          <input v-model.number="form.port" type="number" min="1" max="65535"
                 class="w-full border rounded p-2">
        </div>

        <div class="md:col-span-2">
          <button type="submit" 
                  class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Create Pod
          </button>
        </div>
      </form>
    </div>

    <!-- Pods Table -->
    <div class="bg-white rounded shadow border border-gray-200 overflow-auto relative">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Namespace</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Node</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Containers</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="pod in pods" :key="`${pod.namespace}-${pod.name}`">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ pod.name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ pod.namespace }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <span :class="{
                'text-green-600': pod.status === 'Running',
                'text-yellow-600': pod.status === 'Pending',
                'text-red-600': pod.status === 'Failed'
              }">
                {{ pod.status }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ pod.node }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ pod.ip }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ pod.containers?.join(', ') || 'N/A' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
              <button @click="editPod(pod)"
                      class="text-blue-600 hover:text-blue-900">
                Edit
              </button>
              <button @click="deletePod(pod.namespace, pod.name)"
                      class="text-red-600 hover:text-red-900">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <div v-if="loading" class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center">
        <span class="loader"></span>
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