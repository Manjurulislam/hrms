<script setup>
const props = defineProps({
  registered: Boolean,

})
</script>

<template>
  <v-badge
      :color="registered ? 'success' : 'primary'"
      :content="registered ? 'Registered' : 'Guest'"
      inline
  />
</template>
<style scoped>

</style>
