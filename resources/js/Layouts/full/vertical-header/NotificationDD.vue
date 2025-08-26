<script lang="ts" setup>
import {Icon} from '@iconify/vue';
</script>
<template>
    <!-- ---------------------------------------------- -->
    <!-- notifications DD -->
    <!-- ---------------------------------------------- -->
    <v-menu open-on-click open-on-hover>
        <template v-slot:activator="{ props }">
            <v-btn class="custom-hover-primary" color="primary" icon size="small" v-bind="props" variant="text">
                <Icon height="22" icon="solar:bell-bing-line-duotone"/>
            </v-btn>
        </template>
        <v-sheet elevation="10" rounded="md" width="360">
            <div class="px-6 pt-6 pb-4">
                <div class="d-flex align-center justify-space-between">
                    <h6 class="text-h5">Notifications</h6>
                    <v-chip class="text-white rounded-sm" color="primary" size="small" variant="flat">5 New</v-chip>
                </div>
            </div>
            <perfect-scrollbar style="height: 350px">
                <!--                <v-list class="py-0 theme-list" lines="two">-->
                <!--                    <v-list-item v-for="item in notifications" :key="item.title" :value="item" color="primary" class="py-3 px-6">-->
                <!--                        <template v-slot:prepend>-->
                <!--                            <v-avatar size="45" :color=" 'light' + item.color" rounded="circle">-->
                <!--                                <Icon :icon="'solar:' + item.avatar" height="20" :class="'text-' + item.color"/>-->
                <!--                            </v-avatar>-->
                <!--                        </template>-->
                <!--                        <div class="d-flex justify-space-between">-->
                <!--                            <h6 class="text-subtitle-1 heading mb-1">{{ item.title }}</h6>-->
                <!--                            <span class="text-subtitle-2 textSecondary">{{ item.time }}</span>-->
                <!--                        </div>-->
                <!--                        <p class="text-subtitle-2 font-weight-regular textSecondary">{{ item.subtitle }}</p>-->
                <!--                    </v-list-item>-->
                <!--                </v-list>-->
            </perfect-scrollbar>
            <div class="py-4 px-6 text-center">
                <v-btn block color="primary" size="large" variant="flat">See all Notifications</v-btn>
            </div>
        </v-sheet>
    </v-menu>
</template>
