<template>
    <v-card-title class="border-b pa-0">
        <v-expansion-panels variant="accordion">
            <v-expansion-panel>
                <v-expansion-panel-title class="bg-grey-lighten-4">
                    <div class="d-flex align-center">
                        <v-icon class="mr-2" color="primary">mdi-magnify</v-icon>
                        <span class="font-weight-medium">Search</span>
                        <v-chip v-if="activeFilterCount > 0" class="ml-3" color="primary" size="x-small" variant="tonal">
                            {{ activeFilterCount }} active
                        </v-chip>
                    </div>
                </v-expansion-panel-title>
                <v-expansion-panel-text class="pt-4">
                    <v-row>
                        <v-col cols="12" lg="3" md="6">
                            <label class="text-caption font-weight-medium mb-1 d-block">Search</label>
                            <el-input
                                v-model="filters.search"
                                clearable
                                placeholder="Search by name, email, code..."
                                style="width: 100%"
                            >
                                <template #prefix>
                                    <v-icon size="small">mdi-magnify</v-icon>
                                </template>
                            </el-input>
                        </v-col>

                        <v-col cols="12" lg="3" md="6">
                            <label class="text-caption font-weight-medium mb-1 d-block">Status</label>
                            <el-select
                                v-model="filters.status"
                                clearable
                                placeholder="All Status"
                                style="width: 100%"
                            >
                                <el-option :value="1" label="Active"/>
                                <el-option :value="0" label="Inactive"/>
                            </el-select>
                        </v-col>

                        <v-col cols="12">
                            <v-divider class="my-2"/>
                            <div class="d-flex justify-center ga-2">
                                <v-btn
                                    color="error"
                                    prepend-icon="mdi-brush-outline"
                                    size="small"
                                    variant="outlined"
                                    @click="clearFilter"
                                >
                                    Clear All
                                </v-btn>
                                <v-btn
                                    class="bg-secondary"
                                    color="darkText"
                                    prepend-icon="mdi-magnify"
                                    size="small"
                                    @click.prevent="handleSearch"
                                >
                                    Search
                                </v-btn>
                            </div>
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
});

const emit = defineEmits(['handleFilter']);

const activeFilterCount = computed(() => {
    let count = 0;
    if (props.filters.search) count++;
    if (props.filters.status !== null && props.filters.status !== undefined && props.filters.status !== '') count++;
    return count;
});

const handleSearch = () => {
    emit('handleFilter', props.filters);
};

const clearFilter = () => {
    emit('handleFilter', {...props.filters, search: '', status: null});
};
</script>

<style scoped>
:deep(.el-input__wrapper) {
    border-radius: 4px;
}
</style>
