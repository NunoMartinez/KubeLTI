<template>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
      <div class="p-2 rounded-full" :class="getIconBgClass()">
        <component :is="getIconComponent()" class="h-5 w-5" :class="getIconColorClass()" />
      </div>
    </div>
    <div class="flex items-end justify-between">
      <div>
        <p class="text-2xl font-bold" v-if="title === 'CPU Usage'">
          {{ percentage }}% <span class="text-sm font-normal text-gray-500">usage</span>
        </p>
        <p class="text-2xl font-bold" v-else>
          {{ formattedValue }} <span class="text-sm font-normal text-gray-500">{{ unit }}</span>
        </p>
        <p class="text-sm text-gray-500" v-if="title === 'CPU Usage'">
          {{ formattedValue }} of {{ formattedMax }} {{ unit }}
        </p>
        <p class="text-sm text-gray-500" v-else>
          {{ percentage }}% of {{ formattedMax }} {{ unit }}
        </p>
      </div>
      <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
        <div 
          class="h-full" 
          :class="getProgressColorClass()"
          :style="{ width: percentage + '%' }"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, h } from 'vue'

const props = defineProps({
  title: String,
  value: [Number, String],
  max: [Number, String],
  unit: {
    type: String,
    default: ''
  },
  icon: {
    type: String,
    default: 'default'
  }
})

const formattedValue = computed(() => {
  if (props.unit === 'GB') return parseFloat(props.value).toFixed(2)
  if (props.unit === 'cores') return parseFloat(props.value).toFixed(1)
  return props.value
})

const formattedMax = computed(() => {
  if (props.unit === 'GB') return parseFloat(props.max).toFixed(2)
  if (props.unit === 'cores') return parseFloat(props.max).toFixed(1)
  return props.max
})

const percentage = computed(() => {
  if (!props.max || props.max <= 0) return 0
  return Math.min(100, Math.round((props.value / props.max) * 100))
})

// Get the appropriate color class based on usage percentage
const getProgressColorClass = () => {
  if (percentage.value >= 90) return 'bg-red-500'
  if (percentage.value >= 75) return 'bg-yellow-500'
  return 'bg-blue-500'
}

// Get icon background color class
const getIconBgClass = () => {
  if (props.icon === 'cpu') return 'bg-blue-100'
  if (props.icon === 'memory') return 'bg-green-100'
  if (props.icon === 'pods') return 'bg-purple-100'
  if (props.icon === 'disk') return 'bg-amber-100'
  return 'bg-gray-100'
}

// Get icon color class
const getIconColorClass = () => {
  if (props.icon === 'cpu') return 'text-blue-600'
  if (props.icon === 'memory') return 'text-green-600'
  if (props.icon === 'pods') return 'text-purple-600'
  if (props.icon === 'disk') return 'text-amber-600'
  return 'text-gray-600'
}

// Get the appropriate icon component based on the icon prop
const getIconComponent = () => {
  // Return SVG icon based on the icon prop
  return h('svg', {
    xmlns: 'http://www.w3.org/2000/svg',
    fill: 'none',
    viewBox: '0 0 24 24',
    stroke: 'currentColor',
    'stroke-width': '2'
  }, [
    h('path', {
      'stroke-linecap': 'round',
      'stroke-linejoin': 'round',
      'd': getIconPath()
    })
  ])
}

// Get the path for the icon
const getIconPath = () => {
  switch (props.icon) {
    case 'cpu':
      return 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z'
    case 'memory':
      return 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'
    case 'pods':
      return 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'
    case 'disk':
      return 'M4 5a2 2 0 012-2h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm2 0v14h12V5H6zm3 8h6m-6-4h6'
    default:
      return 'M13 10V3L4 14h7v7l9-11h-7z'
  }
}
</script>