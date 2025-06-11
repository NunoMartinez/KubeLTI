<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import EditDeploymentModal from '@/components/EditDeploymentModal.vue'
import RefreshControl from '@/components/RefreshControl.vue'

const deployments = ref([])
const namespaces = ref([])

const loadingDeployments = ref(false)
const loadingCreate = ref(false)
const loadingDelete = ref(null) // store name of deleting deployment for per-row spinner
const showEditModal = ref(false)
const currentDeployment = ref(null)


function editDeployment(deployment) {
  currentDeployment.value = deployment
  showEditModal.value = true
}

function onDeploymentUpdated() {
  showNotification('Deployment updated successfully')
  fetchDeployments()
}

const notification = ref({ message: '', type: '' }) // {type: 'success'|'error'}

const form = ref({
  namespace: '',
  name: '',
  image: '',
  replicas: 1
})

const fetchDeployments = async () => {
  loadingDeployments.value = true
  try {
    const res = await axios.get('/kube/deployments')
    deployments.value = res.data
  } catch (e) {
    showNotification('Failed to fetch deployments', 'error')
    console.error(e)
  } finally {
    loadingDeployments.value = false
  }
}

const fetchNamespaces = async () => {
  try {
    const res = await axios.get('/kube/namespaces')
    namespaces.value = res.data
    if (namespaces.value.length && !form.value.namespace) {
      form.value.namespace = namespaces.value[0].name
    }
  } catch (e) {
    showNotification('Failed to fetch namespaces', 'error')
    console.error(e)
  }
}

const showNotification = (msg, type = 'success') => {
  notification.value = { message: msg, type }
  setTimeout(() => (notification.value = { message: '', type: '' }), 4000)
}

const createDeployment = async () => {
  if (!form.value.namespace || !form.value.name || !form.value.image || form.value.replicas < 1) {
    showNotification('Please fill all fields correctly', 'error')
    return
  }

  loadingCreate.value = true
  try {
    await axios.post('/kube/deployments', form.value)
    showNotification(`Deployment "${form.value.name}" created successfully!`, 'success')
    await fetchDeployments()
    // Clear form on success
    form.value.name = ''
    form.value.image = ''
    form.value.replicas = 1
  } catch (err) {
    showNotification('Failed to create deployment', 'error')
    console.error(err)
  } finally {
    loadingCreate.value = false
  }
}

const deleteDeployment = async (namespace, name) => {
  if (!confirm(`Delete deployment "${name}" in namespace "${namespace}"?`)) return

  loadingDelete.value = name
  try {
    await axios.delete(`/kube/deployments/${namespace}/${name}`)
    showNotification(`Deployment "${name}" deleted successfully!`, 'success')
    await fetchDeployments()
  } catch (err) {
    showNotification('Failed to delete deployment', 'error')
    console.error(err)
  } finally {
    loadingDelete.value = null
  }
}

onMounted(() => {
  fetchNamespaces()
  fetchDeployments()
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
      <div class="flex items-center mb-4 md:mb-0">
        <div class="bg-blue-600 p-2 rounded-lg mr-3">
          <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Kubernetes Deployments</h1>
      </div>
      
      <div class="flex items-center space-x-4">
        <RefreshControl @refresh="fetchDeployments" :interval="30" />
        
        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
          {{ deployments.length }} Total Deployments
        </span>
      </div>
    </div>

    <!-- Notification -->
    <transition name="fade">
      <div
        v-if="notification.message"
        :class="`p-4 rounded-lg mb-6 shadow-sm ${notification.type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'}`"
      >
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
    </transition>

    <!-- Create Deployment Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
      <div class="flex items-center mb-4">
        <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <h2 class="text-lg font-semibold text-gray-800">Create New Deployment</h2>
      </div>
      
      <form @submit.prevent="createDeployment" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 items-end">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Namespace</label>
          <div class="relative">
            <select 
              v-model="form.namespace" 
              required 
              class="w-full border border-gray-300 rounded-lg p-2 pr-8 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all appearance-none"
              :disabled="loadingCreate"
            >
              <option v-for="ns in namespaces" :key="ns.name" :value="ns.name">
                {{ ns.name }}
              </option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
              <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </div>
          </div>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Deployment Name</label>
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="deployment-name"
            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            :disabled="loadingCreate"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
          <input
            v-model="form.image"
            type="text"
            required
            placeholder="nginx:latest"
            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            :disabled="loadingCreate"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Replicas</label>
          <input
            v-model.number="form.replicas"
            type="number"
            min="1"
            required
            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            :disabled="loadingCreate"
          />
        </div>
        
        <div>
          <button
            type="submit"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors shadow-sm flex items-center disabled:opacity-50"
            :disabled="loadingCreate"
          >
            <span v-if="loadingCreate" class="loader mr-2"></span>
            <svg v-else class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Create Deployment
          </button>
        </div>
      </form>
    </div>

    <!-- Deployments Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center">
          <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
          <h2 class="text-xl font-semibold text-gray-800">Deployment List</h2>
        </div>
      </div>
      
      <!-- Loading state -->
      <div v-if="loadingDeployments" class="p-8 text-center">
        <div class="animate-pulse flex flex-col items-center">
          <div class="w-full h-8 bg-gray-200 rounded mb-4"></div>
          <div class="w-full h-8 bg-gray-200 rounded mb-4"></div>
          <div class="w-full h-8 bg-gray-200 rounded"></div>
        </div>
      </div>
      
      <!-- Empty state -->
      <div v-else-if="deployments.length === 0" class="p-8 text-center">
        <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
          <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">No Deployments Found</h3>
        <p class="text-gray-500">Create your first deployment to get started.</p>
      </div>
      
      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Namespace</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Replicas</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Available</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="dep in deployments" :key="dep.name + dep.namespace" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8 rounded-md bg-blue-100 flex items-center justify-center mr-3">
                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ dep.name }}</div>
                    <div class="text-xs text-gray-500">Created {{ new Date(dep.created_at).toLocaleDateString() }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ dep.namespace }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <span class="text-sm text-gray-900 font-medium">{{ dep.replicas }}</span>
                  <span class="ml-1 text-xs text-gray-500">requested</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  class="px-2.5 py-1 text-xs font-medium rounded-full"
                  :class="{
                    'bg-green-100 text-green-800 border border-green-200': dep.available === dep.replicas,
                    'bg-yellow-100 text-yellow-800 border border-yellow-200': dep.available > 0 && dep.available < dep.replicas,
                    'bg-red-100 text-red-800 border border-red-200': dep.available === 0
                  }"
                >
                  {{ dep.available }}/{{ dep.replicas }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ new Date(dep.created_at).toLocaleString() }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                <button
                  @click="editDeployment(dep)"
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
                  @click="deleteDeployment(dep.namespace, dep.name)"
                  class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-md transition-colors"
                  :disabled="loadingDelete === dep.name"
                >
                  <span class="flex items-center">
                    <span v-if="loadingDelete === dep.name" class="loader mr-1"></span>
                    <svg v-else class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
  
  <EditDeploymentModal 
    v-if="currentDeployment"
    :deployment="currentDeployment"
    :show="showEditModal"
    @close="showEditModal = false"
    @updated="onDeploymentUpdated"
  />
</template>

<style>
/* Simple spinner style */
.loader {
  border: 3px solid #f3f3f3;
  border-top: 3px solid #3b82f6; /* blue-500 */
  border-radius: 50%;
  width: 16px;
  height: 16px;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Fade transition for notifications */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
