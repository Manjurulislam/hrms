<script setup>
const props = defineProps({item: Object});
</script>

<template>
    <v-list-subheader class="smallCap text-uppercase text-subtitle-2 mt-3 font-weight-bold d-flex align-items-center ">
        <span class="mini-icon"><DotsIcon class="iconClass" size="16" stroke-width="1.5"/></span>
        <span class="mini-text">{{ props.item.header }}</span>
    </v-list-subheader>
</template>
