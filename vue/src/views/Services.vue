<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import EditServiceModal from '@/components/EditServiceModal.vue'

const services = ref([])
const namespaces = ref([])
const loading = ref(false)
const notification = ref({ message: '', type: '' })
const showEditModal = ref(false)
const currentService = ref(null)

const form = ref({
  namespace: 'default',
  name: '',
  type: 'ClusterIP',
  port: 80,
  targetPort: 80,
  selector: { app: '' }
})

// Fetch initial data
onMounted(async () => {
  await Promise.all([
    fetchServices(),
    fetchNamespaces()
  ])
})

async function fetchServices() {
  loading.value = true
  try {
    const res = await axios.get('/kube/services')
    services.value = res.data
  } catch (e) {
    showNotification('Failed to fetch services', 'error')
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

function editService(service) {
  currentService.value = service
  showEditModal.value = true
}

function onServiceUpdated() {
  showNotification('Service updated successfully')
  fetchServices()
}

function showNotification(msg, type = 'success') {
  notification.value = { message: msg, type }
  setTimeout(() => notification.value.message = '', 3000)
}

async function createService() {
  try {
    await axios.post('/kube/services', form.value)
    showNotification('Service created successfully!')
    await fetchServices()
    resetForm()
  } catch (err) {
    showNotification(err.response?.data?.error || 'Creation failed', 'error')
  }
}

async function deleteService(namespace, name) {
  if (!confirm(`Delete service ${name} in ${namespace}?`)) return
  
  try {
    await axios.delete(`/kube/services/${namespace}/${name}`)
    showNotification('Service deleted')
    await fetchServices()
  } catch (err) {
    showNotification('Deletion failed', 'error')
  }
}

function resetForm() {
  form.value = {
    namespace: 'default',
    name: '',
    type: 'ClusterIP',
    port: 80,
    targetPort: 80,
    selector: { app: '' }
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-8 max-w-6xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold">Services</h1>

    <!-- Notification -->
    <div v-if="notification.message" 
         :class="`p-4 rounded mb-4 ${notification.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`">
      {{ notification.message }}
    </div>

    <!-- Create Service Form -->
    <div class="bg-white p-6 rounded shadow border border-gray-200">
      <h2 class="text-lg font-medium mb-4">Create New Service</h2>
      <form @submit.prevent="createService" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <!-- Namespace Select -->
        <div>
          <label class="block text-sm font-medium mb-1">Namespace</label>
          <select v-model="form.namespace" class="w-full border rounded p-2">
            <option v-for="ns in namespaces" :value="ns.name">{{ ns.name }}</option>
          </select>
        </div>

        <!-- Service Name -->
        <div>
          <label class="block text-sm font-medium mb-1">Service Name</label>
          <input v-model="form.name" type="text" required 
                 class="w-full border rounded p-2" placeholder="my-service">
        </div>

        <!-- Service Type -->
        <div>
          <label class="block text-sm font-medium mb-1">Type</label>
          <select v-model="form.type" class="w-full border rounded p-2">
            <option value="ClusterIP">ClusterIP</option>
            <option value="NodePort">NodePort</option>
            <option value="LoadBalancer">LoadBalancer</option>
          </select>
        </div>

        <!-- Selector -->
        <div>
          <label class="block text-sm font-medium mb-1">Selector (app name)</label>
          <input v-model="form.selector.app" type="text" required 
                 class="w-full border rounded p-2" placeholder="my-app">
        </div>

        <!-- Ports -->
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-sm font-medium mb-1">Port</label>
            <input v-model.number="form.port" type="number" min="1" max="65535" 
                   class="w-full border rounded p-2">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Target Port</label>
            <input v-model.number="form.targetPort" type="number" min="1" max="65535" 
                   class="w-full border rounded p-2">
          </div>
        </div>

        <div class="md:col-span-2">
          <button type="submit" 
                  class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Create Service
          </button>
        </div>
      </form>
    </div>

    <!-- Services Table -->
    <div class="bg-white rounded shadow border border-gray-200 overflow-auto relative">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Namespace</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cluster IP</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ports</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="svc in services" :key="`${svc.namespace}-${svc.name}`">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ svc.name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ svc.namespace }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ svc.type }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ svc.clusterIP }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ svc.ports }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
              <button @click="editService(svc)"
                      class="text-blue-600 hover:text-blue-900">
                Edit
              </button>
              <button @click="deleteService(svc.namespace, svc.name)"
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

  <EditServiceModal 
    v-if="currentService"
    :service="currentService"
    :show="showEditModal"
    @close="showEditModal = false"
    @updated="onServiceUpdated"
  />
</template>