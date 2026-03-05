<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();
const form = useForm({
    name: '',
    code: '',
    email: '',
    phone: '',
    address: '',
    website: '',
    office_start_time: '',
    office_end_time: '',
});

const submit = () => {
    form.post(route('companies.store'), {
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
                        <!-- Left Column - Company Details -->
                        <v-col cols="12" md="9">
                            <div class="text-subtitle-2 text-medium-emphasis mb-3">Basic Information</div>
                            <v-row dense>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.name"
                                        :error-messages="form.errors.name"
                                        label="Name *"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.code"
                                        :error-messages="form.errors.code"
                                        label="Code"
                                    />
                                </v-col>
                            </v-row>

                            <v-divider class="my-4"/>
                            <div class="text-subtitle-2 text-medium-emphasis mb-3">Contact Information</div>
                            <v-row dense>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.email"
                                        :error-messages="form.errors.email"
                                        label="Email *"
                                        type="email"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.phone"
                                        :error-messages="form.errors.phone"
                                        label="Phone"
                                        type="tel"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.website"
                                        :error-messages="form.errors.website"
                                        label="Website"
                                        placeholder="https://example.com"
                                        type="url"
                                    />
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
                                        class="pb-3"
                                    />
                                </v-col>
                            </v-row>
                        </v-col>

                        <!-- Right Column - Office Schedule -->
                        <v-col cols="12" md="3">
                            <div class="text-subtitle-2 text-medium-emphasis mb-3">Office Schedule</div>
                            <v-label class="mb-2 font-weight-medium">Start Time</v-label>
                            <el-time-picker
                                v-model="form.office_start_time"
                                format="hh:mm A"
                                value-format="HH:mm"
                                placeholder="Start Time"
                                size="large"
                                style="width: 100%"
                            />
                            <div v-if="form.errors.office_start_time" class="text-error text-caption mt-1">{{ form.errors.office_start_time }}</div>

                            <v-label class="mb-2 mt-4 font-weight-medium">End Time</v-label>
                            <el-time-picker
                                v-model="form.office_end_time"
                                format="hh:mm A"
                                value-format="HH:mm"
                                placeholder="End Time"
                                size="large"
                                style="width: 100%"
                            />
                            <div v-if="form.errors.office_end_time" class="text-error text-caption mt-1">{{ form.errors.office_end_time }}</div>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-divider/>
                <v-card-actions class="pa-4">
                    <v-spacer/>
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
