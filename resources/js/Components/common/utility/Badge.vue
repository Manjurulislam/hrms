<template>
    <v-badge
        :color="status ? 'success' : 'error'"
        :content="status ? 'Active' : 'Inactive'"
        inline
    />
</template>
<script setup>
const props = defineProps({
    status: Boolean,
})
</script>
