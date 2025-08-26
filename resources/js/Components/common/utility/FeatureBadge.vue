<template>

  <v-badge
      :color="featured ? 'secondary' : 'error'"
      :content="featured ? 'Yes' : 'No'"
      inline
  ></v-badge>


</template>

<script setup>
const props = defineProps({
  featured: Boolean,

})
</script>
