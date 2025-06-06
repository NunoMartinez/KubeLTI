<script setup>
import { ref, watch } from 'vue'
import * as yaml from 'yaml'
import axios from 'axios'

const props = defineProps({
  pod: Object,
  show: Boolean
})

const emit = defineEmits(['close', 'updated'])

const editingMode = ref('yaml')
const rawYaml = ref('')
const rawJson = ref('')
const error = ref(null)
const yamlError = ref(null)
const loading = ref(false)
const fullPod = ref(null)

// Function to fetch pod data
async function fetchPodData() {
  if (!props.pod) return;
  
  error.value = null
  yamlError.value = null
  try {
    loading.value = true
    const response = await axios.get(`/kube/pods/${props.pod.namespace}/${props.pod.name}`)
    fullPod.value = response.data
    
    // Convert to proper editing formats
    rawYaml.value = yaml.stringify(fullPod.value, {
      sortMapEntries: true,
      indent: 2,
      keepBlobsInJSON: true
    })
    
    rawJson.value = JSON.stringify(fullPod.value, null, 2)
  } catch (e) {
    error.value = 'Failed to load pod: ' + e.message
    console.error(e)
  } finally {
    loading.value = false
  }
}

// Fetch complete pod data when modal opens
watch(() => props.show, async (show) => {
  if (show) {
    await fetchPodData()
  }
}, { immediate: true })

function validateYaml(yamlContent) {
  try {
    const parsed = yaml.parse(yamlContent);
    
    // Check for required top-level fields
    const requiredFields = ['apiVersion', 'kind', 'metadata', 'spec'];
    const missingFields = requiredFields.filter(field => !parsed[field]);
    
    if (missingFields.length > 0) {
      throw new Error(`Missing required fields: ${missingFields.join(', ')}`);
    }
    
    // Check metadata has name and namespace
    if (!parsed.metadata.name || !parsed.metadata.namespace) {
      throw new Error('Metadata must include name and namespace');
    }
    
    // Check spec has containers
    if (!parsed.spec.containers || parsed.spec.containers.length === 0) {
      throw new Error('Pod spec must include at least one container');
    }
    
    return true;
  } catch (e) {
    throw new Error(`YAML Validation Error: ${e.message}`);
  }
}

function validateCurrentYaml() {
  try {
    validateYaml(rawYaml.value);
    yamlError.value = null;
  } catch (e) {
    yamlError.value = e.message;
  }
}

function cleanPodManifest(manifest) {
  // Create a deep copy
  const cleaned = JSON.parse(JSON.stringify(manifest));
  
  // Remove immutable fields
  delete cleaned.status;
  delete cleaned.metadata?.managedFields;
  delete cleaned.metadata?.uid;
  delete cleaned.metadata?.resourceVersion;
  delete cleaned.metadata?.creationTimestamp;
  
  // Ensure required fields exist
  if (!cleaned.apiVersion) cleaned.apiVersion = 'v1';
  if (!cleaned.kind) cleaned.kind = 'Pod';
  
  // Handle securityContext at pod level
  if (cleaned.spec?.securityContext) {
    // If securityContext is an array, convert to object
    if (Array.isArray(cleaned.spec.securityContext)) {
      cleaned.spec.securityContext = {};
    }
  }
  
  // Ensure containers is an array
  if (cleaned.spec) {
    // If containers doesn't exist or is not an array, initialize it
    if (!cleaned.spec.containers || !Array.isArray(cleaned.spec.containers)) {
      // If it's an object but not an array, try to convert it
      if (cleaned.spec.containers && typeof cleaned.spec.containers === 'object') {
        const containersArray = [];
        // Try to convert object to array if it has numeric keys
        let isNumericKeys = false;
        for (const key in cleaned.spec.containers) {
          if (!isNaN(parseInt(key))) {
            containersArray[parseInt(key)] = cleaned.spec.containers[key];
            isNumericKeys = true;
          }
        }
        
        // If not numeric keys, it might be a single container object
        if (!isNumericKeys && Object.keys(cleaned.spec.containers).length > 0) {
          containersArray.push(cleaned.spec.containers);
        }
        
        cleaned.spec.containers = containersArray;
      } else {
        // Initialize as empty array if not an object
        cleaned.spec.containers = [];
      }
    }
    
    // Fix resources format in containers and handle container securityContext
    if (Array.isArray(cleaned.spec.containers)) {
      cleaned.spec.containers.forEach(container => {
        // Handle resources field properly
        if (container.resources) {
          // If resources is an array or not an object, replace with empty object
          if (Array.isArray(container.resources) || typeof container.resources !== 'object') {
            container.resources = {};
          } else {
            // Ensure limits and requests are objects, not arrays
            if (container.resources.limits && Array.isArray(container.resources.limits)) {
              container.resources.limits = {};
            }
            if (container.resources.requests && Array.isArray(container.resources.requests)) {
              container.resources.requests = {};
            }
          }
        }
        
        // Handle securityContext at container level
        if (container.securityContext) {
          // If securityContext is an array, convert to object
          if (Array.isArray(container.securityContext)) {
            container.securityContext = {};
          }
        }
      });
    }
  }
  
  return cleaned;
}

async function saveChanges() {
  try {
    loading.value = true;
    error.value = null;
    yamlError.value = null;

    let requestData = {};
    if (editingMode.value === 'yaml') {
      try {
        // Parse and validate YAML first
        const parsedYaml = yaml.parse(rawYaml.value);
        validateYaml(rawYaml.value);
        
        // Clean the parsed object
        const cleaned = cleanPodManifest(parsedYaml);
        
        requestData = { 
          json: cleaned // Send as JSON since we already parsed it
        };
      } catch (e) {
        throw new Error(`YAML Error: ${e.message}`);
      }
    } else {
      try {
        const json = JSON.parse(rawJson.value);
        requestData = { 
          json: cleanPodManifest(json) 
        };
      } catch (e) {
        throw new Error('Invalid JSON: ' + e.message);
      }
    }

    const response = await axios.put(
      `/kube/pods/${props.pod.namespace}/${props.pod.name}`,
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
                 'Failed to update pod';
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
              Edit Pod: {{ pod.name }} ({{ pod.namespace }})
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
                  :class="{ 'border-red-500': yamlError }"
                  spellcheck="false"
                  @input="validateCurrentYaml"
                ></textarea>
                <p v-if="yamlError" class="mt-1 text-xs text-red-500">
                  {{ yamlError }}
                </p>
                <p v-else class="mt-1 text-xs text-gray-500">
                  Edit the full Pod specification in YAML format
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
                  Edit the full Pod specification in JSON format
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