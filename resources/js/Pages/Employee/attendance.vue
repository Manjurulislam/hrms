<script setup>
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import AttendantRecords from "@/Components/modules/employee/attendance/attendantRecords.vue";
import TrackerClock from "@/Components/modules/employee/attendance/trackerClock.vue";
import EmpStats from "@/Components/modules/employee/attendance/empStats.vue";

import {computed} from 'vue'
import {Head} from "@inertiajs/vue3";

// Props from Inertia backend
const props = defineProps({
    userInfo: {
        type: Object,
        required: true
    },
    officeHours: {
        type: Object,
        required: true
    },
    monthlyStats: {
        type: Object,
        required: true
    },
    todayData: {
        type: Object,
        default: null
    },
    attendanceRecords: {
        type: Object,
        default: () => ({})
    }
})

// Computed properties based on todayData from server
const userStatus = computed(() => {
    if (props.todayData?.currentSession) return 'Working'
    if (props.todayData?.totalWorkedSeconds > 0) return 'Work Completed'
    return 'Ready to Start'
})

const statusColor = computed(() => {
    if (props.todayData?.currentSession) return 'success'
    if (props.todayData?.totalWorkedSeconds > 0) return 'info'
    return 'primary'
})
</script>

<template>
    <DefaultLayout>
        <Head title="Attendance"/>
        <v-row>
            <!-- Main Attendance Section -->
            <v-col cols="12" lg="8">
                <tracker-clock
                    :office-hours="officeHours"
                    :today-data="todayData"
                />
            </v-col>

            <!-- User Info Sidebar -->
            <v-col cols="12" lg="4">
                <emp-stats
                    :monthly-stats="monthlyStats"
                    :status-color="statusColor"
                    :user-info="userInfo"
                    :user-status="userStatus"
                />
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12">
                <AttendantRecords
                    :can-manage="false"
                    :employee-id="userInfo.employeeId"
                />
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
