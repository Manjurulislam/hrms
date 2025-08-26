<script setup>
import {Icon} from '@iconify/vue';
</script>

<template>

    <v-menu open-on-click open-on-hover>
        <template v-slot:activator="{ props }">
            <v-btn class="custom-hover-primary" color="primary" icon size="small" v-bind="props" variant="text">
                <Icon height="22" icon="solar:widget-line-duotone"/>
            </v-btn>
        </template>
        <v-sheet elevation="10" rounded="md" width="360">
            <perfect-scrollbar style="height: 370px">
                <div class="pa-6">

                </div>
            </perfect-scrollbar>
        </v-sheet>
    </v-menu>
</template>

