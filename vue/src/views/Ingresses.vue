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
  <div class="min-h-screen bg-gray-50 p-8 max-w-6xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold">Ingresses</h1>

    <!-- Notification -->
    <div v-if="notification.message" 
         :class="`p-4 rounded mb-4 ${notification.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`">
      {{ notification.message }}
    </div>

    <!-- Create Ingress Form -->
    <div class="bg-white p-6 rounded shadow border border-gray-200">
      <h2 class="text-lg font-medium mb-4">Create New Ingress</h2>
      <form @submit.prevent="createIngress" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <!-- Namespace Select -->
        <div>
          <label class="block text-sm font-medium mb-1">Namespace</label>
          <select v-model="form.namespace" class="w-full border rounded p-2">
            <option v-for="ns in namespaces" :value="ns.name">{{ ns.name }}</option>
          </select>
        </div>

        <!-- Ingress Name -->
        <div>
          <label class="block text-sm font-medium mb-1">Ingress Name</label>
          <input v-model="form.name" type="text" required 
                 class="w-full border rounded p-2" placeholder="my-ingress">
        </div>

        <!-- Host -->
        <div>
          <label class="block text-sm font-medium mb-1">Host</label>
          <input v-model="form.host" type="text" required 
                 class="w-full border rounded p-2" placeholder="example.com">
        </div>

        <!-- Ingress Class -->
        <div>
          <label class="block text-sm font-medium mb-1">Ingress Class</label>
          <input v-model="form.ingressClass" type="text" 
                 class="w-full border rounded p-2" placeholder="nginx">
        </div>

        <!-- Service Selection -->
        <div>
          <label class="block text-sm font-medium mb-1">Service</label>
          <select v-model="form.serviceName" class="w-full border rounded p-2" required>
            <option value="">Select a Service</option>
            <option v-for="svc in services" :value="svc.name">{{ svc.name }} ({{ svc.namespace }})</option>
          </select>
        </div>

        <!-- Service Port -->
        <div>
          <label class="block text-sm font-medium mb-1">Service Port</label>
          <input v-model.number="form.servicePort" type="number" min="1" max="65535" required
                 class="w-full border rounded p-2">
        </div>

        <div class="md:col-span-2">
          <button type="submit" 
                  class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Create Ingress
          </button>
        </div>
      </form>
    </div>

    <!-- Ingresses Table -->
    <div class="bg-white rounded shadow border border-gray-200 overflow-auto relative">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Namespace</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Host</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="ingress in ingresses" :key="`${ingress.namespace}-${ingress.name}`">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ ingress.name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ ingress.namespace }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ ingress.hosts?.join(', ') || 'N/A' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ ingress.class || 'N/A' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              {{ ingress.service || 'N/A' }}
            </td>
             <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
    <button @click="editIngress(ingress)"
            class="text-blue-600 hover:text-blue-900">
      Edit
    </button>
    <button @click="deleteIngress(ingress.namespace, ingress.name)"
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

  <EditIngressModal 
    v-if="currentIngress"
    :ingress="currentIngress"
    :show="showEditModal"
    @close="showEditModal = false"
    @updated="onIngressUpdated"
  />
</template>