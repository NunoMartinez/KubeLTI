<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import EditIngressModal from '@/components/EditIngressModal.vue'

const ingresses = ref([])
const services = ref([])
const namespaces = ref([])
const loading = ref(false)
const notification = ref({ message: '', type: '' })
const showEditModal = ref(false)
const currentIngress = ref(null)


function editIngress(ingress) {
  currentIngress.value = ingress
  showEditModal.value = true
}

function onIngressUpdated() {
  showNotification('Ingress updated successfully')
  fetchIngresses()
}

const form = ref({
  namespace: 'default',
  name: '',
  host: '',
  serviceName: '',
  servicePort: 80,
  ingressClass: 'nginx'
})

// Fetch initial data
onMounted(async () => {
  await Promise.all([
    fetchIngresses(),
    fetchNamespaces(),
    fetchServices()
  ])
})

async function fetchIngresses() {
  loading.value = true
  try {
    const res = await axios.get('/kube/ingresses')
    ingresses.value = res.data
  } catch (e) {
    showNotification('Failed to fetch ingresses', 'error')
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

async function fetchServices() {
  try {
    const res = await axios.get('/kube/services')
    services.value = res.data
  } catch (e) {
    showNotification('Failed to fetch services', 'error')
  }
}

function showNotification(msg, type = 'success') {
  notification.value = { message: msg, type }
  setTimeout(() => notification.value.message = '', 3000)
}

async function createIngress() {
  try {
    await axios.post('/kube/ingresses', form.value)
    showNotification('Ingress created successfully!')
    await fetchIngresses()
    resetForm()
  } catch (err) {
    showNotification(err.response?.data?.error || 'Creation failed', 'error')
    console.error('Error:', err.response?.data)
  }
}

async function deleteIngress(namespace, name) {
  if (!confirm(`Delete ingress ${name} in ${namespace}?`)) return
  
  try {
    await axios.delete(`/kube/ingresses/${namespace}/${name}`)
    showNotification('Ingress deleted')
    await fetchIngresses()
  } catch (err) {
    showNotification('Deletion failed', 'error')
  }
}

function resetForm() {
  form.value = {
    namespace: 'default',
    name: '',
    host: '',
    serviceName: '',
    servicePort: 80,
    ingressClass: 'nginx'
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
      <div class="flex items-center mb-4 md:mb-0">
        <div class="bg-yellow-600 p-2 rounded-lg mr-3">
          <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Kubernetes Ingresses</h1>
      </div>
      
      <div class="flex items-center">
        <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
          {{ ingresses.length }} Total Ingresses
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

    <!-- Create Ingress Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
      <div class="flex items-center mb-4">
        <svg class="h-5 w-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <h2 class="text-lg font-semibold text-gray-800">Create New Ingress</h2>
      </div>
      
      <form @submit.prevent="createIngress" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Namespace Select -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Namespace</label>
          <div class="relative">
            <select 
              v-model="form.namespace" 
              class="w-full border border-gray-300 rounded-lg p-2 pr-8 focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all appearance-none"
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

        <!-- Ingress Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Ingress Name</label>
          <input 
            v-model="form.name" 
            type="text" 
            required 
            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" 
            placeholder="my-ingress"
          >
        </div>

        <!-- Host -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Host</label>
          <div class="relative">
            <input 
              v-model="form.host" 
              type="text" 
              required 
              class="w-full border border-gray-300 rounded-lg p-2 pl-9 focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" 
              placeholder="example.com"
            >
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Ingress Class -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Ingress Class</label>
          <input 
            v-model="form.ingressClass" 
            type="text" 
            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" 
            placeholder="nginx"
          >
        </div>

        <!-- Service Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Service</label>
          <div class="relative">
            <select 
              v-model="form.serviceName" 
              class="w-full border border-gray-300 rounded-lg p-2 pr-8 focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all appearance-none" 
              required
            >
              <option value="">Select a Service</option>
              <option v-for="svc in services" :value="svc.name">{{ svc.name }} ({{ svc.namespace }})</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
              <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Service Port -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Service Port</label>
          <input 
            v-model.number="form.servicePort" 
            type="number" 
            min="1" 
            max="65535" 
            required
            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all"
            placeholder="80"
          >
        </div>

        <div class="md:col-span-2">
          <button 
            type="submit" 
            class="bg-yellow-600 text-white px-6 py-2 rounded-lg hover:bg-yellow-700 transition-colors shadow-sm flex items-center"
          >
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Create Ingress
          </button>
        </div>
      </form>
    </div>

    <!-- Ingresses Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center">
          <svg class="h-5 w-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
          </svg>
          <h2 class="text-xl font-semibold text-gray-800">Ingress List</h2>
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
      <div v-else-if="ingresses.length === 0" class="p-8 text-center">
        <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
          <svg class="h-8 w-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">No Ingresses Found</h3>
        <p class="text-gray-500">Create your first ingress to get started.</p>
      </div>
      
      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Namespace</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Host</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="ingress in ingresses" :key="`${ingress.namespace}-${ingress.name}`" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8 rounded-md bg-yellow-100 flex items-center justify-center mr-3">
                    <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                    </svg>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ ingress.name }}</div>
                    <div class="text-xs text-gray-500">{{ ingress.class || 'Default class' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ ingress.namespace }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="ingress.hosts && ingress.hosts.length" class="flex flex-wrap gap-1">
                  <span 
                    v-for="host in ingress.hosts" 
                    :key="host"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                  >
                    {{ host }}
                  </span>
                </div>
                <span v-else class="text-sm text-gray-500">N/A</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 border border-gray-200"
                >
                  {{ ingress.class || 'Default' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ ingress.service || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="editIngress(ingress)"
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
                  @click="deleteIngress(ingress.namespace, ingress.name)"
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

  <EditIngressModal 
    v-if="currentIngress"
    :ingress="currentIngress"
    :show="showEditModal"
    @close="showEditModal = false"
    @updated="onIngressUpdated"
  />
</template>

<style>
/* Simple spinner style */
.loader {
  border: 3px solid #f3f3f3;
  border-top: 3px solid #d97706; /* yellow-600 */
  border-radius: 50%;
  width: 16px;
  height: 16px;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>