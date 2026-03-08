<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import {onMounted} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import TextInput from '@/Components/common/form/TextInput.vue';

const toast = useToast();
const props = defineProps({
    user: Object,
    employee: Object,
    genderOptions: Array,
});

const profileForm = useForm({
    name: '',
    email: '',
    first_name: '',
    last_name: '',
    phone: '',
    gender: null,
    date_of_birth: null,
    address: '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    profileForm.put(route('profile.update'), {
        onSuccess: () => toast('Profile updated successfully.'),
        onError: () => toast.error('Please check the form and try again.'),
    });
};

const changePassword = () => {
    passwordForm.put(route('profile.password'), {
        onSuccess: () => {
            toast('Password changed successfully.');
            passwordForm.reset();
        },
        onError: () => toast.error('Please check the form and try again.'),
    });
};

onMounted(() => {
    profileForm.name = props.user?.name;
    profileForm.email = props.user?.email;
    if (props.employee) {
        profileForm.first_name = props.employee.first_name;
        profileForm.last_name = props.employee.last_name;
        profileForm.phone = props.employee.phone;
        profileForm.gender = props.employee.gender;
        profileForm.date_of_birth = props.employee.date_of_birth;
        profileForm.address = props.employee.address;
    }
});
</script>

<template>
    <DefaultLayout>
        <Head title="My Profile"/>
        <v-row>
            <!-- Profile Info -->
            <v-col cols="12" md="7">
                <v-card>
                    <CardTitle icon="mdi-account-edit-outline" title="Profile Information"/>
                    <form @submit.prevent="updateProfile">
                        <v-card-text>
                            <v-row dense>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="profileForm.name"
                                        :error-messages="profileForm.errors.name"
                                        label="Name *"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="profileForm.email"
                                        :error-messages="profileForm.errors.email"
                                        label="Email *"
                                        type="email"
                                    />
                                </v-col>
                                <template v-if="employee">
                                    <v-col cols="12" md="6">
                                        <TextInput
                                            v-model="profileForm.first_name"
                                            :error-messages="profileForm.errors.first_name"
                                            label="First Name *"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <TextInput
                                            v-model="profileForm.last_name"
                                            :error-messages="profileForm.errors.last_name"
                                            label="Last Name"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <TextInput
                                            v-model="profileForm.phone"
                                            :error-messages="profileForm.errors.phone"
                                            label="Phone"
                                            type="tel"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-model="profileForm.gender"
                                            :error-messages="profileForm.errors.gender"
                                            :items="genderOptions"
                                            clearable
                                            density="compact"
                                            hide-details="auto"
                                            item-title="label"
                                            item-value="value"
                                            label="Gender"
                                            variant="outlined"
                                            class="pb-3"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-label class="mb-2 font-weight-medium text-caption">Date of Birth</v-label>
                                        <el-date-picker
                                            v-model="profileForm.date_of_birth"
                                            format="YYYY-MM-DD"
                                            placeholder="Select date"
                                            size="large"
                                            style="width: 100%"
                                            type="date"
                                            value-format="YYYY-MM-DD"
                                        />
                                        <div v-if="profileForm.errors.date_of_birth" class="text-error text-caption mt-1">
                                            {{ profileForm.errors.date_of_birth }}
                                        </div>
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-textarea
                                            v-model="profileForm.address"
                                            :error-messages="profileForm.errors.address"
                                            density="compact"
                                            hide-details="auto"
                                            label="Address"
                                            rows="1"
                                            auto-grow
                                            variant="outlined"
                                            class="pb-3"
                                        />
                                    </v-col>
                                </template>
                            </v-row>
                        </v-card-text>
                        <v-divider/>
                        <v-card-actions class="pa-4">
                            <v-spacer/>
                            <v-btn
                                :loading="profileForm.processing"
                                class="text-none"
                                color="primary"
                                type="submit"
                                variant="flat"
                            >
                                Update Profile
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>

            <!-- Change Password -->
            <v-col cols="12" md="5">
                <v-card>
                    <CardTitle icon="mdi-lock-outline" title="Change Password"/>
                    <form @submit.prevent="changePassword">
                        <v-card-text>
                            <v-row dense>
                                <v-col cols="12">
                                    <TextInput
                                        v-model="passwordForm.current_password"
                                        :error-messages="passwordForm.errors.current_password"
                                        label="Current Password *"
                                        type="password"
                                    />
                                </v-col>
                                <v-col cols="12">
                                    <TextInput
                                        v-model="passwordForm.password"
                                        :error-messages="passwordForm.errors.password"
                                        label="New Password *"
                                        type="password"
                                    />
                                </v-col>
                                <v-col cols="12">
                                    <TextInput
                                        v-model="passwordForm.password_confirmation"
                                        :error-messages="passwordForm.errors.password_confirmation"
                                        label="Confirm Password *"
                                        type="password"
                                    />
                                </v-col>
                            </v-row>
                        </v-card-text>
                        <v-divider/>
                        <v-card-actions class="pa-4">
                            <v-spacer/>
                            <v-btn
                                :loading="passwordForm.processing"
                                class="text-none"
                                color="primary"
                                type="submit"
                                variant="flat"
                            >
                                Change Password
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
