<script setup>
import {Icon} from "@iconify/vue";

const {schema} = defineProps(['schema'])

</script>

<template>
    <v-row>
        <v-col cols="3">
            <v-card class="overflow-hidden" elevation="10">
                <v-card-item>
                    <div class="d-flex gap-4 flex-wrap">
                        <div class="flex-1-0">
                            <v-sheet class="py-8 px-3 rounded-md text-center primary-gradient">
                                <v-avatar class="rounded-md mb-3" color="primary" size="48">
                                    <Icon height="25" icon="tabler:users-group"/>
                                </v-avatar>
                                <p class="mb-1">Venues</p>
                                <h3 class="text-h3 heading mb-5">0</h3>
                            </v-sheet>
                        </div>
                    </div>
                </v-card-item>
            </v-card>
        </v-col>

        <v-col cols="3">
            <v-card class="overflow-hidden" elevation="10">
                <v-card-item>
                    <div class="d-flex gap-4 flex-wrap">
                        <div class="flex-1-0">
                            <v-sheet class="py-8 px-3 rounded-md text-center warning-gradient">
                                <v-avatar class="rounded-md mb-3" color="warning" size="48">
                                    <Icon height="25" icon="solar:user-linear"/>
                                </v-avatar>
                                <p class="mb-1">Sponsors</p>
                                <h3 class="text-h3 heading mb-5">0</h3>
                            </v-sheet>
                        </div>
                    </div>
                </v-card-item>
            </v-card>
        </v-col>

        <v-col cols="3">
            <v-card class="overflow-hidden" elevation="10">
                <v-card-item>
                    <div class="d-flex gap-4 flex-wrap">
                        <div class="flex-1-0">
                            <v-sheet class="py-8 px-3 rounded-md text-center secondary-gradient">
                                <v-avatar class="rounded-md mb-3" color="secondary" size="48">
                                    <Icon height="25" icon="solar:user-linear"/>
                                </v-avatar>
                                <p class="mb-1">Performers</p>
                                <h3 class="text-h3 heading mb-5">0</h3>
                            </v-sheet>
                        </div>
                    </div>
                </v-card-item>
            </v-card>
        </v-col>

        <v-col cols="3">
            <v-card class="overflow-hidden" elevation="10">
                <v-card-item>
                    <div class="d-flex gap-4 flex-wrap">
                        <div class="flex-1-0">
                            <v-sheet class="py-8 px-3 rounded-md text-center secondary-gradient">
                                <v-avatar class="rounded-md mb-3" color="secondary" size="48">
                                    <Icon height="25" icon="solar:user-linear"/>
                                </v-avatar>
                                <p class="mb-1">Performers</p>
                                <h3 class="text-h3 heading mb-5">0</h3>
                            </v-sheet>
                        </div>
                    </div>
                </v-card-item>
            </v-card>
        </v-col>


    </v-row>
</template>

<style lang="scss" scoped>

</style>
