<template>
  <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-20">
    <div class="card card-statistics h-100">
      <div class="card-body">
        <div class="clearfix">
          <div class="float-right">
            <span :class="iconClass">
              <i :class="icon" class="highlight-icon" aria-hidden="true"></i>
            </span>
          </div>
          <div class="float-left text-left">
            <p class="card-text text-dark">
              {{ title }}
            </p>
            <h4>{{ formattedValue }}</h4>
          </div>
        </div>
        <p v-if="link" class="text-muted pt-3 mb-0 mt-2 border-top">
          <i class="fas fa-binoculars mr-1" aria-hidden="true"></i>
          <a :href="link" target="_blank">
            <span class="text-danger">{{ linkText }}</span>
          </a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  icon: {
    type: String,
    required: true,
  },
  iconClass: {
    type: String,
    default: 'text-success',
  },
  link: {
    type: String,
    default: null,
  },
  linkText: {
    type: String,
    default: 'عرض البيانات',
  },
  formatNumber: {
    type: Boolean,
    default: false,
  },
})

const formattedValue = computed(() => {
  if (props.formatNumber && typeof props.value === 'number') {
    return props.value.toLocaleString('ar-EG')
  }
  return props.value
})
</script>

<style scoped>
.highlight-icon {
  font-size: 2rem;
}
</style>







