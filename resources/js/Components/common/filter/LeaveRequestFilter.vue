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
                        <v-col cols="12" md="3">
                            <v-text-field
                                v-model="filters.search"
                                clearable
                                density="compact"
                                hide-details
                                label="Search"
                                placeholder="Name, phone or Emp ID"
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
                                v-model="filters.leave_type_id"
                                :items="leaveTypes"
                                clearable
                                density="compact"
                                hide-details
                                item-title="name"
                                item-value="id"
                                label="Leave Type"
                                variant="outlined"
                                @update:model-value="$emit('refresh')"
                            />
                        </v-col>
                        <v-col cols="12" md="3">
                            <el-date-picker
                                v-model="dateRange"
                                class="lr-date-range"
                                end-placeholder="To"
                                range-separator="–"
                                size="large"
                                start-placeholder="From"
                                style="width: 100%"
                                type="daterange"
                                value-format="YYYY-MM-DD"
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
    companies: {
        type: Array,
        default: () => []
    },
    leaveTypes: {
        type: Array,
        default: () => []
    },
    statusOptions: {
        type: Array,
        default: () => []
    }
});

defineEmits(['refresh']);

const dateRange = computed({
    get: () => {
        const {date_from, date_to} = props.filters;
        return date_from && date_to ? [date_from, date_to] : [];
    },
    set: (val) => {
        props.filters.date_from = val?.[0] ?? null;
        props.filters.date_to = val?.[1] ?? null;
    }
});

const activeFilterCount = computed(() => {
    let count = 0;
    if (props.filters.search) count++;
    if (props.filters.company_id) count++;
    if (props.filters.leave_type_id) count++;
    if (props.filters.date_from || props.filters.date_to) count++;
    if (props.filters.status !== null && props.filters.status !== undefined && props.filters.status !== '') count++;
    return count;
});
</script>
