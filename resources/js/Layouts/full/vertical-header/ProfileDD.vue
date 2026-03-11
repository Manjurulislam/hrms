<script setup>
import {Link, router} from '@inertiajs/vue3';
</script>

<template>
    <!-- ---------------------------------------------- -->
    <!-- notifications DD -->
    <!-- ---------------------------------------------- -->
    <v-menu open-on-click open-on-hover>
        <template v-slot:activator="{ props }">
            <v-btn class="custom-hover-primary" color="primary" icon v-bind="props" variant="text">
                <v-avatar size="35">
                    <img alt="Julia" src="/assets/images/profile/user-1.jpg" width="35"/>
                </v-avatar>
            </v-btn>
        </template>
        <v-sheet elevation="10" rounded="md" width="260">
            <div class="pa-4">
                <div class="d-flex align-center pb-3">
                    <v-avatar size="40">
                        <img alt="avatar" src="/assets/images/profile/user-1.jpg" width="40"/>
                    </v-avatar>
                    <div class="ml-3">
                        <h6 class="text-subtitle-2 font-weight-bold">{{ $page.props.auth.user.name }}</h6>
                        <span class="text-caption textSecondary">{{ $page.props.auth.user.email }}</span>
                    </div>
                </div>
                <v-divider/>
                <v-list class="py-0 mt-2" density="compact" lines="one">
                    <v-list-item class="px-2 rounded-md" color="primary" @click="router.visit(route('profile'))">
                        <template #prepend>
                            <v-icon size="small">mdi-account-outline</v-icon>
                        </template>
                        <v-list-item-title class="text-subtitle-2">My Profile</v-list-item-title>
                    </v-list-item>
                    <v-list-item class="px-2 rounded-md" color="error" @click="router.post(route('logout'))">
                        <template #prepend>
                            <v-icon size="small">mdi-logout</v-icon>
                        </template>
                        <v-list-item-title class="text-subtitle-2">Sign out</v-list-item-title>
                    </v-list-item>
                </v-list>
            </div>
        </v-sheet>
    </v-menu>
</template>
