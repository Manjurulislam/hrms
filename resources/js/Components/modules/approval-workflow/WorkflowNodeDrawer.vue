<script setup>
import {watch} from 'vue';

const props = defineProps({
    step: {type: Object, default: null},
    index: {type: Number, default: -1},
    approverTypes: {type: Array, default: () => []},
    conditionTypes: {type: Array, default: () => []},
    designations: {type: Array, default: () => []},
    employees: {type: Array, default: () => []},
    errors: {type: Object, default: () => ({})},
    canDelete: {type: Boolean, default: true},
});

defineEmits(['close', 'delete']);

const errorFor = (field) => props.errors[`steps.${props.index}.${field}`];

const needsConditionValue = (type) => ['days_greater_than', 'days_less_than'].includes(type);

// Reset approver_value whenever the approver type changes (clears stale value and
// switches cleanly between designation <-> employee).
watch(() => props.step?.approver_type, (type, old) => {
    if (!props.step || type === old) return;
    props.step.approver_value = null;
});

watch(() => props.step?.condition_type, (type, old) => {
    if (!props.step || type === old) return;
    if (!needsConditionValue(type)) props.step.condition_value = null;
});
</script>

<template>
    <div v-if="step" class="pa-4">
        <div class="d-flex align-center justify-space-between mb-3">
            <span class="text-subtitle-2 font-weight-bold">Step {{ index + 1 }}</span>
            <v-btn icon="mdi-close" size="small" variant="text" @click="$emit('close')"/>
        </div>

        <v-select
            v-model="step.approver_type"
            :error-messages="errorFor('approver_type')"
            :items="approverTypes"
            class="mb-3"
            density="compact"
            hide-details="auto"
            item-title="label"
            item-value="value"
            label="Approver Type"
            variant="outlined"
        />

        <v-select
            v-if="step.approver_type === 'designation_level'"
            v-model="step.approver_value"
            :error-messages="errorFor('approver_value')"
            :item-title="d => `${d.title} (Level ${d.level})`"
            :items="designations"
            class="mb-3"
            clearable
            density="compact"
            hide-details="auto"
            item-value="id"
            label="Designation"
            variant="outlined"
        />

        <v-autocomplete
            v-else-if="step.approver_type === 'specific_employee'"
            v-model="step.approver_value"
            :error-messages="errorFor('approver_value')"
            :item-title="e => `${e.first_name} ${e.last_name}`"
            :items="employees"
            class="mb-3"
            clearable
            density="compact"
            hide-details="auto"
            item-value="id"
            label="Select Employee"
            variant="outlined"
        />

        <v-select
            v-model="step.condition_type"
            :error-messages="errorFor('condition_type')"
            :items="conditionTypes"
            class="mb-3"
            density="compact"
            hide-details="auto"
            item-title="label"
            item-value="value"
            label="Condition"
            variant="outlined"
        />

        <v-text-field
            v-if="needsConditionValue(step.condition_type)"
            v-model.number="step.condition_value"
            :error-messages="errorFor('condition_value')"
            class="mb-3"
            density="compact"
            hide-details="auto"
            label="Days"
            min="1"
            type="number"
            variant="outlined"
        />

        <div class="d-flex align-center ga-2 mb-4">
            <el-switch
                v-model="step.is_mandatory"
                size="small"
                style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"
            />
            <span :class="step.is_mandatory ? 'text-success' : 'text-warning'" class="text-body-2">
                {{ step.is_mandatory ? 'Required' : 'Optional' }}
            </span>
        </div>

        <v-btn :disabled="!canDelete" block color="error" prepend-icon="mdi-trash-can-outline" variant="outlined" @click="$emit('delete')">
            Delete Step
        </v-btn>
    </div>
</template>
