<script setup>
import {computed, ref} from 'vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import {useToast} from 'vue-toastification';
import Logo from '@/Layouts/full/logo/Logo.vue';

const toast = useToast();

const props = defineProps({
    companies: Array,
    departments: Array,
    designations: Array,
    genderOptions: Array,
});

const inputVisible = ref(false);
const inputVisibleConfirm = ref(false);

const form = useForm({
    first_name: '',
    last_name: '',
    id_no: '',
    email: '',
    phone: '',
    gender: null,
    date_of_birth: null,
    company_id: null,
    department_id: null,
    designation_id: null,
    password: '',
    password_confirmation: '',
});

const filteredDepartments = computed(() => {
    if (!form.company_id) return [];
    return props.departments.filter(d => d.company_id === form.company_id);
});

const filteredDesignations = computed(() => {
    if (!form.company_id) return [];
    return props.designations.filter(d => d.company_id === form.company_id);
});

const onCompanyChange = () => {
    form.department_id = null;
    form.designation_id = null;
};

const submit = () => {
    form.post(route('register'), {
        onSuccess: () => toast.success('Registration successful. Please wait for admin approval.'),
        onError: () => toast.error('Registration failed. Please check your inputs.'),
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="authentication auth-bg">
        <Head title="Register"/>
        <v-container class="pa-3 auth-login">
            <v-row class="h-100vh d-flex justify-center align-center">
                <v-col class="d-flex align-center" lg="10" xl="9">
                    <v-card class="mx-auto w-100" elevation="10" rounded="md">
                        <div class="pa-3">
                            <div class="px-md-12 px-6 pt-md-8 pt-6 pb-md-2 pb-2">
                                <div class="d-flex">
                                    <Logo/>
                                </div>
                                <h2 class="text-32 my-4">Create your account</h2>

                                <div class="d-flex align-center text-center mb-4">
                                    <div class="text-h6 w-100 px-5 font-weight-regular auth-divider position-relative">
                                        <span class="bg-surface px-5 py-3 position-relative textSecondary">Register</span>
                                    </div>
                                </div>
                            </div>

                            <v-form class="px-md-12 px-6 pb-md-8 pb-6" @submit.prevent="submit">
                                <v-row>
                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="form.first_name"
                                            :error-messages="form.errors.first_name"
                                            density="compact"
                                            hide-details="auto"
                                            label="First Name *"
                                            variant="outlined"
                                        />
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="form.last_name"
                                            :error-messages="form.errors.last_name"
                                            density="compact"
                                            hide-details="auto"
                                            label="Last Name"
                                            variant="outlined"
                                        />
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="form.id_no"
                                            :error-messages="form.errors.id_no"
                                            density="compact"
                                            hide-details="auto"
                                            label="ID No"
                                            variant="outlined"
                                        />
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="form.email"
                                            :error-messages="form.errors.email"
                                            density="compact"
                                            hide-details="auto"
                                            label="Email *"
                                            type="email"
                                            variant="outlined"
                                        />
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="form.phone"
                                            :error-messages="form.errors.phone"
                                            density="compact"
                                            hide-details="auto"
                                            label="Phone *"
                                            variant="outlined"
                                        />
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <v-select
                                            v-model="form.gender"
                                            :error-messages="form.errors.gender"
                                            :items="props.genderOptions"
                                            density="compact"
                                            hide-details="auto"
                                            item-title="label"
                                            item-value="value"
                                            label="Gender *"
                                            variant="outlined"
                                        />
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <el-date-picker
                                            v-model="form.date_of_birth"
                                            format="YYYY-MM-DD"
                                            placeholder="Date of Birth"
                                            size="large"
                                            style="width: 100%"
                                            type="date"
                                            value-format="YYYY-MM-DD"
                                        />
                                        <div v-if="form.errors.date_of_birth" class="text-error text-caption mt-1">{{ form.errors.date_of_birth }}</div>
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <v-select
                                            v-model="form.company_id"
                                            :error-messages="form.errors.company_id"
                                            :items="props.companies"
                                            density="compact"
                                            hide-details="auto"
                                            item-title="name"
                                            item-value="id"
                                            label="Company *"
                                            variant="outlined"
                                            @update:model-value="onCompanyChange"
                                        />
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <v-select
                                            v-model="form.department_id"
                                            :disabled="!form.company_id"
                                            :error-messages="form.errors.department_id"
                                            :items="filteredDepartments"
                                            density="compact"
                                            hide-details="auto"
                                            item-title="name"
                                            item-value="id"
                                            label="Department *"
                                            variant="outlined"
                                        />
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <v-select
                                            v-model="form.designation_id"
                                            :disabled="!form.company_id"
                                            :error-messages="form.errors.designation_id"
                                            :items="filteredDesignations"
                                            density="compact"
                                            hide-details="auto"
                                            item-title="title"
                                            item-value="id"
                                            label="Designation"
                                            variant="outlined"
                                        />
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="form.password"
                                            :append-inner-icon="inputVisible ? 'mdi-eye-off' : 'mdi-eye'"
                                            :error-messages="form.errors.password"
                                            :type="inputVisible ? 'text' : 'password'"
                                            density="compact"
                                            hide-details="auto"
                                            label="Password *"
                                            variant="outlined"
                                            @click:append-inner="inputVisible = !inputVisible"
                                        />
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <v-text-field
                                            v-model="form.password_confirmation"
                                            :append-inner-icon="inputVisibleConfirm ? 'mdi-eye-off' : 'mdi-eye'"
                                            :error-messages="form.errors.password_confirmation"
                                            :type="inputVisibleConfirm ? 'text' : 'password'"
                                            density="compact"
                                            hide-details="auto"
                                            label="Confirm Password *"
                                            variant="outlined"
                                            @click:append-inner="inputVisibleConfirm = !inputVisibleConfirm"
                                        />
                                    </v-col>
                                </v-row>

                                <v-btn
                                    :disabled="form.processing"
                                    :loading="form.processing"
                                    block
                                    class="mt-5"
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
                        </div>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>
    </div>
</template>
