<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";
import {computed} from "vue";

const toast = useToast();
const props = defineProps({
    companies: Array,
    approverTypes: Array,
    conditionTypes: Array,
    designations: Array,
    employees: Array,
});

const form = useForm({
    name: '',
    company_id: null,
    steps: [
        {approver_type: 'direct_manager', approver_value: null, is_mandatory: true, condition_type: 'always', condition_value: null},
    ],
});

const addStep = () => {
    form.steps.push({
        approver_type: 'direct_manager',
        approver_value: null,
        is_mandatory: true,
        condition_type: 'always',
        condition_value: null,
    });
};

const removeStep = (index) => {
    if (form.steps.length > 1) {
        form.steps.splice(index, 1);
    }
};

const moveStep = (index, direction) => {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= form.steps.length) return;
    const temp = form.steps[index];
    form.steps[index] = form.steps[newIndex];
    form.steps[newIndex] = temp;
};

const needsApproverValue = (type) => ['designation_level', 'specific_employee'].includes(type);
const needsConditionValue = (type) => ['days_greater_than', 'days_less_than'].includes(type);

const approverTypeIcons = {
    direct_manager: 'mdi-account-tie',
    designation_level: 'mdi-badge-account-horizontal',
    specific_employee: 'mdi-account-check',
    department_head: 'mdi-account-supervisor',
};

const getApproverTypeIcon = (type) => approverTypeIcons[type] || 'mdi-account';

const getApproverTypeLabel = (type) => {
    const found = props.approverTypes.find(t => t.value === type);
    return found ? found.label : type;
};

const requiredSteps = computed(() => form.steps.filter(s => s.is_mandatory).length);
const optionalSteps = computed(() => form.steps.filter(s => !s.is_mandatory).length);

const submit = () => {
    form.post(route('approval-workflows.store'), {
        onSuccess: () => toast('Workflow created successfully.'),
        onError: () => toast.error('Something went wrong. Please try again.'),
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Approval Workflow"/>
        <v-card>
            <CardTitle
                :extra-route="{title: 'Back', route: 'approval-workflows.index', icon: 'mdi-arrow-left-bold'}"
                icon="mdi-sitemap-outline"
                title="Create Approval Workflow"
            />
            <form @submit.prevent="submit">
                <v-card-text>
                    <v-row>
                        <!-- Left Column -->
                        <v-col cols="12" md="8" order="2" order-md="1">
                            <!-- Workflow Details -->
                            <v-card variant="outlined" class="mb-4">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-information-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Basic Information</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-row dense>
                                        <v-col cols="12" md="6">
                                            <TextInput
                                                v-model="form.name"
                                                :error-messages="form.errors.name"
                                                label="Name"
                                                placeholder="e.g., Standard Leave Approval"
                                            />
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <v-select
                                                v-model="form.company_id"
                                                :error-messages="form.errors.company_id"
                                                :items="companies"
                                                density="compact"
                                                item-title="name"
                                                item-value="id"
                                                label="Company"
                                                variant="outlined"
                                                hide-details="auto"
                                            >
                                                <template #prepend-inner>
                                                    <v-icon size="18" color="medium-emphasis">mdi-domain</v-icon>
                                                </template>
                                            </v-select>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Approval Steps -->
                            <v-card variant="outlined">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-sitemap</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Who Needs to Approve?</v-toolbar-title>
                                    <template #append>
                                        <v-btn
                                            color="primary"
                                            prepend-icon="mdi-plus"
                                            size="small"
                                            variant="tonal"
                                            class="mr-2 text-none"
                                            @click="addStep"
                                        >
                                            Add Step
                                        </v-btn>
                                    </template>
                                </v-toolbar>
                                <v-card-text>
                                    <div v-if="form.errors.steps" class="text-error text-body-2 mb-3">
                                        {{ form.errors.steps }}
                                    </div>

                                    <!-- Steps -->
                                    <div
                                        v-for="(step, index) in form.steps"
                                        :key="index"
                                    >
                                        <v-card variant="outlined" class="mb-1">
                                            <!-- Step Header -->
                                            <v-toolbar density="compact" color="transparent" class="border-b">
                                                <div class="d-flex align-center ga-2 ml-4">
                                                    <v-avatar size="28" color="primary" variant="tonal">
                                                        <span class="text-caption font-weight-bold">{{ index + 1 }}</span>
                                                    </v-avatar>
                                                    <v-icon size="16" color="medium-emphasis">{{ getApproverTypeIcon(step.approver_type) }}</v-icon>
                                                    <span class="text-body-2 font-weight-medium">{{ getApproverTypeLabel(step.approver_type) }}</span>
                                                    <v-chip
                                                        :color="step.is_mandatory ? 'success' : 'warning'"
                                                        size="x-small"
                                                        variant="tonal"
                                                        label
                                                    >
                                                        {{ step.is_mandatory ? 'Required' : 'Optional' }}
                                                    </v-chip>
                                                </div>
                                                <template #append>
                                                    <v-btn
                                                        :disabled="index === 0"
                                                        icon="mdi-chevron-up"
                                                        size="x-small"
                                                        variant="text"
                                                        @click="moveStep(index, -1)"
                                                    />
                                                    <v-btn
                                                        :disabled="index === form.steps.length - 1"
                                                        icon="mdi-chevron-down"
                                                        size="x-small"
                                                        variant="text"
                                                        @click="moveStep(index, 1)"
                                                    />
                                                    <v-divider vertical class="mx-1 my-2"/>
                                                    <v-btn
                                                        :disabled="form.steps.length <= 1"
                                                        color="error"
                                                        icon="mdi-trash-can-outline"
                                                        size="x-small"
                                                        variant="text"
                                                        class="mr-2"
                                                        @click="removeStep(index)"
                                                    />
                                                </template>
                                            </v-toolbar>

                                            <!-- Step Body -->
                                            <v-card-text>
                                                <v-row dense>
                                                    <!-- Approver Type -->
                                                    <v-col cols="12" sm="6" :md="needsApproverValue(step.approver_type) ? 3 : 4">
                                                        <v-select
                                                            v-model="step.approver_type"
                                                            :error-messages="form.errors[`steps.${index}.approver_type`]"
                                                            :items="approverTypes"
                                                            density="compact"
                                                            item-title="label"
                                                            item-value="value"
                                                            label="Approver Type"
                                                            variant="outlined"
                                                            hide-details="auto"
                                                        >
                                                            <template #prepend-inner>
                                                                <v-icon size="18" color="medium-emphasis">{{ getApproverTypeIcon(step.approver_type) }}</v-icon>
                                                            </template>
                                                        </v-select>
                                                    </v-col>

                                                    <!-- Approver Value (conditional) -->
                                                    <v-col v-if="needsApproverValue(step.approver_type)" cols="12" sm="6" md="3">
                                                        <v-select
                                                            v-if="step.approver_type === 'designation_level'"
                                                            v-model="step.approver_value"
                                                            :error-messages="form.errors[`steps.${index}.approver_value`]"
                                                            :items="designations"
                                                            density="compact"
                                                            :item-title="d => `${d.title} (Level ${d.level})`"
                                                            item-value="id"
                                                            label="Designation"
                                                            variant="outlined"
                                                            hide-details="auto"
                                                        >
                                                            <template #prepend-inner>
                                                                <v-icon size="18" color="medium-emphasis">mdi-shield-star-outline</v-icon>
                                                            </template>
                                                        </v-select>
                                                        <v-autocomplete
                                                            v-else-if="step.approver_type === 'specific_employee'"
                                                            v-model="step.approver_value"
                                                            :error-messages="form.errors[`steps.${index}.approver_value`]"
                                                            :items="employees"
                                                            density="compact"
                                                            :item-title="e => `${e.first_name} ${e.last_name}`"
                                                            item-value="id"
                                                            label="Select Employee"
                                                            variant="outlined"
                                                            clearable
                                                            hide-details="auto"
                                                        >
                                                            <template #prepend-inner>
                                                                <v-icon size="18" color="medium-emphasis">mdi-account-search</v-icon>
                                                            </template>
                                                        </v-autocomplete>
                                                    </v-col>

                                                    <!-- Condition Type -->
                                                    <v-col cols="12" sm="6" md="3">
                                                        <v-select
                                                            v-model="step.condition_type"
                                                            :error-messages="form.errors[`steps.${index}.condition_type`]"
                                                            :items="conditionTypes"
                                                            density="compact"
                                                            item-title="label"
                                                            item-value="value"
                                                            label="Condition"
                                                            variant="outlined"
                                                            hide-details="auto"
                                                        >
                                                            <template #prepend-inner>
                                                                <v-icon size="18" color="medium-emphasis">mdi-filter-outline</v-icon>
                                                            </template>
                                                        </v-select>
                                                    </v-col>

                                                    <!-- Condition Value (conditional) -->
                                                    <v-col v-if="needsConditionValue(step.condition_type)" cols="12" sm="3" md="1">
                                                        <v-text-field
                                                            v-model.number="step.condition_value"
                                                            :error-messages="form.errors[`steps.${index}.condition_value`]"
                                                            density="compact"
                                                            label="Days"
                                                            min="1"
                                                            type="number"
                                                            variant="outlined"
                                                            hide-details="auto"
                                                        />
                                                    </v-col>

                                                    <!-- Mandatory Toggle -->
                                                    <v-col cols="12" sm="6" md="2">
                                                        <div class="d-flex align-center ga-2 pt-2">
                                                            <el-switch
                                                                v-model="step.is_mandatory"
                                                                size="small"
                                                                style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"
                                                            />
                                                            <span class="text-body-2" :class="step.is_mandatory ? 'text-success' : 'text-warning'">
                                                                {{ step.is_mandatory ? 'Required' : 'Optional' }}
                                                            </span>
                                                        </div>
                                                    </v-col>
                                                </v-row>
                                            </v-card-text>
                                        </v-card>

                                        <!-- Connector -->
                                        <div v-if="index < form.steps.length - 1" class="d-flex justify-center my-1">
                                            <v-icon size="16" color="grey">mdi-chevron-down</v-icon>
                                        </div>
                                    </div>

                                    <!-- Empty State -->
                                    <div v-if="form.steps.length === 0" class="text-center pa-8">
                                        <v-icon size="48" color="grey-lighten-1">mdi-sitemap-outline</v-icon>
                                        <div class="text-body-2 text-medium-emphasis mt-2">No approval steps defined</div>
                                        <v-btn
                                            color="primary"
                                            prepend-icon="mdi-plus"
                                            size="small"
                                            variant="tonal"
                                            class="mt-3 text-none"
                                            @click="addStep"
                                        >
                                            Add First Step
                                        </v-btn>
                                    </div>

                                    <v-alert
                                        type="info"
                                        variant="tonal"
                                        density="compact"
                                        class="mt-4 text-caption"
                                    >
                                        Each step runs in order from top to bottom. You can add conditions to require extra approval for longer leaves.
                                    </v-alert>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <!-- Right Column -->
                        <v-col cols="12" md="4" order="1" order-md="2">
                            <!-- Summary -->
                            <v-card variant="outlined" class="mb-4">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-chart-box-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Overview</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text class="pa-0">
                                    <v-list density="compact">
                                        <v-list-item>
                                            <template #prepend>
                                                <v-icon size="18" color="primary">mdi-layers-outline</v-icon>
                                            </template>
                                            <v-list-item-title class="text-body-2">Total Steps</v-list-item-title>
                                            <template #append>
                                                <v-chip size="small" color="primary" variant="tonal" label>
                                                    {{ form.steps.length }}
                                                </v-chip>
                                            </template>
                                        </v-list-item>
                                        <v-list-item>
                                            <template #prepend>
                                                <v-icon size="18" color="success">mdi-check-circle-outline</v-icon>
                                            </template>
                                            <v-list-item-title class="text-body-2">Required</v-list-item-title>
                                            <template #append>
                                                <v-chip size="small" color="success" variant="tonal" label>
                                                    {{ requiredSteps }}
                                                </v-chip>
                                            </template>
                                        </v-list-item>
                                        <v-list-item>
                                            <template #prepend>
                                                <v-icon size="18" color="warning">mdi-minus-circle-outline</v-icon>
                                            </template>
                                            <v-list-item-title class="text-body-2">Optional</v-list-item-title>
                                            <template #append>
                                                <v-chip size="small" color="warning" variant="tonal" label>
                                                    {{ optionalSteps }}
                                                </v-chip>
                                            </template>
                                        </v-list-item>
                                    </v-list>
                                </v-card-text>
                            </v-card>

                            <!-- Quick Guide -->
                            <v-alert type="info" variant="tonal" density="compact" class="text-caption">
                                <div class="font-weight-bold mb-1">Quick Guide</div>
                                <div><v-icon size="14">mdi-account-tie</v-icon> <strong>Direct Manager</strong> - Employee's immediate boss</div>
                                <div><v-icon size="14">mdi-badge-account-horizontal</v-icon> <strong>Designation Level</strong> - A manager at a specific rank</div>
                                <div><v-icon size="14">mdi-account-check</v-icon> <strong>Specific Employee</strong> - A named person always approves</div>
                                <div><v-icon size="14">mdi-account-supervisor</v-icon> <strong>Department Head</strong> - The top person in the department</div>
                            </v-alert>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-divider/>
                <v-card-actions class="justify-center pa-4">
                    <v-btn
                        :loading="form.processing"
                        class="text-none"
                        color="primary"
                        prepend-icon="mdi-check"
                        type="submit"
                        variant="flat"
                    >
                        Create Workflow
                    </v-btn>
                </v-card-actions>
            </form>
        </v-card>
    </DefaultLayout>
</template>
