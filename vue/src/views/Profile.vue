<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const auth = useAuthStore()
const isLoading = ref(false)
const isSaving = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

// Form data
const formData = ref({
  name: auth.userName || '',
  email: auth.userEmail || '',
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
})

// Computed properties for validation
const passwordsMatch = computed(() => {
  if (!formData.value.newPassword && !formData.value.confirmPassword) return true
  return formData.value.newPassword === formData.value.confirmPassword
})

const isFormValid = computed(() => {
  return formData.value.name && 
         formData.value.email && 
         passwordsMatch.value
})

// Reset messages
const resetMessages = () => {
  successMessage.value = ''
  errorMessage.value = ''
}

// Handle profile update
const updateProfile = async () => {
  resetMessages()
  
  if (!isFormValid.value) {
    errorMessage.value = 'Please correct the errors in the form'
    return
  }
  
  isSaving.value = true
  
  try {
    // Update profile information
    const profileData = {
      name: formData.value.name,
      email: formData.value.email
    }
    
    await axios.put('users/profile', profileData)
    
    // If password fields are filled, update password separately
    if (formData.value.currentPassword && formData.value.newPassword) {
      await axios.put('users/password', {
        current_password: formData.value.currentPassword,
        password: formData.value.newPassword,
        password_confirmation: formData.value.confirmPassword
      })
      
      // Clear password fields after successful update
      formData.value.currentPassword = ''
      formData.value.newPassword = ''
      formData.value.confirmPassword = ''
    }
    
    // Refresh user data
    const responseUser = await axios.get('users/me')
    auth.user = responseUser.data
    
    successMessage.value = 'Profile updated successfully'
  } catch (error) {
    console.error('Error updating profile:', error)
    
    if (error.response && error.response.data && error.response.data.message) {
      errorMessage.value = error.response.data.message
    } else {
      errorMessage.value = 'An error occurred while updating your profile'
    }
  } finally {
    isSaving.value = false
  }
}

// Handle profile image upload
const uploadImage = async (event) => {
  const file = event.target.files[0]
  if (!file) return
  
  // Check file type
  if (!file.type.match('image.*')) {
    errorMessage.value = 'Please select an image file'
    return
  }
  
  // Check file size (max 2MB)
  if (file.size > 2 * 1024 * 1024) {
    errorMessage.value = 'Image size should not exceed 2MB'
    return
  }
  
  resetMessages()
  isLoading.value = true
  
  try {
    const formData = new FormData()
    formData.append('photo', file)
    
    await axios.post('users/photo', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    // Refresh user data to get updated photo URL
    const responseUser = await axios.get('users/me')
    auth.user = responseUser.data
    
    successMessage.value = 'Profile photo updated successfully'
  } catch (error) {
    console.error('Error uploading image:', error)
    errorMessage.value = 'Failed to upload image'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="container mx-auto py-8 px-4">
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
      <div class="md:flex">
        <!-- Left side - Profile picture -->
        <div class="md:w-1/3 bg-gray-50 p-8 border-r border-gray-200">
          <div class="text-center">
            <div class="relative inline-block">
              <img 
                :src="auth.userPhotoUrl" 
                alt="Profile" 
                class="h-32 w-32 rounded-full object-cover border-4 border-white shadow-md mx-auto"
              />
              <div class="absolute bottom-0 right-0">
                <label 
                  class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-600 text-white cursor-pointer shadow-md hover:bg-blue-700"
                  :class="{ 'opacity-50 cursor-not-allowed': isLoading }"
                >
                  <input 
                    type="file" 
                    accept="image/*" 
                    class="hidden" 
                    @change="uploadImage" 
                    :disabled="isLoading" 
                  />
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </label>
              </div>
            </div>
            <h2 class="mt-4 text-xl font-semibold text-gray-800">{{ auth.userName }}</h2>
            <p class="text-gray-600">{{ auth.userEmail }}</p>
            <p v-if="auth.userType" class="mt-2 inline-block px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
              {{ auth.userTypeFormatted }}
            </p>
          </div>
        </div>
        
        <!-- Right side - Profile form -->
        <div class="md:w-2/3 p-8">
          <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Profile</h2>
          
          <!-- Success/Error messages -->
          <div v-if="successMessage" class="mb-4 p-3 bg-green-50 border-l-4 border-green-500 text-green-700">
            {{ successMessage }}
          </div>
          
          <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 text-red-700">
            {{ errorMessage }}
          </div>
          
          <form @submit.prevent="updateProfile" class="space-y-6">
            <!-- Name -->
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
              <input 
                v-model="formData.name" 
                type="text" 
                id="name" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                required
              />
            </div>
            
            <!-- Email -->
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
              <input 
                v-model="formData.email" 
                type="email" 
                id="email" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                required
              />
            </div>
            
            <div class="border-t border-gray-200 pt-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
              
              <!-- Current Password -->
              <div>
                <label for="current-password" class="block text-sm font-medium text-gray-700">Current Password</label>
                <input 
                  v-model="formData.currentPassword" 
                  type="password" 
                  id="current-password" 
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
              </div>
              
              <!-- New Password -->
              <div class="mt-4">
                <label for="new-password" class="block text-sm font-medium text-gray-700">New Password</label>
                <input 
                  v-model="formData.newPassword" 
                  type="password" 
                  id="new-password" 
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
              </div>
              
              <!-- Confirm Password -->
              <div class="mt-4">
                <label for="confirm-password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                <input 
                  v-model="formData.confirmPassword" 
                  type="password" 
                  id="confirm-password" 
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  :class="{ 'border-red-300 ring-red-500': formData.confirmPassword && !passwordsMatch }"
                />
                <p v-if="formData.confirmPassword && !passwordsMatch" class="mt-1 text-sm text-red-600">
                  Passwords do not match
                </p>
              </div>
            </div>
            
            <!-- Submit button -->
            <div class="flex justify-end">
              <button 
                type="submit" 
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                :disabled="!isFormValid || isSaving"
              >
                <svg v-if="isSaving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ isSaving ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>