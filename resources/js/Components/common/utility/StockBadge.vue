<template>

  <v-badge
      :color="stockable ? 'info' : 'error'"
      :content="stockable ? 'Available' : 'Out of Stock'"
      inline
  ></v-badge>


</template>

<script setup>
const props = defineProps({
  stockable: Boolean,

})
</script>
