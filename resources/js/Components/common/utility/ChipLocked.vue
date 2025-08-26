<script setup>
const props = defineProps({
    status: Boolean,

})
</script>

<template>
    <v-icon v-if="status" icon="mdi mdi-shield-lock" size="x-large"/>
    <v-icon v-else color="success" icon="mdi mdi-shield-lock-open" size="x-large"/>
</template>
<style lang="scss" scoped>

</style>
