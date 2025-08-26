<script setup>
import {ref, watch} from 'vue';
import {useCustomizerStore} from '@/Service/stores/customizer';
import NotificationDD from './NotificationDD.vue';
import ProfileDD from './ProfileDD.vue';
import RightMobileSidebar from './RightMobileSidebar.vue';
import {Icon} from '@iconify/vue';
import Logo from '../logo/Logo.vue';
import ThemeToggler from './ThemeToggler.vue';

const customizer = useCustomizerStore();
const priority = ref(customizer.setHorizontalLayout ? 0 : 0);

watch(priority, (newPriority) => {
    priority.value = newPriority;
});
</script>

<template>
    <v-app-bar id="top" :priority="priority" class="main-head" elevation="0" height="70">
        <v-btn
            class="hidden-md-and-down custom-hover-primary" color="primary"
            icon size="small" variant="text"
            @click.stop="customizer.SET_MINI_SIDEBAR(!customizer.mini_sidebar)"
        >
            <Icon height="22" icon="solar:hamburger-menu-line-duotone"/>
        </v-btn>


        <v-btn class="hidden-lg-and-up custom-hover-primary" color="primary" icon size="small" variant="text"
               @click.stop="customizer.SET_SIDEBAR_DRAWER">
            <Icon height="22" icon="solar:hamburger-menu-line-duotone"/>
        </v-btn>
        <!-- ---------------------------------------------- -->
        <!-- Mega menu -->
        <!-- ---------------------------------------------- -->
        <div class="hidden-sm-and-down">
            <!--            <Navigations/>-->
        </div>

        <v-spacer class="hidden-sm-and-down"/>

        <!-- ---------------------------------------------- -->
        <!-- Mobile Logo -->
        <!-- ---------------------------------------------- -->
        <div class="hidden-md-and-up">
            <Logo/>
        </div>

        <!-- ThemeToggler -->
        <ThemeToggler/>

        <!-- ---------------------------------------------- -->
        <!-- Notification -->
        <!-- ---------------------------------------------- -->
        <div class="hidden-sm-and-down">
            <NotificationDD/>
        </div>

        <!-- ---------------------------------------------- -->
        <!-- User Profile -->
        <!-- ---------------------------------------------- -->
        <div class="hidden-sm-and-down">
            <ProfileDD/>
        </div>

        <!----Mobile ----->
        <v-menu :close-on-content-click="true" class="mobile_popup ">
            <template v-slot:activator="{ props }">
                <v-btn class="hidden-md-and-up custom-hover-primary" color="primary" icon size="small" v-bind="props"
                       variant="text">
                    <Icon height="22" icon="solar:menu-dots-bold-duotone"/>
                </v-btn>
            </template>
            <v-sheet class="mt-4 dropdown-box px-4 py-3" elevation="10" rounded="lg">
                <div class="d-flex justify-space-between align-center">
                    <RightMobileSidebar/>
                    <NotificationDD/>
                    <ProfileDD/>
                </div>
            </v-sheet>
        </v-menu>
    </v-app-bar>
</template>
