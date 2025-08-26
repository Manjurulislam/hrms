<script setup>
const props = defineProps({
    status: Boolean,

})
</script>

<template>
    <v-badge
        :color="status ? 'success' : 'error'"
        :content="status ? 'Active' : 'Inactive'"
        inline
    />
</template>
<style lang="scss" scoped>

</style>
