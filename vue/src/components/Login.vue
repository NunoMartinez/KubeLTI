<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const email = ref('')
const password = ref('')
const error = ref(null)
const isLoading = ref(false)
const rememberMe = ref(false)

const auth = useAuthStore()
const router = useRouter()

const isValidEmail = computed(() => {
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return !email.value || emailPattern.test(email.value)
})

const isFormValid = computed(() => {
  return email.value && password.value && isValidEmail.value
})

const handleLogin = async () => {
  if (!isFormValid.value) return
  
  isLoading.value = true
  error.value = null
  
  try {
    const success = await auth.login({
      email: email.value,
      password: password.value,
      remember: rememberMe.value
    })

    if (success) {
      console.log('Login successful, redirecting to dashboard')
      // Use replace to ensure we can't go back to login page
      await router.replace('/dashboard')
      
      // As a fallback, use window.location after a short delay
      setTimeout(() => {
        if (window.location.pathname !== '/dashboard') {
          console.log('Fallback: forcing navigation to dashboard')
          window.location.href = '/dashboard'
        }
      }, 100)
    } else {
      error.value = 'Invalid email or password'
    }
  } catch (e) {
    error.value = 'An error occurred during login. Please try again.'
    console.error('Login error:', e)
  } finally {
    isLoading.value = false
  }
}

const handleForgotPassword = () => {
  // Implement password recovery functionality
  router.push('/forgot-password')
}
</script>

<template>
  <div class="bg-white p-6 rounded shadow-md w-full max-w-md mx-auto">
    <h2 class="text-xl font-semibold mb-4">Login</h2>

    <div v-if="error" role="alert" class="mb-4 p-2 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm">
      {{ error }}
    </div>

    <form @submit.prevent="handleLogin" class="space-y-4" novalidate>
      <div>
        <label for="email" class="block text-sm font-medium">Email</label>
        <input 
          v-model="email" 
          type="email" 
          id="email" 
          name="email"
          autocomplete="email"
          required 
          :class="[
            'mt-1 w-full border rounded-md shadow-sm p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
            !isValidEmail ? 'border-red-300' : 'border-gray-300'
          ]" 
        />
        <p v-if="email && !isValidEmail" class="mt-1 text-sm text-red-600">Please enter a valid email address</p>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium">Password</label>
        <input 
          v-model="password" 
          type="password" 
          id="password" 
          name="password"
          autocomplete="current-password"
          required 
          class="mt-1 w-full border-gray-300 rounded-md shadow-sm p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
        />
      </div>

      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <input 
            v-model="rememberMe" 
            id="remember-me" 
            name="remember-me" 
            type="checkbox" 
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" 
          />
          <label for="remember-me" class="ml-2 block text-sm text-gray-700">Remember me</label>
        </div>
        
        <div class="text-sm">
          <a @click="handleForgotPassword" class="text-blue-600 hover:text-blue-500 cursor-pointer">
            Forgot password?
          </a>
        </div>
      </div>

      <button 
        type="submit" 
        class="w-full flex justify-center items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="!isFormValid || isLoading"
      >
        <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ isLoading ? 'Logging in...' : 'Login' }}
      </button>
    </form>
  </div>
</template>
