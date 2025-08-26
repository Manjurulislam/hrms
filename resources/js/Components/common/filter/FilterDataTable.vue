<template>
    <v-card-title class="border-b">
        <v-row>
            <v-col :cols="dateSearch ? 5 : 10">
                <el-input v-model="filters.search" placeholder="Search..." style="width: 100%"/>
            </v-col>
            <v-col v-if="dateSearch" cols="5">
                <el-date-picker
                    v-model="filters.dateSearch"
                    end-placeholder="End date"
                    range-separator="To"
                    size="default"
                    start-placeholder="Start date"
                    style="width: 100%"
                    type="daterange"
                    value-format="YYYY-MM-DD"
                />
            </v-col>

            <v-col cols="2">
                <v-btn class="bg-secondary" color="darkText" density="default" size="small"
                       @click.prevent="handleSearch">
                    <i aria-hidden="true" class="mdi mdi-magnify"></i>
                </v-btn>
                <v-btn class="ml-1" color="primary" density="default" size="small" @click="clearFilter">
                    <i aria-hidden="true" class="mdi mdi-brush-outline"></i>
                </v-btn>
              <v-btn color="error" size="small" class="ml-1" density="default" @click="toggleCheckbox">
                <v-icon>{{ filters.isChecked ? 'mdi mdi-restore' : 'mdi-trash-can' }}</v-icon>
              </v-btn>
            </v-col>
        </v-row>
    </v-card-title>
</template>

<script setup>
const props = defineProps({
    clear: String,
    filters: {
        type: Object,
        required: true
    },
    dateSearch: Boolean,
});

const emit = defineEmits(['handleFilter']);

const handleSearch = () => {
    emit('handleFilter', props.filters);
};

const toggleCheckbox = () => {
    props.filters.isChecked = !props.filters.isChecked;
    emit('handleFilter', props.filters);
};

const clearFilter = () => {
    props.filters.search = '';
    props.filters.dateSearch = '';
    emit('handleFilter', props.filters);
};
</script>

<style scoped>
</style>
