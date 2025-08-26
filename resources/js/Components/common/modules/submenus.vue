<script setup>
import {Link} from "@inertiajs/vue3";

defineProps({
    items: Array,
})

</script>

<template>
    <v-row>
        <template v-for="(item, i) in items" :key="i">
            <v-col cols="2">
                <Link :href="item.to ? route(item.to) : ''">
                    <v-card class="overflow-hidden" elevation="10">
                        <v-card-item class="pa-0">
                            <v-sheet class="pa-6 secondary-gradient d-flex align-center">
                                <v-avatar class="me-3" color="secondary" size="48">
                                    <v-icon :icon="item.icon" size="25"></v-icon>
                                </v-avatar>
                                <p class="text-subtitle-1">{{ item.title }}</p>
                            </v-sheet>
                        </v-card-item>
                    </v-card>
                </Link>
            </v-col>
        </template>
    </v-row>
</template>

<style scoped>

</style>
