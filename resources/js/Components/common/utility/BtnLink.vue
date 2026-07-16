<template>
    <Link :href="linkHrf" class="d-inline-flex mx-1">
        <i :class="[icon, color]"
           :title="buttonTitle"
           aria-hidden="true"
           class="mdi v-icon"
           @mouseleave="buttonTitle = ''" @mouseover="buttonTitle = 'edit'"></i>
    </Link>
</template>

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
