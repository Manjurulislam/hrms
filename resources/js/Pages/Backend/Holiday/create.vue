<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();
const props = defineProps({
    companies: Array
});

const form = useForm({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    company_id: null,
    status: true,
});

const submit = () => {
    form.post(route('holidays.store'), {
        onSuccess: () => toast('Holiday has been added successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Holiday"/>
        <v-card>
            <CardTitle
                :extra-route="{title: 'Back', route: 'holidays.index', icon: 'mdi-arrow-left-bold'}"
                icon="mdi-calendar-star"
                title="Create Holiday"
            />
            <form @submit.prevent="submit">
                <v-card-text>
                    <v-row>
                        <!-- Left Column -->
                        <v-col cols="12" md="8">
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-information-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Holiday Details</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-row dense>
                                        <v-col cols="12">
                                            <TextInput
                                                v-model="form.name"
                                                :error-messages="form.errors.name"
                                                label="Name"
                                            />
                                        </v-col>
                                        <v-col cols="12">
                                            <v-textarea
                                                v-model="form.description"
                                                :error-messages="form.errors.description"
                                                density="compact"
                                                label="Description"
                                                placeholder="Enter holiday description..."
                                                rows="4"
                                                variant="outlined"
                                            />
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <!-- Right Column -->
                        <v-col cols="12" md="4">
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-domain</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Company</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-select
                                        v-model="form.company_id"
                                        :error-messages="form.errors.company_id"
                                        :items="companies"
                                        clearable
                                        density="compact"
                                        item-title="name"
                                        item-value="id"
                                        label="Company"
                                        variant="outlined"
                                    />
                                </v-card-text>
                            </v-card>

                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-calendar-range</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Date Range</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-label class="mb-2 font-weight-medium text-caption">Start Date</v-label>
                                    <el-date-picker
                                        v-model="form.start_date"
                                        format="YYYY-MM-DD"
                                        placeholder="Start date"
                                        size="large"
                                        style="width: 100%"
                                        type="date"
                                        value-format="YYYY-MM-DD"
                                    />
                                    <div v-if="form.errors.start_date" class="text-error text-caption mt-1">{{ form.errors.start_date }}</div>

                                    <v-label class="mb-2 mt-4 font-weight-medium text-caption">End Date</v-label>
                                    <el-date-picker
                                        v-model="form.end_date"
                                        format="YYYY-MM-DD"
                                        placeholder="End date"
                                        size="large"
                                        style="width: 100%"
                                        type="date"
                                        value-format="YYYY-MM-DD"
                                    />
                                    <div v-if="form.errors.end_date" class="text-error text-caption mt-1">{{ form.errors.end_date }}</div>
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
