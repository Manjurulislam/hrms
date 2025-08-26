<script setup lang="ts">
import {appsLink} from '@/_mockApis/headerData';
import { Icon } from '@iconify/vue';
</script>
<template>
    <!-- ---------------------------------------------- -->
    <!-- apps link -->
    <!-- ---------------------------------------------- -->
    <v-row>
        <v-col cols="12" md="6" v-for="(item, i) in appsLink" :key="i">
            <router-link :to="item.href" class="text-decoration-none custom-text-primary">
                <div class="d-flex align-center">
                    <v-avatar size="45" :color=" 'light' + item.color" rounded="md">
                        <Icon :icon="'solar:' + item.avatar" height="24" :class="'text-' + item.color"/>
                    </v-avatar>
                    <div class="ml-3">
                        <h6 class="text-subtitle-1 mb-1 heading custom-title">{{ item.title }}</h6>
                        <p class="text-subtitle-2 textSecondary">{{ item.subtext }}</p>
                    </div>
                </div>
            </router-link>
        </v-col>
    </v-row>
</template>
