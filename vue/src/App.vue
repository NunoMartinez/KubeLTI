<script setup>
import { RouterLink, RouterView } from 'vue-router'

</script>

<template>
  <div class="flex flex-col min-h-screen bg-gray-100">
    <main class="flex-1 bg-gray-50 flex flex-col">
      <RouterView />
    </main>
  </div>
</template>
