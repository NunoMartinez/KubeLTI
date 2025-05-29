<template>
  <div class="flex min-h-screen bg-gray-50">
    <Sidebar />
    <div class="flex-1 overflow-auto p-8">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import Sidebar from '@/components/Sidebar.vue'
import { provide, ref, onMounted } from 'vue'
import axios from 'axios'

const clusterInfo = ref({})
provide('clusterInfo', clusterInfo)

onMounted(async () => {
  try {
    const res = await axios.get('/kube/api')
    clusterInfo.value = res.data
  } catch (e) {
    console.error('Cluster info failed to load:', e)
  }
})
</script>
