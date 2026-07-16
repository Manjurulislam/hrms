<script setup>
import {computed, ref} from 'vue';
import {VueDraggable} from 'vue-draggable-plus';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import TextInput from '@/Components/common/form/TextInput.vue';
import WorkflowNode from './WorkflowNode.vue';
import WorkflowNodeDrawer from './WorkflowNodeDrawer.vue';

const props = defineProps({
    name: {type: String, default: ''},
    companyId: {type: Number, default: null},
    steps: {type: Array, required: true},
    companies: {type: Array, default: () => []},
    approverTypes: {type: Array, default: () => []},
    conditionTypes: {type: Array, default: () => []},
    designations: {type: Array, default: () => []},
    employees: {type: Array, default: () => []},
    errors: {type: Object, default: () => ({})},
    processing: {type: Boolean, default: false},
    title: {type: String, default: 'Approval Workflow'},
    submitLabel: {type: String, default: 'Save'},
});

const emit = defineEmits(['update:name', 'update:companyId', 'submit']);

const nameProxy = computed({get: () => props.name, set: v => emit('update:name', v)});
const companyProxy = computed({get: () => props.companyId, set: v => emit('update:companyId', v)});

const stepsModel = computed({
    get: () => props.steps,
    set: (val) => props.steps.splice(0, props.steps.length, ...val),
});

const selectedIndex = ref(null);
const selectedStep = computed(() => selectedIndex.value === null ? null : (props.steps[selectedIndex.value] ?? null));

const makeStep = () => ({
    approver_type: 'direct_manager',
    approver_value: null,
    is_mandatory: true,
    condition_type: 'always',
    condition_value: null,
});

const selectNode = (index) => { selectedIndex.value = index; };
const closeDrawer = () => { selectedIndex.value = null; };

const addStep = () => {
    props.steps.push(makeStep());
    selectedIndex.value = props.steps.length - 1;
};

const insertStep = (index) => {
    props.steps.splice(index, 0, makeStep());
    selectedIndex.value = index;
};

const removeStep = (index) => {
    if (props.steps.length <= 1) return;
    props.steps.splice(index, 1);
    selectedIndex.value = null;
};

const stepHasError = (index) => Object.keys(props.errors).some(k => k.startsWith(`steps.${index}.`));

const requiredSteps = computed(() => props.steps.filter(s => s.is_mandatory).length);
const optionalSteps = computed(() => props.steps.filter(s => !s.is_mandatory).length);
</script>

<template>
    <v-card>
        <CardTitle
            :extra-route="{title: 'Back', route: 'approval-workflows.index', icon: 'mdi-arrow-left-bold'}"
            :title="title"
            icon="mdi-sitemap-outline"
        />
        <form @submit.prevent="$emit('submit')">
            <v-card-text>
                <v-card class="mb-4" variant="outlined">
                    <v-card-text>
                        <v-row dense>
                            <v-col cols="12" md="6">
                                <TextInput
                                    v-model="nameProxy"
                                    :error-messages="errors.name"
                                    label="Name"
                                    placeholder="e.g., Standard Leave Approval"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="companyProxy"
                                    :error-messages="errors.company_id"
                                    :items="companies"
                                    density="compact"
                                    hide-details="auto"
                                    item-title="name"
                                    item-value="id"
                                    label="Company"
                                    variant="outlined"
                                />
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <v-row>
                    <v-col cols="12" md="8">
                        <div v-if="errors.steps" class="text-error text-body-2 mb-3">{{ errors.steps }}</div>

                        <VueDraggable
                            v-model="stepsModel"
                            :animation="150"
                            class="d-flex flex-column align-center"
                            handle=".drag-handle"
                            @end="closeDrawer"
                        >
                            <div v-for="(step, index) in steps" :key="index" class="workflow-lane-item">
                                <WorkflowNode
                                    :active="selectedIndex === index"
                                    :approver-types="approverTypes"
                                    :designations="designations"
                                    :employees="employees"
                                    :has-error="stepHasError(index)"
                                    :index="index"
                                    :step="step"
                                    :total="steps.length"
                                    @delete="removeStep(index)"
                                    @select="selectNode(index)"
                                />
                                <div class="workflow-connector">
                                    <div class="workflow-line"></div>
                                    <v-btn
                                        v-if="index < steps.length - 1"
                                        class="workflow-insert"
                                        color="primary"
                                        icon="mdi-plus"
                                        size="x-small"
                                        variant="tonal"
                                        @click="insertStep(index + 1)"
                                    />
                                </div>
                            </div>
                        </VueDraggable>

                        <div class="d-flex justify-center">
                            <v-btn
                                class="text-none"
                                color="primary"
                                prepend-icon="mdi-plus"
                                size="small"
                                variant="tonal"
                                @click="addStep"
                            >
                                Add step
                            </v-btn>
                        </div>
                    </v-col>

                    <v-col cols="12" md="4">
                        <v-card class="mb-4" variant="outlined">
                            <v-toolbar class="border-b" color="transparent" density="compact">
                                <v-icon class="ml-4" size="small">mdi-chart-box-outline</v-icon>
                                <v-toolbar-title class="text-body-2 font-weight-bold">Overview</v-toolbar-title>
                            </v-toolbar>
                            <v-list density="compact">
                                <v-list-item>
                                    <v-list-item-title class="text-body-2">Total Steps</v-list-item-title>
                                    <template #append>
                                        <v-chip color="primary" label size="small" variant="tonal">{{ steps.length }}</v-chip>
                                    </template>
                                </v-list-item>
                                <v-list-item>
                                    <v-list-item-title class="text-body-2">Required</v-list-item-title>
                                    <template #append>
                                        <v-chip color="success" label size="small" variant="tonal">{{ requiredSteps }}</v-chip>
                                    </template>
                                </v-list-item>
                                <v-list-item>
                                    <v-list-item-title class="text-body-2">Optional</v-list-item-title>
                                    <template #append>
                                        <v-chip color="warning" label size="small" variant="tonal">{{ optionalSteps }}</v-chip>
                                    </template>
                                </v-list-item>
                            </v-list>
                        </v-card>

                        <v-alert class="text-caption" density="compact" type="info" variant="tonal">
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
                    :loading="processing"
                    class="text-none"
                    color="primary"
                    prepend-icon="mdi-check"
                    type="submit"
                    variant="flat"
                >
                    {{ submitLabel }}
                </v-btn>
            </v-card-actions>
        </form>
    </v-card>

    <v-navigation-drawer
        :model-value="selectedIndex !== null"
        location="right"
        temporary
        width="360"
        @update:model-value="val => { if (!val) closeDrawer(); }"
    >
        <WorkflowNodeDrawer
            :key="selectedIndex"
            :approver-types="approverTypes"
            :can-delete="steps.length > 1"
            :condition-types="conditionTypes"
            :designations="designations"
            :employees="employees"
            :errors="errors"
            :index="selectedIndex ?? -1"
            :step="selectedStep"
            @close="closeDrawer"
            @delete="removeStep(selectedIndex)"
        />
    </v-navigation-drawer>
</template>

<style scoped>
.workflow-lane-item {
    width: 100%;
    max-width: 520px;
}

.workflow-connector {
    position: relative;
    height: 26px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.workflow-line {
    width: 2px;
    height: 100%;
    background: rgba(0, 0, 0, 0.12);
}

.workflow-insert {
    position: absolute;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.workflow-connector:hover .workflow-insert {
    opacity: 1;
}
</style>
