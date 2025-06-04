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
        

        <!-- Add more links as needed later -->
      </nav>
    </header>

   <main class="min-h-screen bg-gray-50 flex flex-col">
  <div class="flex-1">
    <RouterView />
  </div>
</main>
  </div>
</template>
