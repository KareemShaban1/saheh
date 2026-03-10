<template>
  <div class="col-4 d-flex justify-content-center align-items-center mb-3 py-2">
    <a :href="link" style="color: inherit; text-decoration: none;">
      <div class="col-12 p-0 text-center">
        <img :src="icon" style="width: 30px; height: 30px" :alt="label" />
        <div class="col-12 p-0 text-center mt-2">
          <small>{{ label }}</small>
        </div>
      </div>
    </a>
  </div>
</template>

<script setup>
defineProps({
  icon: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  link: {
    type: String,
    required: true,
  },
})
</script>







