<script setup>
import {Head, Link} from '@inertiajs/vue3';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import EmpStatCards from '@/Components/modules/dashboard/employee/EmpStatCards.vue';
import WeeklyHoursChart from '@/Components/modules/dashboard/employee/WeeklyHoursChart.vue';
import LeaveBalanceCards from '@/Components/modules/dashboard/employee/LeaveBalanceCards.vue';
import RecentLeavesTable from '@/Components/modules/dashboard/employee/RecentLeavesTable.vue';

defineProps({
    stats: Object,
    leaveBalances: Array,
    recentLeaves: Array,
    weeklyHours: Array,
    employeeName: String,
    designation: String,
    department: String,
});
</script>

<template>
    <Head title="Dashboard"/>
    <DefaultLayout>
        <!-- Welcome Banner -->
        <v-card class="welcome-card mb-4" elevation="4">
            <v-card-text class="pa-4">
                <v-row align="center" no-gutters>
                    <v-col cols="12" sm="auto" class="flex-grow-1">
                        <div class="text-subtitle-1 text-sm-h5 font-weight-bold text-grey-darken-3">
                            Welcome back, {{ employeeName }}
                        </div>
                        <div class="text-caption text-sm-body-2 text-grey-darken-1 mt-1">
                            {{ designation }} &bull; {{ department }}
                        </div>
                    </v-col>
                    <v-col cols="12" sm="auto" class="mt-3 mt-sm-0">
                        <div class="d-flex ga-2 flex-wrap">
                            <Link :href="route('emp-attendance.index')">
                                <v-btn
                                    color="primary"
                                    prepend-icon="mdi-clock-outline"
                                    size="small"
                                    variant="flat"
                                >
                                    Attendance
                                </v-btn>
                            </Link>
                            <Link :href="route('emp-leave.create')">
                                <v-btn
                                    color="teal"
                                    prepend-icon="mdi-calendar-plus"
                                    size="small"
                                    variant="flat"
                                >
                                    Apply Leave
                                </v-btn>
                            </Link>
                        </div>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <!-- Stat Cards -->
        <EmpStatCards :stats="stats"/>

        <!-- Charts + Leave Balances -->
        <v-row class="mt-2" dense>
            <v-col cols="12" md="7">
                <WeeklyHoursChart :data="weeklyHours"/>
            </v-col>
            <v-col cols="12" md="5">
                <LeaveBalanceCards :balances="leaveBalances"/>
            </v-col>
        </v-row>

        <!-- Recent Leaves -->
        <v-row class="mt-2" dense>
            <v-col cols="12">
                <RecentLeavesTable :items="recentLeaves"/>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>

<style scoped>
.welcome-card {
    border-radius: 12px !important;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
}
</style>
