<script setup>
import { ref, watch } from 'vue'
import * as yaml from 'yaml'
import axios from 'axios'

const props = defineProps({
  ingress: Object,
  show: Boolean
})

const emit = defineEmits(['close', 'updated'])

const editingMode = ref('yaml')
const rawYaml = ref('')
const rawJson = ref('')
const error = ref(null)
const loading = ref(false)
const fullIngress = ref(null)

// Function to fetch ingress data
async function fetchIngressData() {
  if (!props.ingress) return;
  
  error.value = null
  try {
    loading.value = true
    const response = await axios.get(`/kube/ingresses/${props.ingress.namespace}/${props.ingress.name}`)
    fullIngress.value = response.data
    
    // Convert to proper editing formats
    rawYaml.value = yaml.stringify(fullIngress.value, {
      sortMapEntries: true,
      indent: 2,
      keepBlobsInJSON: true
    })
    
    rawJson.value = JSON.stringify(fullIngress.value, null, 2)
  } catch (e) {
    error.value = 'Failed to load ingress: ' + e.message
    console.error(e)
  } finally {
    loading.value = false
  }
}

// Fetch complete ingress data when modal opens
watch(() => props.show, async (show) => {
  if (show) {
    await fetchIngressData()
  }
}, { immediate: true })

function validateYaml(yamlContent) {
  try {
    const parsed = yaml.parse(yamlContent);
    if (!parsed?.apiVersion || !parsed?.kind || !parsed?.metadata || !parsed?.spec) {
      throw new Error('Missing required Kubernetes fields');
    }
    return true;
  } catch (e) {
    throw new Error(`Invalid YAML: ${e.message}`);
  }
}

async function saveChanges() {
  try {
    loading.value = true;
    error.value = null;

    // Remove status field before sending
    const preparePayload = (content) => {
      if (content.includes('status:')) {
        return content.replace(/status:.*?(?=\n\w|$)/s, '').trim();
      }
      return content;
    };

    let requestData = {};
    if (editingMode.value === 'yaml') {
      const cleanedYaml = preparePayload(rawYaml.value);
      if (!cleanedYaml.trim()) {
        throw new Error('YAML content cannot be empty');
      }
      requestData = { yaml: cleanedYaml };
    } else {
      try {
        const json = JSON.parse(rawJson.value);
        delete json.status;
        requestData = { json };
      } catch (e) {
        throw new Error('Invalid JSON: ' + e.message);
      }
    }

    const response = await axios.put(
      `/kube/ingresses/${props.ingress.namespace}/${props.ingress.name}`,
      requestData,
      {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      }
    );

    // Only emit updated and close if we get here (no error thrown)
    emit('updated');
    emit('close');
  } catch (err) {
    error.value = err.response?.data?.message || 
                 err.message || 
                 'Failed to update ingress';
    console.error('Update error:', {
      error: err.response?.data,
      request: err.config?.data
    });
  } finally {
    loading.value = false;
  }
}


</script>

<template>
  <Transition name="modal">
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
          <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
              Edit Ingress: {{ ingress.name }} ({{ ingress.namespace }})
            </h3>
            
            <div class="flex mb-4 border-b">
              <button 
                @click="editingMode = 'yaml'"
                :class="{'border-b-2 border-blue-500': editingMode === 'yaml'}"
                class="px-4 py-2 text-sm font-medium"
              >
                YAML
              </button>
              <button 
                @click="editingMode = 'json'"
                :class="{'border-b-2 border-blue-500': editingMode === 'json'}"
                class="px-4 py-2 text-sm font-medium"
              >
                JSON
              </button>
            </div>

            <div v-if="error" class="mb-4 p-3 bg-red-50 text-red-700 rounded">
              {{ error }}
            </div>

            <!-- Loading state -->
            <div v-if="loading" class="flex justify-center py-8">
              <span class="loader"></span>
            </div>

            <template v-else>
              <!-- YAML Editor -->
              <div v-show="editingMode === 'yaml'">
                <textarea
                  v-model="rawYaml"
                  class="w-full h-96 font-mono text-sm border rounded p-2"
                  spellcheck="false"
                ></textarea>
                <p class="mt-1 text-xs text-gray-500">
                  Edit the full Ingress specification in YAML format
                </p>
              </div>

              <!-- JSON Editor -->
              <div v-show="editingMode === 'json'">
                <textarea
                  v-model="rawJson"
                  class="w-full h-96 font-mono text-sm border rounded p-2"
                  spellcheck="false"
                ></textarea>
                <p class="mt-1 text-xs text-gray-500">
                  Edit the full Ingress specification in JSON format
                </p>
              </div>
            </template>
          </div>

          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button
              @click="saveChanges"
              :disabled="loading"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
            >
              <span v-if="loading" class="loader mr-2"></span>
              Save Changes
            </button>
            <button
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>