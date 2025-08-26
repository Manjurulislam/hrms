<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import {computed, watch} from "vue";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();
const props = defineProps({
    companies: Array,
    departments: Array,
    designations: Array
});

const form = useForm({
    title: '',
    description: '',
    company_id: '',
    department_id: '',
    parent_id: '',
    status: true,
});

const filteredDepartments = computed(() => {
    if (!form.company_id) return [];
    return props.departments.filter(dept => dept.company_id == form.company_id);
});

const filteredDesignations = computed(() => {
    if (!form.company_id) return [];
    return props.designations.filter(designation => designation.company_id == form.company_id);
});

// Reset department when company changes
watch(() => form.company_id, () => {
    form.department_id = '';
    form.parent_id = '';
});

const submit = () => {
    form.post(route('designation.store'), {
        onSuccess: (success) => {
            toast('Designation has been added successfully.');
        },
        onError: (error) => {
            toast.error('Something is wrong. Please try again.');
        }
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Designation"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        icon="mdi-arrow-left-bold"
                        title="Create Designation"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4 rounded-md">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.title"
                                        :error-messages="form.errors.title"
                                        label="Title"
                                        required
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-select
                                        v-model="form.company_id"
                                        :error-messages="form.errors.company_id"
                                        :items="companies"
                                        clearable
                                        density="compact"
                                        item-title="name"
                                        item-value="id"
                                        label="Company"
                                        required
                                        variant="outlined"
                                    />
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-select
                                        v-model="form.department_id"
                                        :disabled="!form.company_id"
                                        :error-messages="form.errors.department_id"
                                        :items="filteredDepartments"
                                        clearable
                                        density="compact"
                                        item-title="name"
                                        item-value="id"
                                        label="Department"
                                        placeholder="Select company first"
                                        required
                                        variant="outlined"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-select
                                        v-model="form.parent_id"
                                        :disabled="!form.company_id"
                                        :error-messages="form.errors.parent_id"
                                        :items="filteredDesignations"
                                        clearable
                                        density="compact"
                                        item-title="title"
                                        item-value="id"
                                        label="Parent Designation (Optional)"
                                        placeholder="Select for hierarchy"
                                        variant="outlined"
                                    />
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12" md="6">
                                    <div class="mt-3">
                                        <v-label class="mb-2 font-weight-medium">Status</v-label>
                                        <div>
                                            <el-switch
                                                v-model="form.status"
                                                size="large"
                                                style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"
                                            />
                                        </div>
                                    </div>
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="form.description"
                                        :error-messages="form.errors.description"
                                        label="Description"
                                        placeholder="Enter designation description..."
                                        rows="4"
                                        variant="outlined"
                                    />
                                </v-col>
                            </v-row>
                        </v-card-text>
                        <v-divider></v-divider>
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
