<script setup>
import {computed} from 'vue';

const props = defineProps({
    step: {type: Object, required: true},
    index: {type: Number, required: true},
    total: {type: Number, required: true},
    active: {type: Boolean, default: false},
    hasError: {type: Boolean, default: false},
    approverTypes: {type: Array, default: () => []},
    designations: {type: Array, default: () => []},
    employees: {type: Array, default: () => []},
});

defineEmits(['select', 'delete']);

const approverTypeIcons = {
    direct_manager: 'mdi-account-tie',
    designation_level: 'mdi-badge-account-horizontal',
    specific_employee: 'mdi-account-check',
    department_head: 'mdi-account-supervisor',
};

const icon = computed(() => approverTypeIcons[props.step.approver_type] || 'mdi-account');

const typeLabel = computed(() => {
    const found = props.approverTypes.find(t => t.value === props.step.approver_type);
    return found ? found.label : props.step.approver_type;
});

const resolvedValue = computed(() => {
    if (props.step.approver_type === 'designation_level') {
        const d = props.designations.find(d => d.id === props.step.approver_value);
        return d ? `${d.title} (Level ${d.level})` : 'Not set';
    }
    if (props.step.approver_type === 'specific_employee') {
        const e = props.employees.find(e => e.id === props.step.approver_value);
        return e ? `${e.first_name} ${e.last_name}` : 'Not set';
    }
    return null;
});

const conditionText = computed(() => {
    if (props.step.condition_type === 'days_greater_than') return `> ${props.step.condition_value ?? '?'} days`;
    if (props.step.condition_type === 'days_less_than') return `< ${props.step.condition_value ?? '?'} days`;
    return 'Always';
});
</script>

<template>
    <v-card
        :color="hasError ? 'error' : undefined"
        :variant="active ? 'tonal' : 'outlined'"
        class="workflow-node"
        @click="$emit('select')"
    >
        <div class="d-flex align-center pa-2 ga-2">
            <v-icon class="drag-handle" color="medium-emphasis" size="18" style="cursor: grab" @click.stop>
                mdi-drag-vertical
            </v-icon>
            <v-avatar size="26" color="primary" variant="tonal">
                <span class="text-caption font-weight-bold">{{ index + 1 }}</span>
            </v-avatar>
            <v-icon size="16" color="medium-emphasis">{{ icon }}</v-icon>
            <div class="flex-grow-1" style="min-width: 0">
                <div class="text-body-2 font-weight-medium">{{ typeLabel }}</div>
                <div v-if="resolvedValue" class="text-caption text-medium-emphasis text-truncate">{{ resolvedValue }}</div>
            </div>
            <v-chip size="x-small" variant="outlined" label>{{ conditionText }}</v-chip>
            <v-chip :color="step.is_mandatory ? 'success' : 'warning'" size="x-small" variant="outlined" label>
                {{ step.is_mandatory ? 'Required' : 'Optional' }}
            </v-chip>
            <v-btn
                :disabled="total <= 1"
                color="error"
                icon="mdi-trash-can-outline"
                size="x-small"
                variant="text"
                @click.stop="$emit('delete')"
            />
        </div>
    </v-card>
</template>
