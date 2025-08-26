<template>
  <v-toolbar :rounded="true" class="border-b" color="#FFFFFF" density="compact">
    <v-toolbar-title class="text-uppercase text-subtitle-2 font-weight-bold">
      {{ title }}
      {{ getTotalItem() }}
    </v-toolbar-title>
    <v-spacer></v-spacer>

  </v-toolbar>
</template>

<script setup>
import {Link} from '@inertiajs/vue3';

const props = defineProps({
  totalItem: Number,
  title: String,
  icon: String,
  router: {
    title: String,
    route: String,
    queryParam: String,
  },
  extraRoute: {
    title: String,
    route: String,
    icon: String,
    queryParam: String,
  }
});

function getTotalItem() {
  return props.totalItem ? '(' + props.totalItem + ')' : '';
}
</script>

<style scoped>

</style>
