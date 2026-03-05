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
    employees: Array,
    genderOptions: Array,
    bloodGroupOptions: Array,
    maritalStatusOptions: Array,
    empStatusOptions: Array,
});

let form = useForm({
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
    designation_id: null,
    manager_id: null,
    emp_status: 'probation',
    date_of_birth: '',
    joining_date: '',
    status: true,
});

const filteredDepartments = computed(() => {
    if (!form.company_id) return [];
    return props.departments.filter(dept => dept.company_id === form.company_id);
});

const filteredDesignations = computed(() => {
    if (!form.company_id) return [];
    return props.designations.filter(d => d.company_id === form.company_id);
});

const filteredManagers = computed(() => {
    if (!form.company_id) return [];
    return props.employees
        .filter(e => e.company_id === form.company_id && e.id !== props.item.id)
        .map(e => ({...e, full_name: `${e.first_name} ${e.last_name}`}));
});

const onCompanyChange = () => {
    form.department_id = null;
    form.designation_id = null;
    form.manager_id = null;
};

const submit = () => {
    form.put(route('employees.update', props.item.id), {
        onSuccess: () => toast('Employee has been updated successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};

onMounted(() => {
    form = Object.assign(form, props.item);
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
                        <v-card-text>
                            <v-row>
                                <!-- Left Column -->
                                <v-col cols="12" md="8">
                                    <v-card variant="outlined" class="mb-5">
                                        <v-toolbar density="compact" color="transparent" class="border-b">
                                            <v-icon class="ml-4" size="small">mdi-account</v-icon>
                                            <v-toolbar-title class="text-body-2 font-weight-bold">Personal Information</v-toolbar-title>
                                        </v-toolbar>
                                        <v-card-text>
                                            <v-row dense>
                                                <v-col cols="12" md="6">
                                                    <TextInput v-model="form.first_name" :error-messages="form.errors.first_name" label="First Name" required/>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <TextInput v-model="form.last_name" :error-messages="form.errors.last_name" label="Last Name"/>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <TextInput v-model="form.email" :error-messages="form.errors.email" label="Email" required type="email"/>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <TextInput v-model="form.phone" :error-messages="form.errors.phone" label="Phone" type="tel"/>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <TextInput v-model="form.nid" :error-messages="form.errors.nid" label="National ID"/>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <v-select v-model="form.gender" :error-messages="form.errors.gender" :items="genderOptions" clearable density="compact" item-title="label" item-value="value" label="Gender" variant="outlined"/>
                                                </v-col>
                                            </v-row>
                                        </v-card-text>
                                    </v-card>

                                    <v-card variant="outlined" class="mb-5">
                                        <v-toolbar density="compact" color="transparent" class="border-b">
                                            <v-icon class="ml-4" size="small">mdi-briefcase</v-icon>
                                            <v-toolbar-title class="text-body-2 font-weight-bold">Employment Information</v-toolbar-title>
                                        </v-toolbar>
                                        <v-card-text>
                                            <v-row dense>
                                                <v-col cols="12" md="6">
                                                    <v-select v-model="form.company_id" :error-messages="form.errors.company_id" :items="companies" clearable density="compact" item-title="name" item-value="id" label="Company" required variant="outlined" @update:model-value="onCompanyChange"/>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <v-select v-model="form.department_id" :error-messages="form.errors.department_id" :items="filteredDepartments" clearable density="compact" item-title="name" item-value="id" label="Department" required variant="outlined"/>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <v-select v-model="form.designation_id" :error-messages="form.errors.designation_id" :items="filteredDesignations" clearable density="compact" item-title="title" item-value="id" label="Designation" variant="outlined"/>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <v-select v-model="form.manager_id" :error-messages="form.errors.manager_id" :items="filteredManagers" clearable density="compact" item-title="full_name" item-value="id" label="Reporting Manager" variant="outlined"/>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <v-select v-model="form.emp_status" :error-messages="form.errors.emp_status" :items="empStatusOptions" density="compact" item-title="label" item-value="value" label="Employment Status" variant="outlined"/>
                                                </v-col>
                                            </v-row>
                                        </v-card-text>
                                    </v-card>

                                    <v-card variant="outlined">
                                        <v-toolbar density="compact" color="transparent" class="border-b">
                                            <v-icon class="ml-4" size="small">mdi-card-account-details</v-icon>
                                            <v-toolbar-title class="text-body-2 font-weight-bold">Additional Details</v-toolbar-title>
                                        </v-toolbar>
                                        <v-card-text>
                                            <v-row dense>
                                                <v-col cols="12" md="6">
                                                    <TextInput v-model="form.emergency_contact" :error-messages="form.errors.emergency_contact" label="Emergency Contact" type="tel"/>
                                                </v-col>
                                                <v-col cols="12" md="6">
                                                    <TextInput v-model="form.bank_account" :error-messages="form.errors.bank_account" label="Bank Account"/>
                                                </v-col>
                                                <v-col cols="12">
                                                    <v-textarea v-model="form.address" :error-messages="form.errors.address" density="compact" label="Address" rows="3" variant="outlined"/>
                                                </v-col>
                                            </v-row>
                                        </v-card-text>
                                    </v-card>
                                </v-col>

                                <!-- Right Column -->
                                <v-col cols="12" md="4">
                                    <v-card variant="outlined" class="mb-5">
                                        <v-toolbar density="compact" color="transparent" class="border-b">
                                            <v-icon class="ml-4" size="small">mdi-calendar</v-icon>
                                            <v-toolbar-title class="text-body-2 font-weight-bold">Dates</v-toolbar-title>
                                        </v-toolbar>
                                        <v-card-text>
                                            <div class="mb-4">
                                                <v-label class="text-caption mb-1">Date of Birth</v-label>
                                                <el-date-picker v-model="form.date_of_birth" format="YYYY-MM-DD" placeholder="Select date" style="width: 100%" type="date" value-format="YYYY-MM-DD"/>
                                                <div v-if="form.errors.date_of_birth" class="text-error text-caption mt-1">{{ form.errors.date_of_birth }}</div>
                                            </div>
                                            <div>
                                                <v-label class="text-caption mb-1">Joining Date</v-label>
                                                <el-date-picker v-model="form.joining_date" format="YYYY-MM-DD" placeholder="Select date" style="width: 100%" type="date" value-format="YYYY-MM-DD"/>
                                                <div v-if="form.errors.joining_date" class="text-error text-caption mt-1">{{ form.errors.joining_date }}</div>
                                            </div>
                                        </v-card-text>
                                    </v-card>

                                    <v-card variant="outlined">
                                        <v-toolbar density="compact" color="transparent" class="border-b">
                                            <v-icon class="ml-4" size="small">mdi-heart-pulse</v-icon>
                                            <v-toolbar-title class="text-body-2 font-weight-bold">Personal Details</v-toolbar-title>
                                        </v-toolbar>
                                        <v-card-text>
                                            <v-select v-model="form.marital_status" :error-messages="form.errors.marital_status" :items="maritalStatusOptions" clearable density="compact" item-title="label" item-value="value" label="Marital Status" variant="outlined"/>
                                            <v-select v-model="form.blood_group" :error-messages="form.errors.blood_group" :items="bloodGroupOptions" clearable density="compact" item-title="label" item-value="value" label="Blood Group" variant="outlined"/>
                                            <TextInput v-model="form.qualification" :error-messages="form.errors.qualification" label="Qualification"/>
                                            <TextInput v-model="form.sec_phone" :error-messages="form.errors.sec_phone" label="Secondary Phone" type="tel"/>
                                        </v-card-text>
                                    </v-card>
                                </v-col>
                            </v-row>
                        </v-card-text>

                        <v-divider></v-divider>
                        <v-card-actions>
                            <v-btn :loading="form.processing" class="text-none mb-4 mx-auto" color="primary" type="submit" variant="flat">
                                Update Employee
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
