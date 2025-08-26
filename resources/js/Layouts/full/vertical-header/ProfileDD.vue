<script setup>
import {Link} from '@inertiajs/vue3';
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
        <v-sheet elevation="10" rounded="md" width="280">
            <div class="pa-6">
                <div class="d-flex align-center pb-6">
                    <v-avatar size="55">
                        <img alt="avatar" src="/assets/images/profile/user-1.jpg" width="55"/>
                    </v-avatar>
                    <div class="ml-3">
                        <h6 class="text-subtitle-1 mb-n1">
                            <span class="text-success text-caption">{{ $page.props.auth.user.name }}</span>
                        </h6>
                        <span class="text-subtitle-1 textSecondary">{{ $page.props.auth.user.email }}</span>
                    </div>
                </div>
                <v-divider></v-divider>
                <perfect-scrollbar style="height: calc(100vh - 240px); max-height: 240px">
                    <v-list class="py-0 theme-list mt-3" lines="one">
                        <v-list-item class="py-0 px-2 rounded-md custom-text-primary" color="primary">
                            <div class="d-flex gap-3 align-center">
                                <p class="text-subtitle-1 heading custom-title">
                                    <Link :href="route('dashboard')">
                                        My Profile
                                    </Link>
                                </p>
                            </div>
                        </v-list-item>

                        <v-list-item class="py-0 px-2 rounded-md custom-text-primary" color="primary">
                            <div class="d-flex gap-3 align-center">
                                <p class="text-subtitle-1 heading custom-title">
                                    <Link :href="route('logout')" method="post">
                                        Sign out
                                    </Link>
                                </p>
                            </div>
                        </v-list-item>

                    </v-list>
                </perfect-scrollbar>
            </div>
        </v-sheet>
    </v-menu>
</template>
