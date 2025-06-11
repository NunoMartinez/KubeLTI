<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  // Interval in seconds for auto-refresh
  interval: {
    type: Number,
    default: 30
  },
  // Whether to start auto-refresh immediately
  autoStart: {
    type: Boolean,
    default: true
  },
  // Display the time remaining until next refresh
  showCountdown: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['refresh']);

const isAutoRefreshEnabled = ref(props.autoStart);
const timer = ref(null);
const countdown = ref(props.interval);

// Start the auto-refresh timer
const startAutoRefresh = () => {
  stopAutoRefresh(); // Clear any existing timer
  isAutoRefreshEnabled.value = true;
  countdown.value = props.interval;
  
  timer.value = setInterval(() => {
    countdown.value -= 1;
    
    if (countdown.value <= 0) {
      emit('refresh');
      countdown.value = props.interval;
    }
  }, 1000); // Update every second
};

// Stop the auto-refresh timer
const stopAutoRefresh = () => {
  if (timer.value) {
    clearInterval(timer.value);
    timer.value = null;
  }
  isAutoRefreshEnabled.value = false;
};

// Toggle auto-refresh
const toggleAutoRefresh = () => {
  if (isAutoRefreshEnabled.value) {
    stopAutoRefresh();
  } else {
    startAutoRefresh();
  }
};

// Manual refresh button handler
const handleManualRefresh = () => {
  emit('refresh');
  
  // Reset countdown if auto-refresh is enabled
  if (isAutoRefreshEnabled.value) {
    countdown.value = props.interval;
  }
};

// Format seconds to MM:SS
const formatTime = (seconds) => {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
};

onMounted(() => {
  if (props.autoStart) {
    startAutoRefresh();
  }
});

onBeforeUnmount(() => {
  stopAutoRefresh();
});

// Expose methods for parent components
defineExpose({
  startAutoRefresh,
  stopAutoRefresh,
  toggleAutoRefresh
});
</script>

<template>
  <div class="flex items-center space-x-2">
    <!-- Manual refresh button -->
    <button 
      @click="handleManualRefresh"
      class="inline-flex items-center px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-md transition-colors"
      title="Refresh data"
    >
      <svg 
        class="h-4 w-4 mr-1" 
        :class="{ 'animate-spin': isAutoRefreshEnabled }"
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
      >
        <path 
          stroke-linecap="round" 
          stroke-linejoin="round" 
          stroke-width="2" 
          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" 
        />
      </svg>
      Refresh
    </button>
    
    <!-- Auto-refresh toggle -->
    <button 
      @click="toggleAutoRefresh"
      class="inline-flex items-center px-3 py-1.5 rounded-md transition-colors"
      :class="isAutoRefreshEnabled ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'"
      :title="isAutoRefreshEnabled ? 'Disable auto-refresh' : 'Enable auto-refresh'"
    >
      <svg 
        class="h-4 w-4 mr-1" 
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
      >
        <path 
          v-if="isAutoRefreshEnabled"
          stroke-linecap="round" 
          stroke-linejoin="round" 
          stroke-width="2" 
          d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" 
        />
        <path 
          v-else
          stroke-linecap="round" 
          stroke-linejoin="round" 
          stroke-width="2" 
          d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z" 
        />
      </svg>
      {{ isAutoRefreshEnabled ? 'Auto' : 'Auto' }}
    </button>
    
    <!-- Countdown display -->
    <span 
      v-if="isAutoRefreshEnabled && showCountdown" 
      class="text-xs text-gray-500 font-mono"
    >
      Next: {{ formatTime(countdown) }}
    </span>
  </div>
</template>