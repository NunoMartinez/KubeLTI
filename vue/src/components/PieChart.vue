<template>
  <div class="relative">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  options: {
    type: Object,
    default: () => ({})
  }
})

const chartCanvas = ref(null)
let chartInstance = null

onMounted(() => {
  renderChart()
})

watch(() => props.data, () => {
  if (chartInstance) {
    chartInstance.destroy()
  }
  renderChart()
}, { deep: true })

const renderChart = () => {
  if (!chartCanvas.value) return
  
  chartInstance = new Chart(chartCanvas.value, {
    type: 'pie',
    data: props.data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      ...props.options,
      plugins: {
        legend: {
          position: 'right',
          ...props.options?.plugins?.legend
        },
        ...props.options?.plugins
      }
    }
  })
}
</script>