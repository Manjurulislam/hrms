<script setup>
import TextInput from '@/Components/common/form/TextInput.vue';
import {Head, useForm} from '@inertiajs/vue3';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {computed, onMounted} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const toast = useToast();
const props = defineProps({
    item: Object,
    companies: Array,
    departments: Array,
    designations: Array,
    selectedDesignations: Array,
    genderOptions: Array,
    bloodGroupOptions: Array,
    maritalStatusOptions: Array,
});

let form = useForm({
    id_no: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    sec_phone: '',
    nid: '',
    gender: null,
    qualification: '',
    emergency_contact: '',
    blood_group: null,
    marital_status: null,
    bank_account: '',
    address: '',
    company_id: null,
    department_id: null,
    designations: [],
    date_of_birth: '',
    joining_date: '',
    probation_end_at: '',
    status: true
});

// Filter departments based on selected company
const filteredDepartments = computed(() => {
    if (!form.company_id) {
        return [];
    }
    return props.departments.filter(dept => dept.company_id === form.company_id);
});

// Filter designations based on selected company and optionally department
const filteredDesignations = computed(() => {
    if (!form.company_id) {
        return [];
    }

    let filtered = props.designations.filter(designation =>
        designation.company_id === form.company_id
    );

    // If department is selected, further filter by department
    if (form.department_id) {
        filtered = filtered.filter(designation =>
            !designation.department_id || designation.department_id === form.department_id
        );
    }

    return filtered;
});

// Reset dependent fields when company changes
const onCompanyChange = () => {
    form.department_id = '';
    form.designations = [];
};

// Reset designations when department changes
const onDepartmentChange = () => {
    form.designations = [];
};

const submit = () => {
    form.put(route('employees.update', props.item.id), {
        onSuccess: () => toast('Employee has been updated successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};

onMounted(() => {
    form = Object.assign(form, props.item);
    form.designations = props.selectedDesignations || [];
});
</script>

<template>
    <DefaultLayout>
        <Head title="Edit Employee"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back', route: 'employees.index', icon:'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        title="Edit Employee"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4 rounded-md">
                            <!-- Personal Information Section -->
                            <div class="mb-6">
                                <h3 class="text-h6 mb-4 text-primary">Personal Information</h3>
                                <v-row>
                                    <v-col cols="12" md="4">
                                        <TextInput
                                            v-model="form.id_no"
                                            :error-messages="form.errors.id_no"
                                            label="Employee ID"
                                            required
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <TextInput
                                            v-model="form.first_name"
                                            :error-messages="form.errors.first_name"
                                            label="First Name"
                                            required
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <TextInput
                                            v-model="form.last_name"
                                            :error-messages="form.errors.last_name"
                                            label="Last Name"
                                            required
                                        />
                                    </v-col>
                                </v-row>

                                <v-row>
                                    <v-col cols="12" md="4">
                                        <TextInput
                                            v-model="form.email"
                                            :error-messages="form.errors.email"
                                            label="Email"
                                            required
                                            type="email"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <TextInput
                                            v-model="form.phone"
                                            :error-messages="form.errors.phone"
                                            label="Primary Phone"
                                            required
                                            type="tel"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <TextInput
                                            v-model="form.sec_phone"
                                            :error-messages="form.errors.sec_phone"
                                            label="Secondary Phone"
                                            type="tel"
                                        />
                                    </v-col>
                                </v-row>

                                <v-row>
                                    <v-col cols="12" md="4">
                                        <TextInput
                                            v-model="form.nid"
                                            :error-messages="form.errors.nid"
                                            label="National ID"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-select
                                            v-model="form.gender"
                                            :error-messages="form.errors.gender"
                                            :items="genderOptions"
                                            clearable
                                            density="compact"
                                            item-title="label"
                                            item-value="value"
                                            label="Gender"
                                            variant="outlined"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-select
                                            v-model="form.marital_status"
                                            :error-messages="form.errors.marital_status"
                                            :items="maritalStatusOptions"
                                            clearable
                                            density="compact"
                                            item-title="label"
                                            item-value="value"
                                            label="Marital Status"
                                            variant="outlined"
                                        />
                                    </v-col>
                                </v-row>

                                <v-row>
                                    <v-col cols="12" md="4">
                                        <div>
                                            <el-date-picker
                                                v-model="form.date_of_birth"
                                                format="YYYY-MM-DD"
                                                placeholder="Select date of birth"
                                                size="large"
                                                style="width: 100%"
                                                type="date"
                                                value-format="YYYY-MM-DD"
                                            />
                                            <div v-if="form.errors.date_of_birth" class="text-error text-caption mt-1">
                                                {{ form.errors.date_of_birth }}
                                            </div>
                                        </div>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-select
                                            v-model="form.blood_group"
                                            :error-messages="form.errors.blood_group"
                                            :items="bloodGroupOptions"
                                            clearable
                                            density="compact"
                                            item-title="label"
                                            item-value="value"
                                            label="Blood Group"
                                            variant="outlined"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <TextInput
                                            v-model="form.qualification"
                                            :error-messages="form.errors.qualification"
                                            label="Qualification"
                                            placeholder="e.g., Bachelor's in Computer Science"
                                        />
                                    </v-col>
                                </v-row>
                            </div>

                            <!-- Employment Information Section -->
                            <div class="mb-6">
                                <h3 class="text-h6 mb-4 text-primary">Employment Information</h3>
                                <v-row>
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
                                            required
                                            variant="outlined"
                                            @update:model-value="onCompanyChange"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
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
                                            @update:model-value="onDepartmentChange"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-select
                                            v-model="form.designations"
                                            :error-messages="form.errors.designations"
                                            :items="filteredDesignations"
                                            chips
                                            clearable
                                            closable-chips
                                            density="compact"
                                            item-title="title"
                                            item-value="id"
                                            label="Designations"
                                            multiple
                                            variant="outlined"
                                        />
                                    </v-col>
                                </v-row>

                                <v-row>
                                    <v-col cols="12" md="4">
                                        <v-label class="font-weight-medium">Joining Date *</v-label>
                                        <el-date-picker
                                            v-model="form.joining_date"
                                            format="YYYY-MM-DD"
                                            placeholder="Select joining date"
                                            style="width: 100%"
                                            type="date"
                                            value-format="YYYY-MM-DD"
                                        />
                                        <div v-if="form.errors.joining_date" class="text-error text-caption mt-1">
                                            {{ form.errors.joining_date }}
                                        </div>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-label class="font-weight-medium">Probation End Date</v-label>
                                        <el-date-picker
                                            v-model="form.probation_end_at"
                                            format="YYYY-MM-DD"
                                            placeholder="Select probation end date"
                                            style="width: 100%"
                                            type="date"
                                            value-format="YYYY-MM-DD"
                                        />
                                        <div v-if="form.errors.probation_end_at"
                                             class="text-error text-caption mt-1">
                                            {{ form.errors.probation_end_at }}
                                        </div>
                                    </v-col>
                                </v-row>
                            </div>

                            <!-- Additional Information Section -->
                            <div class="mb-6">
                                <h3 class="text-h6 mb-4 text-primary">Additional Information</h3>
                                <v-row>
                                    <v-col cols="12" md="6">
                                        <TextInput
                                            v-model="form.emergency_contact"
                                            :error-messages="form.errors.emergency_contact"
                                            label="Emergency Contact"
                                            type="tel"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <TextInput
                                            v-model="form.bank_account"
                                            :error-messages="form.errors.bank_account"
                                            label="Bank Account"
                                        />
                                    </v-col>
                                </v-row>

                                <v-row>
                                    <v-col cols="12">
                                        <v-textarea
                                            v-model="form.address"
                                            :error-messages="form.errors.address"
                                            label="Address"
                                            placeholder="Enter complete address..."
                                            rows="3"
                                            variant="outlined"
                                        />
                                    </v-col>
                                </v-row>
                            </div>
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
                                Update
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
