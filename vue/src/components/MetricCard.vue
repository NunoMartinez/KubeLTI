<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ title }}</h3>
    <div class="flex items-end justify-between">
      <div>
        <p class="text-2xl font-bold">
          {{ formattedValue }} <span class="text-sm font-normal text-gray-500">{{ unit }}</span>
        </p>
        <p class="text-sm text-gray-500">
          {{ percentage }}% of {{ formattedMax }} {{ unit }}
        </p>
      </div>
      <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
        <div 
          class="h-full bg-blue-500" 
          :style="{ width: percentage + '%' }"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: String,
  value: [Number, String],
  max: [Number, String],
  unit: {
    type: String,
    default: ''
  }
})

const formattedValue = computed(() => {
  if (props.unit === 'GB') return parseFloat(props.value).toFixed(2)
  return props.value
})

const formattedMax = computed(() => {
  if (props.unit === 'GB') return parseFloat(props.max).toFixed(2)
  return props.max
})

const percentage = computed(() => {
  if (!props.max || props.max <= 0) return 0
  return Math.min(100, Math.round((props.value / props.max) * 100))
})
</script>