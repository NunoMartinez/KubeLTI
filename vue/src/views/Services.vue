<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import EditServiceModal from '@/components/EditServiceModal.vue'
import RefreshControl from '@/components/RefreshControl.vue'

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
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
      <div class="flex items-center mb-4 md:mb-0">
        <div class="bg-indigo-600 p-2 rounded-lg mr-3">
          <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Kubernetes Services</h1>
      </div>
      
      <div class="flex items-center space-x-4">
        <RefreshControl @refresh="fetchServices" :interval="30" />
        
        <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
          {{ services.length }} Total Services
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

    <!-- Create Service Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
      <div class="flex items-center mb-4">
        <svg class="h-5 w-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <h2 class="text-lg font-semibold text-gray-800">Create New Service</h2>
      </div>
      
      <form @submit.prevent="createService" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Namespace Select -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Namespace</label>
          <div class="relative">
            <select 
              v-model="form.namespace" 
              class="w-full border border-gray-300 rounded-lg p-2 pr-8 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all appearance-none"
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

        <!-- Service Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
          <input 
            v-model="form.name" 
            type="text" 
            required 
            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
            placeholder="my-service"
          >
        </div>

        <!-- Service Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
          <div class="relative">
            <select 
              v-model="form.type" 
              class="w-full border border-gray-300 rounded-lg p-2 pr-8 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all appearance-none"
            >
              <option value="ClusterIP">ClusterIP</option>
              <option value="NodePort">NodePort</option>
              <option value="LoadBalancer">LoadBalancer</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
              <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Selector -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Selector (app name)</label>
          <input 
            v-model="form.selector.app" 
            type="text" 
            required 
            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
            placeholder="my-app"
          >
        </div>

        <!-- Ports -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
            <input 
              v-model.number="form.port" 
              type="number" 
              min="1" 
              max="65535" 
              required
              class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
              placeholder="80"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Target Port</label>
            <input 
              v-model.number="form.targetPort" 
              type="number" 
              min="1" 
              max="65535" 
              required
              class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
              placeholder="8080"
            >
          </div>
        </div>

        <div class="md:col-span-2">
          <button 
            type="submit" 
            class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center"
          >
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Create Service
          </button>
        </div>
      </form>
    </div>

    <!-- Services Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center">
          <svg class="h-5 w-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
          </svg>
          <h2 class="text-xl font-semibold text-gray-800">Service List</h2>
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
      <div v-else-if="services.length === 0" class="p-8 text-center">
        <div class="mx-auto w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
          <svg class="h-8 w-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">No Services Found</h3>
        <p class="text-gray-500">Create your first service to get started.</p>
      </div>
      
      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Namespace</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cluster IP</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ports</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="svc in services" :key="`${svc.namespace}-${svc.name}`" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8 rounded-md bg-indigo-100 flex items-center justify-center mr-3">
                    <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ svc.name }}</div>
                    <div class="text-xs text-gray-500">{{ svc.clusterIP || 'No IP assigned' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ svc.namespace }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  class="px-2.5 py-1 text-xs font-medium rounded-full"
                  :class="{
                    'bg-blue-100 text-blue-800 border border-blue-200': svc.type === 'ClusterIP',
                    'bg-purple-100 text-purple-800 border border-purple-200': svc.type === 'NodePort',
                    'bg-indigo-100 text-indigo-800 border border-indigo-200': svc.type === 'LoadBalancer',
                    'bg-gray-100 text-gray-800 border border-gray-200': !['ClusterIP', 'NodePort', 'LoadBalancer'].includes(svc.type)
                  }"
                >
                  {{ svc.type }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ svc.clusterIP || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  {{ svc.ports }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                <button
                  @click="editService(svc)"
                  class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-md transition-colors mr-2"
                >
                  <span class="flex items-center">
                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                  </span>
                </button>
                <button
                  @click="deleteService(svc.namespace, svc.name)"
                  class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-md transition-colors"
                >
                  <span class="flex items-center">
                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete
                  </span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
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