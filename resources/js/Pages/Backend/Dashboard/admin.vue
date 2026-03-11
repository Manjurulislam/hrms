<script setup>
import {Head} from '@inertiajs/vue3';
import {ref, watch} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import StatCards from '@/Components/modules/dashboard/company/StatCards.vue';
import AttendanceStatesChart from '@/Components/modules/dashboard/company/AttendanceStatesChart.vue';
import DepartmentAttendanceChart from '@/Components/modules/dashboard/company/DepartmentAttendanceChart.vue';

import RecentAttendanceTable from '@/Components/modules/dashboard/company/RecentAttendanceTable.vue';
import PendingLeavesTable from '@/Components/modules/dashboard/company/PendingLeavesTable.vue';

const props = defineProps({
    stats: Object,
    monthlyAttendance: Array,
    departmentAttendance: Array,

    recentAttendance: Array,
    pendingLeaves: Array,
    companies: Array,
    defaultCompanyId: Number,
});

const selectedCompanyId = ref(props.defaultCompanyId);
const data = ref({
    stats: props.stats,
    monthlyAttendance: props.monthlyAttendance,
    departmentAttendance: props.departmentAttendance,

    recentAttendance: props.recentAttendance,
    pendingLeaves: props.pendingLeaves,
});
const loading = ref(false);

const loadData = () => {
    loading.value = true;
    const params = {};
    if (selectedCompanyId.value) {
        params.company_id = selectedCompanyId.value;
    }
    axios.get(route('dashboard.data', params)).then(({data: res}) => {
        data.value = res;
    }).catch(() => {}).finally(() => {
        loading.value = false;
    });
};

watch(selectedCompanyId, loadData);
</script>

<template>
    <DefaultLayout>
        <Head title="Dashboard"/>

        <div v-if="companies.length > 1" class="d-flex justify-end mb-3">
            <v-select
                v-model="selectedCompanyId"
                :items="companies"
                density="compact"
                hide-details
                item-title="name"
                item-value="id"
                label="Company"
                style="max-width: 250px"
                variant="outlined"
            />
        </div>

        <StatCards :stats="data.stats"/>

        <v-row class="mt-2" dense>
            <v-col cols="12" md="6">
                <RecentAttendanceTable :items="data.recentAttendance"/>
            </v-col>
            <v-col cols="12" md="6">
                <PendingLeavesTable :items="data.pendingLeaves"/>
            </v-col>
        </v-row>

        <v-row class="mt-2" dense>
            <v-col cols="12" md="6">
                <DepartmentAttendanceChart :data="data.departmentAttendance"/>
            </v-col>
            <v-col cols="12" md="6">
                <AttendanceStatesChart :data="data.monthlyAttendance"/>
            </v-col>
        </v-row>

    </DefaultLayout>
</template>
