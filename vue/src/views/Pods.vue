<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const pods = ref([])
const namespaces = ref([])

const loadingPods = ref(false)
const loadingCreate = ref(false)
const loadingDelete = ref(null) // store name of deleting pod

const notification = ref({ message: '', type: '' }) // {type: 'success'|'error'}

const form = ref({
  namespace: '',
  name: '',
  image: '',
})

const fetchPods = async () => {
  loadingPods.value = true
  try {
    const res = await axios.get('/kube/pods')
    pods.value = res.data
  } catch (e) {
    showNotification('Failed to fetch pods', 'error')
    console.error(e)
  } finally {
    loadingPods.value = false
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

const createPod = async () => {
  if (!form.value.namespace || !form.value.name || !form.value.image) {
    showNotification('Please fill all fields', 'error')
    return
  }

  loadingCreate.value = true
  try {
    await axios.post('/kube/pods', form.value)
    showNotification(`Pod "${form.value.name}" created successfully!`, 'success')
    await fetchPods()
    form.value.name = ''
    form.value.image = ''
  } catch (err) {
    showNotification('Failed to create pod', 'error')
    console.error(err)
  } finally {
    loadingCreate.value = false
  }
}

const deletePod = async (namespace, name) => {
  if (!confirm(`Delete pod "${name}" in namespace "${namespace}"?`)) return

  loadingDelete.value = name
  try {
    await axios.delete(`/kube/pods/${namespace}/${name}`)
    showNotification(`Pod "${name}" deleted successfully!`, 'success')
    await fetchPods()
  } catch (err) {
    showNotification('Failed to delete pod', 'error')
    console.error(err)
  } finally {
    loadingDelete.value = null
  }
}

onMounted(() => {
  fetchNamespaces()
  fetchPods()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-8 max-w-6xl mx-auto space-y-8">
    <h1 class="text-2xl font-bold mb-4">Pods</h1>

    <transition name="fade">
      <div
        v-if="notification.message"
        :class="[
          'p-4 rounded mb-6',
          notification.type === 'success' ? 'bg-green-100 text-green-800' : '',
          notification.type === 'error' ? 'bg-red-100 text-red-800' : '',
        ]"
      >
        {{ notification.message }}
      </div>
    </transition>

    <div class="bg-white p-6 rounded shadow border border-gray-200">
      <form @submit.prevent="createPod" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Namespace</label>
          <select
            v-model="form.namespace"
            required
            class="border rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option v-for="ns in namespaces" :key="ns.name" :value="ns.name">{{ ns.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pod Name</label>
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="pod-name"
            :disabled="loadingCreate"
            class="border rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
          <input
            v-model="form.image"
            type="text"
            required
            placeholder="nginx:latest"
            :disabled="loadingCreate"
            class="border rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
          />
        </div>
        <div>
          <button
            type="submit"
            :disabled="loadingCreate"
            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50 flex items-center justify-center"
          >
            <span v-if="loadingCreate" class="loader mr-2"></span>
            Create Pod
          </button>
        </div>
      </form>
    </div>

    <div class="bg-white rounded shadow border border-gray-200 overflow-auto relative">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Namespace</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Node</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Restarts</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-if="loadingPods">
            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Loading pods...</td>
          </tr>
          <tr v-for="pod in pods" :key="pod.name + pod.namespace">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ pod.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ pod.namespace }}</td>
            <td
              class="px-6 py-4 text-sm"
              :class="{
                'text-green-600': pod.status === 'Running',
                'text-yellow-600': pod.status === 'Pending',
                'text-red-600': pod.status !== 'Running' && pod.status !== 'Pending'
              }"
            >{{ pod.status }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ pod.nodeName }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ pod.restarts }}</td>
            <td class="px-6 py-4 text-right">
              <button
                @click="deletePod(pod.namespace, pod.name)"
                :disabled="loadingDelete === pod.name"
                class="text-red-600 hover:underline text-sm flex items-center disabled:opacity-50"
              >
                <span v-if="loadingDelete === pod.name" class="loader mr-1"></span>
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="!loadingPods && pods.length === 0">
            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No pods found.</td>
          </tr>
        </tbody>
      </table>

      <div
        v-if="loadingPods"
        class="absolute inset-0 bg-white bg-opacity-70 flex justify-center items-center"
      >
        <span class="loader"></span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.loader {
  border: 3px solid #f3f3f3;
  border-top: 3px solid #3b82f6; /* Tailwind blue-500 */
  border-radius: 50%;
  width: 16px;
  height: 16px;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
