<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const deployments = ref([])
const namespaces = ref([])

const loadingDeployments = ref(false)
const loadingCreate = ref(false)
const loadingDelete = ref(null) // store name of deleting deployment for per-row spinner

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
  <div class="min-h-screen bg-gray-50 p-8 max-w-6xl mx-auto space-y-8">
    <h1 class="text-2xl font-bold mb-4">Deployments</h1>

    <!-- Notification -->
    <transition name="fade">
      <div
        v-if="notification.message"
        :class="{
          'bg-green-100 text-green-800': notification.type === 'success',
          'bg-red-100 text-red-800': notification.type === 'error',
        }"
        class="p-4 rounded mb-6"
      >
        {{ notification.message }}
      </div>
    </transition>

    <!-- Create Deployment Form -->
    <div class="bg-white p-6 rounded shadow border border-gray-200">
      <form @submit.prevent="createDeployment" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Namespace</label>
          <select v-model="form.namespace" required class="border rounded px-3 py-2 w-full">
            <option v-for="ns in namespaces" :key="ns.name" :value="ns.name">
              {{ ns.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Deployment Name</label>
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="deployment-name"
            class="border rounded px-3 py-2 w-full"
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
            class="border rounded px-3 py-2 w-full"
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
            class="border rounded px-3 py-2 w-full"
            :disabled="loadingCreate"
          />
        </div>
        <div>
          <button
            type="submit"
            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
            :disabled="loadingCreate"
          >
            <span v-if="loadingCreate" class="loader mr-2"></span>
            Create Deployment
          </button>
        </div>
      </form>
    </div>

    <!-- Deployments Table -->
    <div class="bg-white rounded shadow border border-gray-200 overflow-auto relative">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Namespace</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Replicas</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Available</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="dep in deployments" :key="dep.name + dep.namespace">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ dep.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ dep.namespace }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ dep.replicas }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ dep.available }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ new Date(dep.created_at).toLocaleString() }}</td>
            <td class="px-6 py-4 text-right">
              <button
                @click="deleteDeployment(dep.namespace, dep.name)"
                class="text-red-600 hover:underline text-sm flex items-center"
                :disabled="loadingDelete === dep.name"
              >
                <span v-if="loadingDelete === dep.name" class="loader mr-1"></span>
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Loading Overlay -->
      <div
        v-if="loadingDeployments"
        class="absolute inset-0 bg-white bg-opacity-70 flex justify-center items-center"
      >
        <span class="loader"></span>
      </div>
    </div>
  </div>
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
