<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();
const props = defineProps({
    departments: Array,
    designations: Array,
    genderOptions: Array,
    bloodGroupOptions: Array,
    maritalStatusOptions: Array,
});

const form = useForm({
    id_no: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    sec_phone: '',
    nid: '',
    gender: '',
    qualification: '',
    emergency_contact: '',
    blood_group: '',
    marital_status: '',
    bank_account: '',
    address: '',
    department_id: '',
    designations: [],
    date_of_birth: '',
    joining_date: '',
    probation_end_at: '',
    status: true,
});

const submit = () => {
    form.post(route('employees.store'), {
        onSuccess: (success) => {
            toast('Employee has been added successfully.');
        },
        onError: (error) => {
            toast.error('Something is wrong. Please try again.');
        }
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Employee"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        icon="mdi-arrow-left-bold"
                        title="Create Employee"
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
                                    <v-col cols="12" md="6">
                                        <TextInput
                                            v-model="form.email"
                                            :error-messages="form.errors.email"
                                            label="Email"
                                            required
                                            type="email"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="3">
                                        <TextInput
                                            v-model="form.phone"
                                            :error-messages="form.errors.phone"
                                            label="Primary Phone"
                                            required
                                            type="tel"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="3">
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
                                            item-title="text"
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
                                            item-title="text"
                                            item-value="value"
                                            label="Marital Status"
                                            variant="outlined"
                                        />
                                    </v-col>
                                </v-row>

                                <v-row>
                                    <v-col cols="12" md="4">
                                        <div class="mt-3">
                                            <v-label class="mb-2 font-weight-medium">Date of Birth</v-label>
                                            <el-date-picker
                                                v-model="form.date_of_birth"
                                                format="YYYY-MM-DD"
                                                placeholder="Select date of birth"
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
                                            item-title="text"
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
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-model="form.department_id"
                                            :error-messages="form.errors.department_id"
                                            :items="departments"
                                            clearable
                                            density="compact"
                                            item-title="name"
                                            item-value="id"
                                            label="Department"
                                            required
                                            variant="outlined"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-model="form.designations"
                                            :error-messages="form.errors.designations"
                                            :items="designations"
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
                                        <div class="mt-3">
                                            <v-label class="mb-2 font-weight-medium">Joining Date *</v-label>
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
                                        </div>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <div class="mt-3">
                                            <v-label class="mb-2 font-weight-medium">Probation End Date</v-label>
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
                                        </div>
                                    </v-col>
                                    <v-col cols="12" md="4">
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
                                Submit
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
