<template>

  <v-badge
      :color="hasCombo ? 'info' : 'error'"
      :content="hasCombo ? 'Yes' : 'No'"
      inline
  ></v-badge>


</template>

<script setup>
const props = defineProps({
  hasCombo: Boolean,

})
</script>
