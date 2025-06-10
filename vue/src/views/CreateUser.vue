<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const router = useRouter()
const toast = useToast()

const isLoading = ref(false)
const errorMessage = ref('')

// Form data
const formData = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  nickname: '',
  type: 'P', // Default to regular user
})

// Computed properties for validation
const isValidEmail = computed(() => {
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return !formData.value.email || emailPattern.test(formData.value.email)
})

const passwordsMatch = computed(() => {
  return formData.value.password === formData.value.password_confirmation
})

const isFormValid = computed(() => {
  return formData.value.name && 
         formData.value.email && 
         formData.value.password &&
         formData.value.password_confirmation &&
         formData.value.nickname &&
         isValidEmail.value &&
         passwordsMatch.value
})

// Reset form
const resetForm = () => {
  formData.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    nickname: '',
    type: 'P',
  }
  errorMessage.value = ''
}

// Handle user creation
const createUser = async () => {
  if (!isFormValid.value) {
    errorMessage.value = 'Please correct the errors in the form'
    return
  }
  
  isLoading.value = true
  
  try {
    await axios.post('users', formData.value)
    
    toast.success('User created successfully')
    router.push('/dashboard')
  } catch (error) {
    console.error('Error creating user:', error)
    
    if (error.response && error.response.data) {
      if (error.response.data.errors) {
        // Format validation errors
        const errors = Object.values(error.response.data.errors).flat()
        errorMessage.value = errors.join('\n')
      } else if (error.response.data.message) {
        errorMessage.value = error.response.data.message
      } else {
        errorMessage.value = 'An error occurred while creating the user'
      }
    } else {
      errorMessage.value = 'An error occurred while creating the user'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="container mx-auto py-8 px-4">
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
      <div class="p-8">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold text-gray-800">Create New User</h2>
          <button 
            @click="resetForm" 
            class="text-sm text-blue-600 hover:text-blue-800"
          >
            Reset Form
          </button>
        </div>
        
        <!-- Error message -->
        <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 text-red-700 whitespace-pre-line">
          {{ errorMessage }}
        </div>
        
        <form @submit.prevent="createUser" class="space-y-6">
          <!-- Name -->
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
            <input 
              v-model="formData.name" 
              type="text" 
              id="name" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              required
            />
          </div>
          
          <!-- Nickname -->
          <div>
            <label for="nickname" class="block text-sm font-medium text-gray-700">Nickname</label>
            <input 
              v-model="formData.nickname" 
              type="text" 
              id="nickname" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              required
              maxlength="20"
            />
            <p class="mt-1 text-sm text-gray-500">Max 20 characters, must be unique</p>
          </div>
          
          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input 
              v-model="formData.email" 
              type="email" 
              id="email" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              :class="{ 'border-red-300 ring-red-500': formData.email && !isValidEmail }"
              required
            />
            <p v-if="formData.email && !isValidEmail" class="mt-1 text-sm text-red-600">
              Please enter a valid email address
            </p>
          </div>
          
          <!-- User Type -->
          <div>
            <label for="type" class="block text-sm font-medium text-gray-700">User Type</label>
            <select 
              v-model="formData.type" 
              id="type" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="A">Admin</option>
              <option value="P">Regular User</option>
            </select>
          </div>
          
          <!-- Password -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input 
              v-model="formData.password" 
              type="password" 
              id="password" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              required
              minlength="8"
            />
            <p class="mt-1 text-sm text-gray-500">Minimum 8 characters</p>
          </div>
          
          <!-- Confirm Password -->
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
            <input 
              v-model="formData.password_confirmation" 
              type="password" 
              id="password_confirmation" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              :class="{ 'border-red-300 ring-red-500': formData.password_confirmation && !passwordsMatch }"
              required
            />
            <p v-if="formData.password_confirmation && !passwordsMatch" class="mt-1 text-sm text-red-600">
              Passwords do not match
            </p>
          </div>
          
          <!-- Submit button -->
          <div class="flex justify-end space-x-3">
            <button 
              type="button"
              @click="$router.back()"
              class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              :disabled="!isFormValid || isLoading"
            >
              <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isLoading ? 'Creating...' : 'Create User' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>