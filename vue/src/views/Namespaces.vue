<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import EditNamespacesModal from '@/components/EditNamespacesModal.vue'

const namespaces = ref([])
const newNamespace = ref('')
const showEditModal = ref(false)
const currentNamespace = ref(null)
const notification = ref({ message: '', type: '' })

const fetchNamespaces = async () => {
  try {
    const res = await axios.get('/kube/namespaces')
    namespaces.value = res.data
  } catch (err) {
    showNotification('Failed to load namespaces', 'error')
    console.error('Failed to load namespaces:', err)
  }
}

const createNamespace = async () => {
  if (!newNamespace.value) return
  try {
    await axios.post('/kube/namespaces', { name: newNamespace.value })
    newNamespace.value = ''
    showNotification('Namespace created successfully')
    await fetchNamespaces()
  } catch (err) {
    showNotification('Failed to create namespace', 'error')
    console.error(err)
  }
}

const deleteNamespace = async (name) => {
  if (!confirm(`Delete namespace "${name}"? This cannot be undone.`)) return
  try {
    await axios.delete(`/kube/namespaces/${name}`)
    showNotification('Namespace deleted successfully')
    await fetchNamespaces()
  } catch (err) {
    showNotification('Failed to delete namespace', 'error')
    console.error(err)
  }
}

const editNamespace = (namespace) => {
  currentNamespace.value = namespace
  showEditModal.value = true
}

const onNamespaceUpdated = () => {
  showNotification('Namespace updated successfully')
  fetchNamespaces()
}

function showNotification(msg, type = 'success') {
  notification.value = { message: msg, type }
  setTimeout(() => notification.value.message = '', 3000)
}

onMounted(fetchNamespaces)
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
      <div class="flex items-center mb-4 md:mb-0">
        <div class="bg-purple-600 p-2 rounded-lg mr-3">
          <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Namespaces</h1>
      </div>
      
      <div class="flex items-center">
        <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
          {{ namespaces.length }} Total
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

    <!-- Create Namespace -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
      <div class="flex items-center mb-4">
        <svg class="h-5 w-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <h2 class="text-lg font-semibold text-gray-800">Create New Namespace</h2>
      </div>
      
      <form @submit.prevent="createNamespace" class="flex gap-4 items-center">
        <div class="relative flex-grow">
          <input
            v-model="newNamespace"
            type="text"
            placeholder="Enter new namespace name"
            class="border border-gray-300 rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
          />
          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
          </div>
        </div>
        <button
          type="submit"
          class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition-colors shadow-sm flex items-center"
        >
          <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Create Namespace
        </button>
      </form>
    </div>

    <!-- Namespace Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-6 border-b border-gray-200">
        <div class="flex items-center">
          <svg class="h-5 w-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
          <h2 class="text-xl font-semibold text-gray-800">Namespace List</h2>
        </div>
      </div>
      
      <div v-if="namespaces.length === 0" class="p-8 text-center">
        <div class="mx-auto w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4">
          <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">No Namespaces Found</h3>
        <p class="text-gray-500">Create your first namespace to get started.</p>
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created At</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="ns in namespaces" :key="ns.name" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-8 w-8 rounded-md bg-purple-100 flex items-center justify-center mr-3">
                    <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ ns.name }}</div>
                    <div class="text-xs text-gray-500">Created {{ new Date(ns.created_at).toLocaleDateString() }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-2.5 py-1 text-xs font-medium rounded-full"
                  :class="{
                    'bg-green-100 text-green-800 border border-green-200': ns.status === 'Active',
                    'bg-red-100 text-red-800 border border-red-200': ns.status !== 'Active'
                  }"
                >
                  {{ ns.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ new Date(ns.created_at).toLocaleString() }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                <button
                  @click="editNamespace(ns)"
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
                  @click="deleteNamespace(ns.name)"
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

  <EditNamespacesModal 
    v-if="currentNamespace"
    :namespace="currentNamespace"
    :show="showEditModal"
    @close="showEditModal = false"
    @updated="onNamespaceUpdated"
  />
</template>
