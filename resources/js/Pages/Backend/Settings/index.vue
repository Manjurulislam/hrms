<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import {ref} from 'vue';
import {useToast} from 'vue-toastification';
import axios from 'axios';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import TextInput from '@/Components/common/form/TextInput.vue';

const toast = useToast();
const props = defineProps({
    settings: Object,
    timezones: Array,
    logoUrl: String,
});

const logoPreview = ref(props.logoUrl);
const logoUploading = ref(false);

const handleLogoUpload = async (options) => {
    const formData = new FormData();
    formData.append('logo', options.file);

    logoUploading.value = true;
    try {
        const {data} = await axios.post(route('settings.logo'), formData, {
            headers: {'Content-Type': 'multipart/form-data'},
        });
        logoPreview.value = data.logoUrl;
        toast.success(data.message);
    } catch (error) {
        const msg = error.response?.data?.message || error.response?.data?.errors?.logo?.[0] || 'Failed to upload logo.';
        toast.error(msg);
    } finally {
        logoUploading.value = false;
    }
};

const beforeLogoUpload = (file) => {
    const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
    if (!validTypes.includes(file.type)) {
        toast.error('Logo must be JPG, PNG, WebP or SVG.');
        return false;
    }
    if (file.size > 2 * 1024 * 1024) {
        toast.error('Logo must be less than 2MB.');
        return false;
    }
    return true;
};

const removeLogo = async () => {
    logoUploading.value = true;
    try {
        const {data} = await axios.delete(route('settings.logo.remove'));
        logoPreview.value = null;
        toast.success(data.message);
    } catch {
        toast.error('Failed to remove logo.');
    } finally {
        logoUploading.value = false;
    }
};

const activeSection = ref('general');

const sections = [
    {value: 'general', label: 'General', icon: 'mdi-cog-outline'},
    {value: 'notification', label: 'Notifications', icon: 'mdi-bell-outline'},
];

const dateFormats = [
    {title: '08 Mar 2026 (d M Y)', value: 'd M Y'},
    {title: '2026-03-08 (Y-m-d)', value: 'Y-m-d'},
    {title: '03/08/2026 (m/d/Y)', value: 'm/d/Y'},
    {title: '08/03/2026 (d/m/Y)', value: 'd/m/Y'},
    {title: 'Mar 08, 2026 (M d, Y)', value: 'M d, Y'},
];

const timeFormats = [
    {title: '02:30 PM (h:i A)', value: 'h:i A'},
    {title: '14:30 (H:i)', value: 'H:i'},
];

const buildFormData = (group) => {
    const data = {};
    Object.keys(props.settings[group]).forEach(key => {
        const meta = props.settings[group][key];
        if (meta.type === 'boolean') {
            data[key] = meta.value === '1' || meta.value === true;
        } else if (meta.type === 'integer') {
            data[key] = parseInt(meta.value) || 0;
        } else {
            data[key] = meta.value;
        }
    });
    return data;
};

const generalForm = useForm(buildFormData('general'));
const notificationForm = useForm(buildFormData('notification'));

const submitGroup = (group, form) => {
    form.put(route('settings.update', group), {
        preserveScroll: true,
        onSuccess: () => toast(ucfirst(group) + ' settings updated successfully.'),
        onError: () => toast.error('Failed to update settings.'),
    });
};

const ucfirst = (str) => str.charAt(0).toUpperCase() + str.slice(1);
</script>

<template>
    <DefaultLayout>
        <Head title="Settings"/>
        <v-row>
            <!-- Sidebar Navigation -->
            <v-col cols="12" md="3">
                <v-card>
                    <v-list density="compact" nav>
                        <v-list-item
                            v-for="section in sections"
                            :key="section.value"
                            :active="activeSection === section.value"
                            :prepend-icon="section.icon"
                            :title="section.label"
                            color="primary"
                            @click="activeSection = section.value"
                        />
                    </v-list>
                </v-card>
            </v-col>

            <!-- Content Area -->
            <v-col cols="12" md="9">
                <!-- General Settings -->
                <template v-if="activeSection === 'general'">
                    <!-- Logo Upload -->
                    <v-card class="mb-4">
                        <CardTitle icon="mdi-image-outline" title="Application Logo"/>
                        <v-card-text class="d-flex align-center ga-4">
                            <el-upload
                                class="logo-uploader"
                                :show-file-list="false"
                                :http-request="handleLogoUpload"
                                :before-upload="beforeLogoUpload"
                                accept="image/jpeg,image/png,image/webp,image/svg+xml"
                            >
                                <v-avatar size="80" rounded="lg" color="grey-lighten-3" style="cursor: pointer;">
                                    <v-img v-if="logoPreview" :src="logoPreview" cover/>
                                    <v-icon v-else size="40" color="grey">mdi-image-plus</v-icon>
                                    <v-progress-circular
                                        v-if="logoUploading"
                                        class="position-absolute"
                                        indeterminate
                                        size="80"
                                        width="3"
                                        color="primary"
                                    />
                                </v-avatar>
                            </el-upload>
                            <div>
                                <div class="text-subtitle-2">Upload Logo</div>
                                <div class="text-caption text-medium-emphasis">JPG, PNG, WebP or SVG. Max 2MB.</div>
                                <v-btn
                                    v-if="logoPreview"
                                    class="mt-2"
                                    size="small"
                                    variant="tonal"
                                    color="error"
                                    prepend-icon="mdi-delete-outline"
                                    :loading="logoUploading"
                                    @click="removeLogo"
                                >
                                    Remove
                                </v-btn>
                            </div>
                        </v-card-text>
                    </v-card>

                    <v-card>
                        <CardTitle icon="mdi-cog-outline" title="General Settings"/>
                        <form @submit.prevent="submitGroup('general', generalForm)">
                            <v-card-text>
                                <div class="text-subtitle-2 text-medium-emphasis mb-3">Application</div>
                                <v-row dense>
                                    <v-col cols="12" md="6">
                                        <TextInput
                                            v-model="generalForm.app_name"
                                            :error-messages="generalForm.errors.app_name"
                                            label="Application Name"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-autocomplete
                                            v-model="generalForm.timezone"
                                            :items="timezones"
                                            density="compact"
                                            hide-details="auto"
                                            label="Timezone"
                                            class="pb-3"
                                        />
                                    </v-col>
                                </v-row>

                                <v-divider class="my-4"/>
                                <div class="text-subtitle-2 text-medium-emphasis mb-3">Format</div>
                                <v-row dense>
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-model="generalForm.date_format"
                                            :items="dateFormats"
                                            density="compact"
                                            hide-details="auto"
                                            label="Date Format"
                                            class="pb-3"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-model="generalForm.time_format"
                                            :items="timeFormats"
                                            density="compact"
                                            hide-details="auto"
                                            label="Time Format"
                                            class="pb-3"
                                        />
                                    </v-col>
                                </v-row>

                                <v-divider class="my-4"/>
                                <div class="text-subtitle-2 text-medium-emphasis mb-3">Currency</div>
                                <v-row dense>
                                    <v-col cols="12" md="6">
                                        <TextInput
                                            v-model="generalForm.currency"
                                            :error-messages="generalForm.errors.currency"
                                            label="Currency Code"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <TextInput
                                            v-model="generalForm.currency_symbol"
                                            :error-messages="generalForm.errors.currency_symbol"
                                            label="Currency Symbol"
                                        />
                                    </v-col>
                                </v-row>
                            </v-card-text>
                            <v-divider/>
                            <v-card-actions class="justify-center pa-4">
                                <v-btn
                                    :loading="generalForm.processing"
                                    class="text-none"
                                    color="primary"
                                    type="submit"
                                    variant="flat"
                                >
                                    Save Changes
                                </v-btn>
                            </v-card-actions>
                        </form>
                    </v-card>
                </template>

                <!-- Notification Settings -->
                <template v-if="activeSection === 'notification'">
                    <v-card>
                        <CardTitle icon="mdi-bell-outline" title="Notification Settings"/>
                        <form @submit.prevent="submitGroup('notification', notificationForm)">
                            <v-card-text>
                                <div class="text-subtitle-2 text-medium-emphasis mb-3">Attendance Notifications</div>
                                <v-row dense>
                                    <v-col cols="12" md="6">
                                        <v-switch
                                            v-model="notificationForm.send_late_notification"
                                            color="primary"
                                            density="compact"
                                            hide-details
                                            label="Late Arrival Notification"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-switch
                                            v-model="notificationForm.send_absence_notification"
                                            color="primary"
                                            density="compact"
                                            hide-details
                                            label="Absence Notification"
                                        />
                                    </v-col>
                                </v-row>

                                <v-divider class="my-4"/>
                                <div class="text-subtitle-2 text-medium-emphasis mb-3">Leave Notifications</div>
                                <v-row dense>
                                    <v-col cols="12" md="4">
                                        <v-switch
                                            v-model="notificationForm.send_leave_notification"
                                            color="primary"
                                            density="compact"
                                            hide-details
                                            label="Leave Request Notification"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-switch
                                            v-model="notificationForm.notify_on_approval"
                                            color="primary"
                                            density="compact"
                                            hide-details
                                            label="Approval Notification"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-switch
                                            v-model="notificationForm.notify_on_rejection"
                                            color="primary"
                                            density="compact"
                                            hide-details
                                            label="Rejection Notification"
                                        />
                                    </v-col>
                                </v-row>
                            </v-card-text>
                            <v-divider/>
                            <v-card-actions class="justify-center pa-4">
                                <v-btn
                                    :loading="notificationForm.processing"
                                    class="text-none"
                                    color="primary"
                                    type="submit"
                                    variant="flat"
                                >
                                    Save Changes
                                </v-btn>
                            </v-card-actions>
                        </form>
                    </v-card>
                </template>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
