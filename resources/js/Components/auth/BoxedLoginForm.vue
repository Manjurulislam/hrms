<script setup>
import {ref} from 'vue';
import {useForm} from "@inertiajs/vue3";

const isSubmitting = ref(false);
const inputVisible = ref(false);
const form = useForm({
    email: '',
    password: '',
    remember: false,
});
const submit = () => {
    form.post(route('login.post'), {
        onFinish: () => {
            form.reset('password')
        },
    });
};
</script>

<template>
    <div class="d-flex align-center text-center mb-6">
        <div class="text-h6 w-100 px-5 font-weight-regular auth-divider position-relative">
            <span class="bg-surface px-5 py-3 position-relative textSecondary">Backend</span>
        </div>
    </div>
    <v-form class="mt-5" @submit.prevent="submit">
        <v-label class="font-weight-semibold pb-2 ">Email</v-label>
        <VTextField
            v-model="form.email"
            :error-messages="form.errors.email"
            class="mb-8"
            density="compact"
            hide-details="auto"
            placeholder="Email"
            required/>
        <v-label class="font-weight-semibold pb-2 ">Password</v-label>
        <VTextField
            v-model="form.password"
            :append-inner-icon="inputVisible ? 'mdi-eye-off' : 'mdi-eye'"
            :error-messages="form.errors.password"
            :type="inputVisible ? 'text' : 'password'"
            class="pwdInput"
            density="compact"
            hide-details="auto"
            placeholder="Password"
            required
            @click:append-inner="inputVisible = !inputVisible"
        />
        <div class="d-flex flex-wrap align-center my-3 ml-n2">
            <v-checkbox
                v-model:checked="form.remember"
                class="pe-2"
                color="primary"
                hide-details
                required
            >
                <template v-slot:label class="font-weight-medium">Keep me logged in</template>
            </v-checkbox>
        </div>
        <v-btn :loading="isSubmitting" block color="darkgray" flat size="large" type="submit">Sign In</v-btn>
    </v-form>
</template>
