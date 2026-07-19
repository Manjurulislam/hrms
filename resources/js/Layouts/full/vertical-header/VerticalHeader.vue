<script setup>
import {ref, watch} from 'vue';
import {useCustomizerStore} from '@/Service/stores/customizer';
import NotificationDD from './NotificationDD.vue';
import ProfileDD from './ProfileDD.vue';
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
        <!-- Notification (TODO: enable when notification feature is ready) -->
        <!-- ---------------------------------------------- -->
        <!-- <div class="hidden-sm-and-down">
            <NotificationDD/>
        </div> -->

        <!-- ---------------------------------------------- -->
        <!-- User Profile (all breakpoints; single tap opens the sign-out menu) -->
        <!-- ---------------------------------------------- -->
        <ProfileDD/>
    </v-app-bar>
</template>
