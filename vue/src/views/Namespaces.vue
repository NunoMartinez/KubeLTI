<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const namespaces = ref([])
const newNamespace = ref('')

const fetchNamespaces = async () => {
  try {
    const res = await axios.get('/kube/namespaces')
    namespaces.value = res.data
  } catch (err) {
    console.error('Failed to load namespaces:', err)
  }
}

const createNamespace = async () => {
  if (!newNamespace.value) return
  try {
    await axios.post('/kube/namespaces', { name: newNamespace.value })
    newNamespace.value = ''
    await fetchNamespaces()
  } catch (err) {
    alert('Failed to create namespace.')
    console.error(err)
  }
}

const deleteNamespace = async (name) => {
  if (!confirm(`Delete namespace "${name}"? This cannot be undone.`)) return
  try {
    await axios.delete(`/kube/namespaces/${name}`)
    await fetchNamespaces()
  } catch (err) {
    alert('Failed to delete namespace.')
    console.error(err)
  }
}

onMounted(fetchNamespaces)
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-8 space-y-8 max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Namespaces</h1>

    <!-- Create Namespace -->
    <div class="bg-white p-6 rounded shadow border border-gray-200">
      <form @submit.prevent="createNamespace" class="flex gap-4 items-center">
        <input
          v-model="newNamespace"
          type="text"
          placeholder="Enter new namespace name"
          class="border border-gray-300 rounded px-4 py-2 w-full"
        />
        <button
          type="submit"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
          Create
        </button>
      </form>
    </div>

    <!-- Namespace Table -->
    <div class="overflow-auto bg-white rounded shadow border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created At</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="ns in namespaces" :key="ns.name">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ ns.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">
              <span
                class="px-2 py-0.5 rounded-full text-xs font-semibold"
                :class="{
                  'bg-green-100 text-green-800': ns.status === 'Active',
                  'bg-red-100 text-red-800': ns.status !== 'Active'
                }"
              >
                {{ ns.status }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ new Date(ns.created_at).toLocaleString() }}
            </td>
            <td class="px-6 py-4 text-right">
              <button
                @click="deleteNamespace(ns.name)"
                class="text-red-600 hover:underline text-sm"
              >
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
