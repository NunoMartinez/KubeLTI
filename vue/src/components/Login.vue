<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const email = ref('')
const password = ref('')
const error = ref(null)

const auth = useAuthStore()
const router = useRouter()

const handleLogin = async () => {
  const success = await auth.login({
    email: email.value,
    password: password.value
  })

  if (success) {
    error.value = null
    await router.push('/dashboard') // ✅ redirect
  } else {
    error.value = 'Invalid email or password'
  }
}
</script>


<template>
  <div class="bg-white p-6 rounded shadow-md w-full max-w-md mx-auto">
    <h2 class="text-xl font-semibold mb-4">Login MINITESTE</h2>

    <div v-if="error" class="mb-4 text-red-600 text-sm">{{ error }}</div>

    <form @submit.prevent="handleLogin" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium">Email</label>
        <input v-model="email" type="email" id="email" required class="mt-1 w-full border-gray-300 rounded-md shadow-sm" />
      </div>

      <div>
        <label for="password" class="block text-sm font-medium">Password</label>
        <input v-model="password" type="password" id="password" required class="mt-1 w-full border-gray-300 rounded-md shadow-sm" />
      </div>

      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        Login
      </button>
    </form>
  </div>
</template>
