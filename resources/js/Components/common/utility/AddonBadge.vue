<template>

  <v-badge
      :color="hasAddons ? 'info' : 'error'"
      :content="hasAddons ? 'Yes' : 'No'"
      inline
  ></v-badge>


</template>

<script setup>
const props = defineProps({
  hasAddons: Boolean,

})
</script>
