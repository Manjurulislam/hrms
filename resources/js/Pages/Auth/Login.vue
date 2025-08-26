<script setup>
import BoxedLoginForm from '@/Components/auth/BoxedLoginForm.vue';
import {Head} from '@inertiajs/vue3';
import Logo from "@/Layouts/full/logo/Logo.vue";
</script>

<template>
    <div class="authentication auth-bg">
        <Head title="Log in"/>
        <v-container class="pa-3 auth-login">
            <v-row class="h-100vh d-flex justify-center align-center">
                <v-col class="d-flex align-center" lg="5" md="12">
                    <v-card class="mx-auto" elevation="10" rounded="md">
                        <div class="pa-3">
                            <v-row>
                                <v-col class="px-md-12 px-6 py-md-12 py-6" cols="12" md="12">
                                    <div class="d-flex">
                                        <Logo/>
                                    </div>
                                    <h2 class="text-32 my-6">Let's get you signed in</h2>
                                    <BoxedLoginForm/>
                                </v-col>
                            </v-row>
                        </div>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>
    </div>
</template>
