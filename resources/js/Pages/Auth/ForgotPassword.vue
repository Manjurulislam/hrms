<script setup>
import {Head, Link, useForm} from '@inertiajs/vue3';
import {useToast} from 'vue-toastification';
import Logo from '@/Layouts/full/logo/Logo.vue';

const toast = useToast();

const props = defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'), {
        onSuccess: () => toast.success('Password reset link has been sent to your email.'),
        onError: () => toast.error('Failed to send reset link. Please try again.'),
    });
};
</script>

<template>
    <div class="authentication auth-bg">
        <Head title="Forgot Password"/>
        <v-container class="pa-3 auth-login">
            <v-row class="h-100vh d-flex justify-center align-center">
                <v-col class="d-flex align-center" lg="5" md="12">
                    <v-card class="mx-auto w-100" elevation="10" rounded="md">
                        <div class="pa-3">
                            <div class="px-md-12 px-6 pt-md-8 pt-6 pb-md-2 pb-2">
                                <div class="d-flex">
                                    <Logo/>
                                </div>
                                <h2 class="text-32 my-4">Forgot your password?</h2>
                                <p class="textSecondary mb-4">
                                    Enter your email address and we'll send you a link to reset your password.
                                </p>

                                <v-alert v-if="status" class="mb-4" color="success" density="compact" type="success" variant="tonal">
                                    {{ status }}
                                </v-alert>
                            </div>

                            <v-form class="px-md-12 px-6 pb-md-8 pb-6" @submit.prevent="submit">
                                <v-label class="font-weight-semibold pb-2">Email</v-label>
                                <v-text-field
                                    v-model="form.email"
                                    :error-messages="form.errors.email"
                                    class="mb-4"
                                    density="compact"
                                    hide-details="auto"
                                    placeholder="Enter your email"
                                    type="email"
                                    variant="outlined"
                                />

                                <v-btn
                                    :disabled="form.processing"
                                    :loading="form.processing"
                                    block
                                    class="mt-2"
                                    color="darkgray"
                                    flat
                                    size="large"
                                    type="submit"
                                >
                                    Send Reset Link
                                </v-btn>

                                <div class="text-center mt-4">
                                    <Link :href="route('login')" class="text-primary font-weight-semibold">
                                        Back to Sign In
                                    </Link>
                                </div>
                            </v-form>
                        </div>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>
    </div>
</template>
