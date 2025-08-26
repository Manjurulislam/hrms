<script setup>
import {Icon} from '@iconify/vue';
</script>

<template>
    <!-- ---------------------------------------------- -->
    <!-- mega menu DD -->
    <!-- ---------------------------------------------- -->
    <v-menu open-on-click open-on-hover>
        <template v-slot:activator="{ props }">
            <v-btn class="hidden-sm-and-down custom-hover-primary" color="primary" icon size="small" v-bind="props"
                   variant="text">
                <Icon height="20" icon="solar:widget-3-line-duotone"/>
            </v-btn>
        </template>
    </v-menu>
</template>
s
