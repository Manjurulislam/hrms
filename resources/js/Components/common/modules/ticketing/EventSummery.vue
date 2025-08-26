<script setup>

import EventTotalCard from "@/Components/common/modules/ticketing/EventTotalCard.vue";
</script>

<template>
    <v-card flat>
        <v-row>
            <v-col cols="4">
                <v-card class="bg-lvDark overflow-hidden" elevation="10">
                    <v-card-item class="pb-0">
                        <v-row>
                            <v-col cols="12" md="7">
                                <div class="d-flex gap-3 align-center">
                                    <v-avatar class="rounded-md bg-surface" size="48">
                                        <Icon class="text-textPrimary opacity-80" height="25"
                                              icon="solar:course-up-outline"/>
                                    </v-avatar>
                                    <div class="text-h4">Events</div>
                                </div>
                                <div class="mt-xl-16 mt-lg-8 mt-6">
                                    <div class="d-flex justify-space-between pr-7">
                                        <div class="py-0">
                                            <p class="opacity-80 mb-1">Total</p>
                                            <h3 class="text-xl-h3 text-lg-h5">0</h3>
                                        </div>
                                        <v-divider class="opacity-20 h-55" vertical></v-divider>
                                        <div class="py-0">
                                            <p class="opacity-80 mb-1">Upcoming</p>
                                            <h3 class="text-xl-h3 text-lg-h5">0</h3>
                                        </div>
                                    </div>
                                </div>
                            </v-col>
                            <v-col class="pb-0 text-md-end text-center" cols="12" md="5">
                                <img alt="matdash-img" class="welcome-img"
                                     src="/assets/images/backgrounds/welcome.webp"/>
                            </v-col>
                        </v-row>
                    </v-card-item>
                </v-card>
            </v-col>
            <v-col cols="8">
                <event-total-card/>
            </v-col>
        </v-row>
    </v-card>
</template>

<style scoped>

</style>
