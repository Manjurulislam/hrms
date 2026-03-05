<script setup>
import {computed} from 'vue';
import {usePage} from "@inertiajs/vue3";
import NavGroup from './NavGroup/index.vue';
import NavItem from './NavItem/index.vue';
import NavCollapse from './NavCollapse/NavCollapse.vue';
import Logo from '../logo/Logo.vue';

const page = usePage();
const sidebarMenu = computed(() => page.props.auth.menus);

const openedGroups = computed(() => {
    const currentPath = page.url.split('?')[0];
    const opened = [];

    sidebarMenu.value?.forEach(item => {
        if (item.children) {
            const hasActiveChild = item.children.some(child => {
                if (!child.to) return false;
                try {
                    const targetPath = new URL(route(child.to)).pathname;
                    return currentPath === targetPath || (targetPath !== '/' && currentPath.startsWith(targetPath + '/'));
                } catch {
                    return false;
                }
            });
            if (hasActiveChild) {
                opened.push(item.title);
            }
        }
    });

    return opened;
});
</script>

<template>
    <!---Logo part -->
    <div class="pa-4 pb-0">
        <Logo/>
    </div>
    <!-- Navigation -->
    <perfect-scrollbar class="scrollnavbar">
        <v-list :opened="openedGroups" class="px-4">
            <!---Menu Loop -->
            <template v-for="(item, i) in sidebarMenu">
                <!---Item Sub Header -->
                <NavGroup v-if="item.header" :key="item.title" :item="item"/>
                <!---If Has Child -->
                <NavCollapse v-else-if="item.children" :item="item" :level="0" class="leftPadding"/>
                <!---Single Item-->
                <NavItem v-else :item="item" class="leftPadding"/>
                <!---End Single Item-->
            </template>
        </v-list>
    </perfect-scrollbar>
</template>
