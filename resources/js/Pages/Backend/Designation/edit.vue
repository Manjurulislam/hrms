<script setup>
import TextInput from '@/Components/common/form/TextInput.vue';
import {Head, useForm} from '@inertiajs/vue3';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {computed, onMounted, watch} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const toast = useToast();
const props = defineProps({
    item: Object,
    companies: Array,
    departments: Array,
    designations: Array
});

let form = useForm({
    title: '',
    description: '',
    company_id: '',
    department_id: '',
    parent_id: '',
    status: true
});

const filteredDepartments = computed(() => {
    if (!form.company_id) return [];
    return props.departments.filter(dept => dept.company_id == form.company_id);
});

const filteredDesignations = computed(() => {
    if (!form.company_id) return [];
    // Exclude current item from parent options to prevent circular reference
    return props.designations.filter(designation =>
        designation.company_id == form.company_id && designation.id !== props.item.id
    );
});

// Reset department when company changes (but not on initial load)
let isInitialLoad = true;
watch(() => form.company_id, () => {
    if (!isInitialLoad) {
        form.department_id = '';
        form.parent_id = '';
    }
});

const submit = () => {
    form.put(route('designation.update', props.item.id), {
        onSuccess: () => toast('Designation has been updated successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};

onMounted(() => {
    form = Object.assign(form, props.item);
    isInitialLoad = false;
});
</script>

<template>
    <DefaultLayout>
        <Head title="Edit Designation"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        icon="mdi-arrow-left-bold"
                        title="Edit Designation"
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
                                        item-title="name"
                                        item-value="id"
                                        label="Company"
                                        variant="outlined"
                                        density="compact"
                                        clearable
                                        required
                                    />
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-select
                                        v-model="form.department_id"
                                        :error-messages="form.errors.department_id"
                                        :items="filteredDepartments"
                                        item-title="name"
                                        item-value="id"
                                        label="Department"
                                        variant="outlined"
                                        density="compact"
                                        clearable
                                        :disabled="!form.company_id"
                                        placeholder="Select company first"
                                        required
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-select
                                        v-model="form.parent_id"
                                        :error-messages="form.errors.parent_id"
                                        :items="filteredDesignations"
                                        item-title="title"
                                        item-value="id"
                                        label="Parent Designation (Optional)"
                                        variant="outlined"
                                        density="compact"
                                        clearable
                                        :disabled="!form.company_id"
                                        placeholder="Select for hierarchy"
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
                                        rows="4"
                                        variant="outlined"
                                        placeholder="Enter designation description..."
                                    />
                                </v-col>
                            </v-row>
                        </v-card-text>
                        <v-divider></v-divider>
                        <v-card-actions>
                            <v-btn
                                class="text-none mb-4 mx-auto"
                                color="primary"
                                type="submit"
                                variant="flat"
                                :loading="form.processing"
                            >
                                Update
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template
