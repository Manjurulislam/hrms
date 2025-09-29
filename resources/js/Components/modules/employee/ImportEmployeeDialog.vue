<script setup>
import {computed} from 'vue';
import {useToast} from 'vue-toastification';
import {useForm} from '@inertiajs/vue3';

const toast = useToast();
const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    companies: Array,
    departments: Array,
});


const form = useForm({
    file: '',
    company_id: null,
    department_id: null,
});


// Filter departments based on selected company
const filteredDepartments = computed(() => {
    if (!form.company_id) {
        return [];
    }
    return props.departments.filter(dept => dept.company_id === form.company_id);
});

// Reset dependent fields when company changes
const onCompanyChange = () => {
    form.department_id = null;
};


const statusOptions = [
    {value: 'READY_FOR_FIELD_VISIT', title: 'Approved'},
    {value: 'PLANNING_REQUIRED', title: 'Reject'}
];


const emit = defineEmits(['update:modelValue', 'import-success']);
const localDialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

const closeModal = () => {
    emit('update:modelValue', false);
};

const submit = () => {
    form.post(route('employees.import'), {
        onSuccess: (success) => {
            closeModal();
            form.reset();
            emit('import-success');
            toast('Data has been added successfully.');
        },
        onError: (error) => {
            toast.error('Something is wrong. Please try again.');
        }
    });
};
</script>

<template>
    <div class="pa-4 text-center">
        <v-dialog
            v-model="localDialog"
            max-width="800"
        >
            <v-card>
                <v-card-title class="border-b">
                    <h4>
                        Employee Import
                    </h4>
                </v-card-title>
                <form @submit.prevent="submit">
                    <v-card-text>
                        <v-file-input
                            v-model="form.file"
                            :error-messages="form.errors.file"
                            clearable
                            density="compact"
                            label="Upload File"
                            variant="outlined"
                        />

                        <v-row>
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
                                    @update:model-value="onCompanyChange"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="form.department_id"
                                    :error-messages="form.errors.department_id"
                                    :items="filteredDepartments"
                                    clearable
                                    density="compact"
                                    item-title="name"
                                    item-value="id"
                                    label="Department"
                                    required
                                    variant="outlined"
                                />
                            </v-col>
                        </v-row>
                    </v-card-text>
                    <v-card-actions class="mr-2">
                        <v-spacer></v-spacer>
                        <v-btn
                            size="small"
                            text="Close"
                            variant="outlined"
                            @click="closeModal"
                        ></v-btn>
                        <v-btn
                            color="primary"
                            size="small"
                            text="Submit"
                            type="submit"
                            variant="outlined"
                        ></v-btn>
                    </v-card-actions>
                </form>
            </v-card>
        </v-dialog>
    </div>
</template>
