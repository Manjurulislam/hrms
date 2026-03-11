<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import {onMounted} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import TextInput from '@/Components/common/form/TextInput.vue';

const toast = useToast();
const props = defineProps({
    item: Object
});

let form = useForm({
    name: '',
    code: '',
    email: '',
    phone: '',
    address: '',
    website: '',
    office_start: '',
    office_end: '',
    work_hours: 8,
    half_day_hours: 4,
    late_grace: 15,
    early_grace: 15,
    max_sessions: 10,
    min_session_gap: 2,
    max_breaks: 5,
    auto_close: true,
    auto_close_at: '23:59',
    track_ip: true,
    track_location: true,
});

const submit = () => {
    form.put(route('companies.update', props.item.id), {
        onSuccess: () => toast('Company has been updated successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};

onMounted(() => {
    form = Object.assign(form, props.item);
});
</script>

<template>
    <DefaultLayout>
        <Head title="Edit Company"/>
        <v-card>
            <CardTitle
                :extra-route="{title: 'Back', route: 'companies.index', icon: 'mdi-arrow-left-bold'}"
                icon="mdi-domain"
                title="Edit Company"
            />
            <form @submit.prevent="submit">
                <v-card-text>
                    <v-row>
                        <!-- Left Column -->
                        <v-col cols="12" md="8">
                            <!-- Basic Information -->
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-information-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Basic Information</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-row dense>
                                        <v-col cols="12" md="6">
                                            <TextInput v-model="form.name" :error-messages="form.errors.name" label="Name"/>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <TextInput v-model="form.code" :error-messages="form.errors.code" label="Code"/>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <TextInput v-model="form.email" :error-messages="form.errors.email" label="Email" type="email"/>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <TextInput v-model="form.phone" :error-messages="form.errors.phone" label="Phone" type="tel"/>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <TextInput v-model="form.website" :error-messages="form.errors.website" label="Website" placeholder="https://example.com" type="url"/>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <v-textarea
                                                v-model="form.address"
                                                :error-messages="form.errors.address"
                                                density="compact"
                                                hide-details="auto"
                                                label="Address"
                                                rows="1"
                                                auto-grow
                                                variant="outlined"
                                                class="pb-3"
                                            />
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Attendance Rules -->
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-shield-check-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Attendance Rules</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <div class="text-subtitle-2 text-medium-emphasis mb-3">Grace Periods</div>
                                    <v-row dense>
                                        <v-col cols="12" md="4">
                                            <TextInput v-model.number="form.late_grace" :error-messages="form.errors.late_grace" label="Late Grace (min)" type="number"/>
                                        </v-col>
                                        <v-col cols="12" md="4">
                                            <TextInput v-model.number="form.early_grace" :error-messages="form.errors.early_grace" label="Early Leave Grace (min)" type="number"/>
                                        </v-col>
                                    </v-row>

                                    <v-divider class="my-4"/>
                                    <div class="text-subtitle-2 text-medium-emphasis mb-3">Limits</div>
                                    <v-row dense>
                                        <v-col cols="12" md="4">
                                            <TextInput v-model.number="form.max_sessions" :error-messages="form.errors.max_sessions" label="Max Sessions/Day" type="number"/>
                                        </v-col>
                                        <v-col cols="12" md="4">
                                            <TextInput v-model.number="form.min_session_gap" :error-messages="form.errors.min_session_gap" label="Min Gap (min)" type="number"/>
                                        </v-col>
                                        <v-col cols="12" md="4">
                                            <TextInput v-model.number="form.max_breaks" :error-messages="form.errors.max_breaks" label="Max Breaks/Day" type="number"/>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <!-- Right Column -->
                        <v-col cols="12" md="4">
                            <!-- Office Schedule -->
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-clock-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Office Schedule</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-label class="mb-2 font-weight-medium text-caption">Office Start</v-label>
                                    <el-time-picker
                                        v-model="form.office_start"
                                        format="hh:mm A"
                                        value-format="HH:mm"
                                        placeholder="Start Time"
                                        size="large"
                                        style="width: 100%"
                                    />
                                    <div v-if="form.errors.office_start" class="text-error text-caption mt-1">{{ form.errors.office_start }}</div>

                                    <v-label class="mb-2 mt-4 font-weight-medium text-caption">Office End</v-label>
                                    <el-time-picker
                                        v-model="form.office_end"
                                        format="hh:mm A"
                                        value-format="HH:mm"
                                        placeholder="End Time"
                                        size="large"
                                        style="width: 100%"
                                    />
                                    <div v-if="form.errors.office_end" class="text-error text-caption mt-1">{{ form.errors.office_end }}</div>

                                    <v-divider class="my-4"/>
                                    <TextInput v-model.number="form.work_hours" :error-messages="form.errors.work_hours" label="Working Hours/Day" type="number"/>
                                    <TextInput v-model.number="form.half_day_hours" :error-messages="form.errors.half_day_hours" label="Half Day Hours" type="number"/>

                                    <!-- Office IP (if set) -->
                                    <div v-if="item.office_ip" class="mt-3">
                                        <v-chip size="small" variant="tonal" color="info" prepend-icon="mdi-ip-network">
                                            Office IP: {{ item.office_ip }}
                                        </v-chip>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Auto Close & Tracking -->
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-cog-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Auto Close & Tracking</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-switch v-model="form.auto_close" color="primary" density="compact" hide-details label="Auto Close Sessions"/>
                                    <div v-if="form.auto_close" class="mt-3">
                                        <v-label class="mb-2 font-weight-medium text-caption">Auto Close Time</v-label>
                                        <el-time-picker
                                            v-model="form.auto_close_at"
                                            format="hh:mm A"
                                            value-format="HH:mm"
                                            placeholder="Auto Close Time"
                                            size="large"
                                            style="width: 100%"
                                        />
                                    </div>
                                    <v-divider class="my-4"/>
                                    <v-switch v-model="form.track_ip" color="primary" density="compact" hide-details label="Track IP Address" class="mb-2"/>
                                    <v-switch v-model="form.track_location" color="primary" density="compact" hide-details label="Track Location"/>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-divider/>
                <v-card-actions class="justify-center pa-4">
                    <v-btn
                        :loading="form.processing"
                        class="text-none"
                        color="primary"
                        type="submit"
                        variant="flat"
                    >
                        Update
                    </v-btn>
                </v-card-actions>
            </form>
        </v-card>
    </DefaultLayout>
</template>
