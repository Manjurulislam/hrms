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
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model="filters.search"
                                clearable
                                density="compact"
                                hide-details
                                label="Search"
                                placeholder="Search by name"
                                prepend-inner-icon="mdi-magnify"
                                variant="outlined"
                                @keyup.enter="$emit('refresh')"
                                @click:clear="$emit('refresh')"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
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
    }
});

defineEmits(['refresh']);

const activeFilterCount = computed(() => {
    let count = 0;
    if (props.filters.search) count++;
    if (props.filters.company_id) count++;
    return count;
});
</script>
