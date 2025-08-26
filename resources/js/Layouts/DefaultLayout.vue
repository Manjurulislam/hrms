<script setup>
import {useCustomizerStore} from "@/Service/stores/customizer";
import VerticalSidebarVue from '@/Layouts/full/vertical-sidebar/VerticalSidebar.vue';
import VerticalHeaderVue from "@/Layouts/full/vertical-header/VerticalHeader.vue";

const customizer = useCustomizerStore();
</script>

<template>
    <v-locale-provider>
        <v-app :class="[
            customizer.actTheme,
            customizer.mini_sidebar ? 'mini-sidebar' : '',
            customizer.setHorizontalLayout ? 'horizontalLayout' : 'verticalLayout',
            customizer.setBorderCard ? 'cardBordered' : '',
        ]" :theme="customizer.actTheme" class="bg-containerBg">
            <!---Customizer location right side--->
            <v-navigation-drawer
                v-model="customizer.Sidebar_drawer"
                :rail="customizer.mini_sidebar"
                app
                class="leftSidebar"
                elevation="0"
                expand-on-hover
                left
                rail-width="75" width="260"
            >
                <VerticalSidebarVue v-if="!customizer.setHorizontalLayout"/>
            </v-navigation-drawer>

            <div :class="customizer.boxed ? 'maxWidth' : 'full-header'">
                <VerticalHeaderVue v-if="!customizer.setHorizontalLayout"/>
            </div>
            <v-main class="mr-md-4">
                <div class="mb-3 hr-layout bg-containerBg">
                    <v-container class="page-wrapper bg-background  pt-md-8 rounded-xl" fluid>
                        <slot/>
                    </v-container>
                </div>
            </v-main>
        </v-app>
    </v-locale-provider>
</template>
