<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import {computed} from "vue";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();
const props = defineProps({
    companies: Array,
    workflows: Array,
});

const form = useForm({
    name: '',
    max_per_year: '',
    company_id: null,
    approval_workflow_id: null,
    status: true,
});

const filteredWorkflows = computed(() => {
    if (!form.company_id) return [];
    return props.workflows.filter(w => w.company_id === form.company_id);
});

const onCompanyChange = () => {
    form.approval_workflow_id = null;
};

const submit = () => {
    form.post(route('leave-types.store'), {
        onSuccess: () => toast('Leave type has been added successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.'),
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Leave Type"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back', route: 'leave-types.index', icon: 'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        title="Create Leave Type"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text>
                            <!-- Basic Information -->
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-information-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Basic Information</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-row dense>
                                        <v-col cols="12" md="4">
                                            <TextInput
                                                v-model="form.name"
                                                :error-messages="form.errors.name"
                                                label="Leave Type Name"
                                                placeholder="e.g., Annual Leave, Sick Leave"
                                            />
                                        </v-col>
                                        <v-col cols="12" md="4">
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
                                                @update:model-value="onCompanyChange"
                                            />
                                        </v-col>
                                        <v-col cols="12" md="4">
                                            <v-text-field
                                                v-model="form.max_per_year"
                                                :error-messages="form.errors.max_per_year"
                                                density="compact"
                                                label="Max Days Per Year"
                                                min="1"
                                                max="365"
                                                placeholder="e.g., 21"
                                                type="number"
                                                variant="outlined"
                                            />
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Approval Settings -->
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-shield-check-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Approval Settings</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-row dense>
                                        <v-col cols="12" md="4">
                                            <v-select
                                                v-model="form.approval_workflow_id"
                                                :error-messages="form.errors.approval_workflow_id"
                                                :items="filteredWorkflows"
                                                :disabled="!form.company_id"
                                                :hint="!form.company_id ? 'Select a company first' : ''"
                                                :persistent-hint="!form.company_id"
                                                clearable
                                                density="compact"
                                                item-title="name"
                                                item-value="id"
                                                label="Approval Workflow"
                                                variant="outlined"
                                            />
                                        </v-col>
                                        <v-col cols="12" md="4">
                                            <v-alert
                                                density="compact"
                                                type="info"
                                                variant="tonal"
                                                class="text-body-2"
                                            >
                                                If no workflow is selected, leaves will use single-step direct manager approval.
                                            </v-alert>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>
                        </v-card-text>

                        <v-divider/>
                        <v-card-actions>
                            <v-btn
                                :loading="form.processing"
                                class="text-none mb-4 mx-auto"
                                color="primary"
                                type="submit"
                                variant="flat"
                            >
                                Submit
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
