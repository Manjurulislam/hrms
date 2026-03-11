<script setup>
import {Head, useForm, router} from '@inertiajs/vue3';
import {onMounted, ref} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import TextInput from '@/Components/common/form/TextInput.vue';
import axios from 'axios';

const toast = useToast();
const props = defineProps({
    user: Object,
    employee: Object,
    avatarUrl: String,
    genderOptions: Array,
});

const avatarPreview = ref(props.avatarUrl);
const avatarUploading = ref(false);

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

const handleAvatarUpload = async (options) => {
    const formData = new FormData();
    formData.append('avatar', options.file);

    avatarUploading.value = true;
    try {
        const {data} = await axios.post(route('profile.avatar'), formData, {
            headers: {'Content-Type': 'multipart/form-data'},
        });
        avatarPreview.value = data.avatarUrl;
        toast.success(data.message);
    } catch (error) {
        const msg = error.response?.data?.message || error.response?.data?.errors?.avatar?.[0] || 'Failed to upload photo.';
        toast.error(msg);
    } finally {
        avatarUploading.value = false;
    }
};

const beforeAvatarUpload = (file) => {
    const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        toast.error('Photo must be JPG, PNG or WebP.');
        return false;
    }
    if (file.size > 2 * 1024 * 1024) {
        toast.error('Photo must be less than 2MB.');
        return false;
    }
    return true;
};

const removeAvatar = async () => {
    avatarUploading.value = true;
    try {
        const {data} = await axios.delete(route('profile.avatar.remove'));
        avatarPreview.value = null;
        toast.success(data.message);
    } catch {
        toast.error('Failed to remove photo.');
    } finally {
        avatarUploading.value = false;
    }
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
            <!-- Avatar & Profile Info -->
            <v-col cols="12" md="7">
                <!-- Avatar Upload -->
                <v-card class="mb-4" v-if="employee">
                    <v-card-text class="d-flex align-center ga-4">
                        <el-upload
                            class="avatar-uploader"
                            :show-file-list="false"
                            :http-request="handleAvatarUpload"
                            :before-upload="beforeAvatarUpload"
                            accept="image/jpeg,image/png,image/webp"
                        >
                            <v-avatar size="80" color="primary" variant="tonal" style="cursor: pointer;">
                                <v-img v-if="avatarPreview" :src="avatarPreview" cover/>
                                <span v-else class="text-h5 text-uppercase">
                                    {{ employee?.first_name?.charAt(0) }}{{ employee?.last_name?.charAt(0) }}
                                </span>
                                <v-progress-circular
                                    v-if="avatarUploading"
                                    class="position-absolute"
                                    indeterminate
                                    size="80"
                                    width="3"
                                    color="primary"
                                />
                            </v-avatar>
                        </el-upload>
                        <div>
                            <h6 class="text-h6 font-weight-bold">{{ employee?.first_name }} {{ employee?.last_name }}</h6>
                            <span class="text-caption text-medium-emphasis">{{ user?.email }}</span>
                            <div class="d-flex ga-2 mt-2">
                                <v-btn
                                    v-if="avatarPreview"
                                    size="small"
                                    variant="tonal"
                                    color="error"
                                    prepend-icon="mdi-delete-outline"
                                    :loading="avatarUploading"
                                    @click="removeAvatar"
                                >
                                    Remove
                                </v-btn>
                            </div>

                        </div>
                    </v-card-text>
                </v-card>

                <!-- Profile Form -->
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
                        <v-card-actions class="justify-center pa-4">
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
                        <v-card-actions class="justify-center pa-4">
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
