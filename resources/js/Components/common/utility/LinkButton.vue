<template>
  <Link :class="color"
        :href="linkHrf"
        class="v-btn mx-1 v-btn--elevated v-btn--icon
        v-theme--light bg-secondary v-btn--density-comfortable v-btn--size-small
        v-btn--variant-elevated"
  >
    <i :class="icon"
       :title="buttonTitle"
       aria-hidden="true"
       class="mdi v-icon"
       @mouseleave="buttonTitle = ''" @mouseover="buttonTitle = 'Log view'"></i>
  </Link>
</template>z

<script setup>
import {Link} from '@inertiajs/vue3';
import {computed, ref} from "vue";

const props = defineProps({
  color: String,
  icon: String,
  route: String,
});
const buttonTitle = ref('');
const linkHrf = computed(() => {
  return props.route != null ? props.route : '';
})

</script>

<style scoped>

</style>
