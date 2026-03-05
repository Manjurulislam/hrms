<script setup>
import {ref} from 'vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import Logo from '@/Layouts/full/logo/Logo.vue';

const props = defineProps({
    companies: Array,
    genderOptions: Array,
});

const inputVisible = ref(false);
const inputVisibleConfirm = ref(false);

const form = useForm({
    first_name: '',
    email: '',
    gender: null,
    company_id: null,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="authentication auth-bg">
        <Head title="Register"/>
        <v-container class="pa-3 auth-login">
            <v-row class="h-100vh d-flex justify-center align-center">
                <v-col class="d-flex align-center" lg="5" md="12">
                    <v-card class="mx-auto w-100" elevation="10" rounded="md">
                        <div class="pa-3">
                            <v-row>
                                <v-col class="px-md-12 px-6 py-md-12 py-6" cols="12">
                                    <div class="d-flex">
                                        <Logo/>
                                    </div>
                                    <h2 class="text-32 my-6">Create your account</h2>

                                    <div class="d-flex align-center text-center mb-6">
                                        <div class="text-h6 w-100 px-5 font-weight-regular auth-divider position-relative">
                                            <span class="bg-surface px-5 py-3 position-relative textSecondary">Register</span>
                                        </div>
                                    </div>

                                    <v-form class="mt-5" @submit.prevent="submit">
                                        <v-label class="font-weight-semibold pb-2">Full Name *</v-label>
                                        <v-text-field
                                            v-model="form.first_name"
                                            :error-messages="form.errors.first_name"
                                            class="mb-4"
                                            density="compact"
                                            hide-details="auto"
                                            placeholder="Full Name"
                                        />

                                        <v-label class="font-weight-semibold pb-2">Email *</v-label>
                                        <v-text-field
                                            v-model="form.email"
                                            :error-messages="form.errors.email"
                                            class="mb-4"
                                            density="compact"
                                            hide-details="auto"
                                            placeholder="Email"
                                            type="email"
                                        />

                                        <v-label class="font-weight-semibold pb-2">Gender</v-label>
                                        <v-select
                                            v-model="form.gender"
                                            :error-messages="form.errors.gender"
                                            :items="props.genderOptions"
                                            class="mb-4"
                                            density="compact"
                                            hide-details="auto"
                                            item-title="label"
                                            item-value="value"
                                            placeholder="Select Gender"
                                        />

                                        <v-label class="font-weight-semibold pb-2">Company *</v-label>
                                        <v-select
                                            v-model="form.company_id"
                                            :error-messages="form.errors.company_id"
                                            :items="props.companies"
                                            class="mb-4"
                                            density="compact"
                                            hide-details="auto"
                                            item-title="name"
                                            item-value="id"
                                            placeholder="Select Company"
                                        />

                                        <v-label class="font-weight-semibold pb-2">Password *</v-label>
                                        <v-text-field
                                            v-model="form.password"
                                            :append-inner-icon="inputVisible ? 'mdi-eye-off' : 'mdi-eye'"
                                            :error-messages="form.errors.password"
                                            :type="inputVisible ? 'text' : 'password'"
                                            class="mb-4"
                                            density="compact"
                                            hide-details="auto"
                                            placeholder="Password"
                                            @click:append-inner="inputVisible = !inputVisible"
                                        />

                                        <v-label class="font-weight-semibold pb-2">Confirm Password *</v-label>
                                        <v-text-field
                                            v-model="form.password_confirmation"
                                            :append-inner-icon="inputVisibleConfirm ? 'mdi-eye-off' : 'mdi-eye'"
                                            :error-messages="form.errors.password_confirmation"
                                            :type="inputVisibleConfirm ? 'text' : 'password'"
                                            density="compact"
                                            hide-details="auto"
                                            placeholder="Confirm Password"
                                            @click:append-inner="inputVisibleConfirm = !inputVisibleConfirm"
                                        />

                                        <v-btn
                                            :disabled="form.processing"
                                            :loading="form.processing"
                                            block
                                            class="mt-6"
                                            color="darkgray"
                                            flat
                                            size="large"
                                            type="submit"
                                        >
                                            Register
                                        </v-btn>

                                        <div class="text-center mt-4">
                                            <span class="textSecondary">Already have an account?</span>
                                            <Link :href="route('login')" class="text-primary font-weight-semibold ml-1">
                                                Sign In
                                            </Link>
                                        </div>
                                    </v-form>
                                </v-col>
                            </v-row>
                        </div>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>
    </div>
</template>
