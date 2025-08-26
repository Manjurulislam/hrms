<template>
  <v-badge
      :color="paymentStatus ? 'secondary' : 'error'"
      :content="paymentStatus ? 'Paid' : 'Pending'"
      inline
  ></v-badge>


</template>

<script setup>
const props = defineProps({
  paymentStatus: Boolean,
})
</script>