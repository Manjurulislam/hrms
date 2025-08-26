<template>
    <v-toolbar :rounded="true" class="border-b" color="#FFFFFF" density="compact">
        <v-toolbar-title class="text-uppercase text-subtitle-2 font-weight-bold">
            {{ title }}
            {{ getTotalItem() }}
        </v-toolbar-title>
        <v-spacer></v-spacer>

        <!-- Multiple Extra Routes (New Dynamic Approach) -->
        <template v-if="extraRoutes && extraRoutes.length > 0">
            <Link
                v-for="(extraRoute, index) in extraRoutes"
                :key="index"
                :class="getButtonClass(index, extraRoutes.length)"
                :href="extraRoute.queryParam != null ? route(extraRoute.route, extraRoute.queryParam) : route(extraRoute.route)"
            >
                <v-icon v-if="extraRoute.icon" :icon="extraRoute.icon"></v-icon>
                {{ extraRoute.title }}
            </Link>
        </template>

        <!-- Backward Compatibility: Single Extra Route (Legacy) -->
        <Link v-else-if="extraRoute"
              :href="extraRoute.queryParam != null ? route(extraRoute.route, extraRoute.queryParam) : route(extraRoute.route)"
              class="v-btn bg-darkgray v-btn--density-default v-btn--size-small mx-2">
            <v-icon v-if="extraRoute.icon" :icon="extraRoute.icon"></v-icon>
            {{ extraRoute.title }}
        </Link>

        <!-- Main Router (Back button, etc.) -->
        <Link v-if="router"
              :href="router.queryParam != null ? route(router.route, router.queryParam) : route(router.route)"
              class="v-btn bg-darkgray v-btn--density-default v-btn--size-small">
            <v-icon v-if="icon" :icon="icon"></v-icon>
            {{ router.title }}
        </Link>
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
    // Legacy single extra route (for backward compatibility)
    extraRoute: {
        title: String,
        route: String,
        icon: String,
        queryParam: String,
    },
    // New dynamic multiple extra routes
    extraRoutes: {
        type: Array,
        default: () => [],
        validator: (routes) => {
            return routes.every(route =>
                route.hasOwnProperty('title') &&
                route.hasOwnProperty('route')
            );
        }
    }
});

function getTotalItem() {
    return props.totalItem ? '(' + props.totalItem + ')' : '';
}

function getButtonClass(index, totalRoutes) {
    const baseClass = 'v-btn bg-darkgray v-btn--density-default v-btn--size-small';
    // Add margin based on position
    if (totalRoutes === 1) {
        return `${baseClass} mx-2`;
    } else if (index === totalRoutes - 1) {
        // Last button gets mx-2 (margin on both sides)
        return `${baseClass} mx-2`;
    } else {
        // Other buttons get mr-2 (right margin only)
        return `${baseClass} mr-2`;
    }
}
</script>
