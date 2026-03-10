<template>
  <div class="loading-spinner" :class="sizeClass">
    <div class="spinner-border" :class="colorClass" role="status">
      <span class="sr-only">Loading...</span>
    </div>
    <p v-if="message" class="mt-2">{{ message }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  size: {
    type: String,
    default: 'normal', // 'small', 'normal', 'large'
    validator: (value) => ['small', 'normal', 'large'].includes(value),
  },
  color: {
    type: String,
    default: 'primary', // 'primary', 'secondary', 'success', etc.
  },
  message: {
    type: String,
    default: null,
  },
})

const sizeClass = computed(() => {
  const sizes = {
    small: 'spinner-border-sm',
    normal: '',
    large: 'spinner-border-lg',
  }
  return sizes[props.size] || ''
})

const colorClass = computed(() => {
  return `text-${props.color}`
})
</script>

<style scoped>
.loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.spinner-border {
  width: 3rem;
  height: 3rem;
  border-width: 0.25em;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  border-width: 0.15em;
}

.spinner-border-lg {
  width: 5rem;
  height: 5rem;
  border-width: 0.35em;
}
</style>







