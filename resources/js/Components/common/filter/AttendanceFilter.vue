<template>
    <v-card-title class="border-b pa-0">
        <v-expansion-panels variant="accordion">
            <v-expansion-panel>
                <v-expansion-panel-title class="bg-grey-lighten-4">
                    <div class="d-flex align-center">
                        <v-icon class="mr-2" color="primary">mdi-filter-variant</v-icon>
                        <span class="font-weight-medium">Filters</span>
                        <v-chip v-if="activeFilterCount > 0" class="ml-3" color="primary" size="x-small" variant="tonal">
                            {{ activeFilterCount }} active
                        </v-chip>
                    </div>
                </v-expansion-panel-title>
                <v-expansion-panel-text class="pt-4">
                    <v-row dense>
                        <v-col cols="12" md="2">
                            <v-text-field
                                v-model="filters.search"
                                clearable
                                density="compact"
                                hide-details
                                label="Search"
                                placeholder="Name, email, phone or Emp ID"
                                prepend-inner-icon="mdi-magnify"
                                variant="outlined"
                                @keyup.enter="$emit('refresh')"
                                @click:clear="$emit('refresh')"
                            />
                        </v-col>
                        <v-col cols="12" md="2">
                            <v-select
                                v-model="filters.company_id"
                                :items="companies"
                                clearable
                                density="compact"
                                hide-details
                                item-title="name"
                                item-value="id"
                                label="Company"
                                variant="outlined"
                                @update:model-value="$emit('refresh')"
                            />
                        </v-col>
                        <v-col cols="12" md="2">
                            <v-select
                                v-model="filters.department_id"
                                :items="departments"
                                clearable
                                density="compact"
                                hide-details
                                item-title="name"
                                item-value="id"
                                label="Department"
                                variant="outlined"
                                @update:model-value="$emit('refresh')"
                            />
                        </v-col>
                        <v-col cols="12" md="2">
                            <v-select
                                v-model="filters.designation_id"
                                :items="designations"
                                clearable
                                density="compact"
                                hide-details
                                item-title="title"
                                item-value="id"
                                label="Designation"
                                variant="outlined"
                                @update:model-value="$emit('refresh')"
                            />
                        </v-col>
                        <v-col cols="12" md="2">
                            <v-btn-toggle
                                :model-value="filterMode"
                                color="primary"
                                density="compact"
                                mandatory
                                variant="outlined"
                            >
                                <v-btn size="small" value="date" @click="$emit('switchMode', 'date')">Date</v-btn>
                                <v-btn size="small" value="month" @click="$emit('switchMode', 'month')">Month</v-btn>
                            </v-btn-toggle>
                        </v-col>
                        <v-col cols="12" md="2">
                            <el-date-picker
                                v-if="filterMode === 'date'"
                                v-model="filters.date"
                                format="YYYY-MM-DD"
                                placeholder="Select date"
                                size="large"
                                style="width: 100%"
                                type="date"
                                value-format="YYYY-MM-DD"
                                @change="$emit('refresh')"
                            />
                            <el-date-picker
                                v-else
                                v-model="filters.month"
                                format="YYYY-MM"
                                placeholder="Select month"
                                size="large"
                                style="width: 100%"
                                type="month"
                                value-format="YYYY-MM"
                                @change="$emit('refresh')"
                            />
                        </v-col>
                        <v-col cols="12" md="2">
                            <v-select
                                v-model="filters.status"
                                :items="statusOptions"
                                clearable
                                density="compact"
                                hide-details
                                item-title="label"
                                item-value="value"
                                label="Status"
                                variant="outlined"
                                @update:model-value="$emit('refresh')"
                            />
                        </v-col>
                    </v-row>
                </v-expansion-panel-text>
            </v-expansion-panel>
        </v-expansion-panels>
    </v-card-title>
</template>

<script setup>
import {computed} from 'vue';

const props = defineProps({
    filters: {
        type: Object,
        required: true
    },
    filterMode: {
        type: String,
        default: 'date'
    },
    companies: {
        type: Array,
        default: () => []
    },
    departments: {
        type: Array,
        default: () => []
    },
    designations: {
        type: Array,
        default: () => []
    },
    statusOptions: {
        type: Array,
        default: () => []
    }
});

defineEmits(['refresh', 'switchMode']);

const activeFilterCount = computed(() => {
    let count = 0;
    if (props.filters.search) count++;
    if (props.filters.company_id) count++;
    if (props.filters.department_id) count++;
    if (props.filters.designation_id) count++;
    if (props.filters.status !== null && props.filters.status !== undefined && props.filters.status !== '') count++;
    return count;
});
</script>
