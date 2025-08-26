<script setup>
import {Link} from '@inertiajs/vue3';

const props = defineProps({
    title: String,
    icon: String,
    router: {
        title: String,
        route: String,
        queryParam: String,
    },
});
</script>

<template>
    <v-card elevation="10">
        <v-toolbar :rounded="true" class="border-b" color="#FFFFFF" density="compact">
            <v-toolbar-title class="text-uppercase text-subtitle-2 font-weight-bold">
                {{ title }}
            </v-toolbar-title>
            <v-spacer></v-spacer>
            <Link
                :href="router.queryParam != null ? route(router.route, router.queryParam) : route(router.route)"
                class="v-btn bg-darkgray
            v-btn--density-default
            v-btn--size-small">
                <v-icon :icon="icon"></v-icon>
                {{ router.title }}
            </Link>
        </v-toolbar>
        <v-divider></v-divider>
        <v-card-text>
            <slot/>
        </v-card-text>
    </v-card>
</template>

<style lang="scss" scoped>

</style>
