<script setup>
import {ref} from 'vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import {useToast} from 'vue-toastification';
import Logo from '@/Layouts/full/logo/Logo.vue';

const toast = useToast();

const props = defineProps({
    email: String,
    token: String,
});

const inputVisible = ref(false);
const inputVisibleConfirm = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onSuccess: () => toast.success('Password has been reset successfully.'),
        onError: () => toast.error('Failed to reset password. Please try again.'),
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="authentication auth-bg">
        <Head title="Reset Password"/>
        <v-container class="pa-3 auth-login">
            <v-row class="h-100vh d-flex justify-center align-center">
                <v-col class="d-flex align-center" lg="5" md="12">
                    <v-card class="mx-auto w-100" elevation="10" rounded="md">
                        <div class="pa-3">
                            <div class="px-md-12 px-6 pt-md-8 pt-6 pb-md-2 pb-2">
                                <div class="d-flex">
                                    <Logo/>
                                </div>
                                <h2 class="text-32 my-4">Set your new password</h2>
                            </div>

                            <v-form class="px-md-12 px-6 pb-md-8 pb-6" @submit.prevent="submit">
                                <v-label class="font-weight-semibold pb-2">Email</v-label>
                                <v-text-field
                                    v-model="form.email"
                                    :error-messages="form.errors.email"
                                    class="mb-4"
                                    density="compact"
                                    hide-details="auto"
                                    placeholder="Email"
                                    type="email"
                                    variant="outlined"
                                    readonly
                                />

                                <v-label class="font-weight-semibold pb-2">New Password</v-label>
                                <v-text-field
                                    v-model="form.password"
                                    :append-inner-icon="inputVisible ? 'mdi-eye-off' : 'mdi-eye'"
                                    :error-messages="form.errors.password"
                                    :type="inputVisible ? 'text' : 'password'"
                                    class="mb-4"
                                    density="compact"
                                    hide-details="auto"
                                    placeholder="New password"
                                    variant="outlined"
                                    @click:append-inner="inputVisible = !inputVisible"
                                />

                                <v-label class="font-weight-semibold pb-2">Confirm Password</v-label>
                                <v-text-field
                                    v-model="form.password_confirmation"
                                    :append-inner-icon="inputVisibleConfirm ? 'mdi-eye-off' : 'mdi-eye'"
                                    :error-messages="form.errors.password_confirmation"
                                    :type="inputVisibleConfirm ? 'text' : 'password'"
                                    class="mb-4"
                                    density="compact"
                                    hide-details="auto"
                                    placeholder="Confirm new password"
                                    variant="outlined"
                                    @click:append-inner="inputVisibleConfirm = !inputVisibleConfirm"
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
                                    Reset Password
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
