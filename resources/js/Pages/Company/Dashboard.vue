<script setup>
import {Head} from '@inertiajs/vue3';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import StatCards from '@/Components/modules/dashboard/company/StatCards.vue';
import DepartmentAttendanceChart from '@/Components/modules/dashboard/company/DepartmentAttendanceChart.vue';
import StatusDistributionChart from '@/Components/modules/dashboard/company/StatusDistributionChart.vue';
import RecentAttendanceTable from '@/Components/modules/dashboard/company/RecentAttendanceTable.vue';
import PendingLeavesTable from '@/Components/modules/dashboard/company/PendingLeavesTable.vue';

defineProps({
    stats: Object,
    departmentAttendance: Array,
    statusDistribution: Array,
    recentAttendance: Array,
    pendingLeaves: Array,
});
</script>

<template>
    <DefaultLayout>
        <Head title="Company Dashboard"/>

        <StatCards :stats="stats"/>

        <v-row class="mt-2" dense>
            <v-col cols="12" md="7">
                <DepartmentAttendanceChart :data="departmentAttendance"/>
            </v-col>
            <v-col cols="12" md="5">
                <StatusDistributionChart :data="statusDistribution"/>
            </v-col>
        </v-row>

        <v-row class="mt-2" dense>
            <v-col cols="12" md="7">
                <RecentAttendanceTable :items="recentAttendance"/>
            </v-col>
            <v-col cols="12" md="5">
                <PendingLeavesTable :items="pendingLeaves"/>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
