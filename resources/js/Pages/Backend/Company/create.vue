<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {computed} from "vue";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();
defineProps({
    office_ip: String,
});
const form = useForm({
    name: '',
    code: '',
    email: '',
    phone: '',
    address: '',
    website: '',
    office_start: '',
    office_end: '',
    work_hours: 9,
    half_day_hours: 5,
    late_grace: 30,
    early_grace: 15,
    track_ip: true,
    track_location: true,
});

// Working hours/day = office end − office start (rounded to whole hours)
const workHours = computed(() => {
    const {office_start: start, office_end: end} = form;
    if (!start || !end) return 0;
    const [sh, sm] = start.split(':').map(Number);
    const [eh, em] = end.split(':').map(Number);
    let minutes = (eh * 60 + em) - (sh * 60 + sm);
    if (minutes < 0) minutes += 24 * 60; // overnight shift
    return Math.round(minutes / 60);
});

const submit = () => {
    form.transform((data) => ({...data, work_hours: workHours.value}))
        .post(route('companies.store'), {
            onSuccess: () => toast('Company has been added successfully.'),
            onError: () => toast.error('Something is wrong. Please try again.')
        });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Company"/>
        <v-card>
            <CardTitle
                :extra-route="{title: 'Back', route: 'companies.index', icon: 'mdi-arrow-left-bold'}"
                icon="mdi-domain"
                title="Create Company"
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
                                            <TextInput v-model="form.email" :error-messages="form.errors.email" label="Email" type="email"/>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <TextInput v-model="form.phone" :error-messages="form.errors.phone" label="Phone" type="tel"/>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <TextInput v-model="form.code" :error-messages="form.errors.code" label="Code"/>
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
                                        <v-col cols="12" md="6">
                                            <TextInput v-model="form.website" :error-messages="form.errors.website" label="Website" placeholder="https://example.com" type="url"/>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Office Schedule -->
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-clock-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Office Schedule</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-row dense>
                                        <v-col cols="12" md="6">
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
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <v-label class="mb-2 font-weight-medium text-caption">Office End</v-label>
                                            <el-time-picker
                                                v-model="form.office_end"
                                                format="hh:mm A"
                                                value-format="HH:mm"
                                                placeholder="End Time"
                                                size="large"
                                                style="width: 100%"
                                            />
                                            <div v-if="form.errors.office_end" class="text-error text-caption mt-1">{{ form.errors.office_end }}</div>
                                        </v-col>
                                    </v-row>

                                    <v-divider class="my-4"/>
                                    <v-row dense>
                                        <v-col cols="12" md="6">
                                            <v-text-field
                                                :model-value="workHours"
                                                :error-messages="form.errors.work_hours"
                                                label="Working Hours/Day"
                                                type="number"
                                                readonly
                                                density="compact"
                                                hide-details="auto"
                                                class="pb-3"
                                                hint="Auto-calculated from office start & end"
                                                persistent-hint
                                            />
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <TextInput v-model.number="form.half_day_hours" :error-messages="form.errors.half_day_hours" label="Half Day Hours" type="number"/>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                        </v-col>

                        <!-- Right Column -->
                        <v-col cols="12" md="4">
                            <!-- Attendance Rules -->
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-shield-check-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Attendance Rules</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <div class="text-subtitle-2 text-medium-emphasis mb-3">Grace Periods</div>
                                    <v-row dense>
                                        <v-col cols="12" md="6">
                                            <TextInput v-model.number="form.late_grace" :error-messages="form.errors.late_grace" label="Late Grace (min)" type="number"/>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <TextInput v-model.number="form.early_grace" :error-messages="form.errors.early_grace" label="Early Leave Grace (min)" type="number"/>
                                        </v-col>
                                    </v-row>
                                    <div class="text-caption text-medium-emphasis">
                                        Late grace: 0–60 min. Arriving later than the grace period is marked
                                        <strong>Late</strong>.
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Tracking -->
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-cog-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Tracking</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-switch v-model="form.track_ip" color="primary" density="compact" hide-details label="Track IP Address" class="mb-2"/>
                                    <v-switch v-model="form.track_location" color="primary" density="compact" hide-details label="Track Location"/>
                                    <div v-if="office_ip" class="mt-4">
                                        <v-label class="mb-2 font-weight-medium text-caption">Office IP</v-label>
                                        <div>
                                            <v-chip size="small" variant="tonal" color="info" prepend-icon="mdi-ip-network">
                                                {{ office_ip }}
                                            </v-chip>
                                        </div>
                                    </div>
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
                        Submit
                    </v-btn>
                </v-card-actions>
            </form>
        </v-card>
    </DefaultLayout>
</template>
