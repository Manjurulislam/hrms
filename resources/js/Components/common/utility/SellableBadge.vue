<template>

  <v-badge
      :color="sellable ? 'info' : 'error'"
      :content="sellable ? 'Yes' : 'No'"
      inline
  ></v-badge>


</template>

<script setup>
const props = defineProps({
  sellable: Boolean,

})
</script>
