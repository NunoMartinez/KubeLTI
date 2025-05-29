<script setup>
import { RouterLink, RouterView } from 'vue-router'

</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white shadow">
      <nav class="max-w-7xl mx-auto px-4 py-4 flex space-x-6">
        <RouterLink
          to="/login"
          class="text-gray-800 hover:text-blue-600 font-medium"
          active-class="text-blue-600 font-bold"
        >
          Login
        </RouterLink>
        <RouterLink to="/dashboard"
  class="text-gray-900 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors"
  active-class="text-blue-600 font-semibold">
  Dashboard
</RouterLink>

        <!-- Add more links as needed later -->
      </nav>
    </header>

   <main class="flex min-h-screen bg-gray-50">
  <Sidebar />
  <div class="flex-1 overflow-auto ">
    <RouterView />
  </div>
</main>
  </div>
</template>
