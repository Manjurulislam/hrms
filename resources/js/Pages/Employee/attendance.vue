<script setup>
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import AttendantRecords from "@/Components/modules/employee/attendance/attendantRecords.vue";
import TrackerClock from "@/Components/modules/employee/attendance/trackerClock.vue";
import EmpStats from "@/Components/modules/employee/attendance/empStats.vue";

import {computed, ref} from 'vue'
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

// Emits
const emit = defineEmits(['work-started', 'work-ended', 'attendance-updated'])

// Track current attendance state from trackerClock
const isWorking = ref(false)
const endTime = ref('--:--')

// Computed properties
const userStatus = computed(() => {
    if (isWorking.value) return 'Working'
    if (endTime.value !== '--:--') return 'Work Completed'
    return 'Ready to Start'
})

const statusColor = computed(() => {
    if (isWorking.value) return 'success'
    if (endTime.value !== '--:--') return 'info'
    return 'primary'
})

// Handle events from tracker-clock
const handleWorkStarted = (data) => {
    isWorking.value = true
    emit('work-started', {...data, user: props.userInfo})
}

const handleWorkEnded = (data) => {
    isWorking.value = false
    endTime.value = data.endTime
    emit('work-ended', {...data, user: props.userInfo})
}

const handleAttendanceUpdated = (data) => {
    emit('attendance-updated', data)
}
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
                    @work-started="handleWorkStarted"
                    @work-ended="handleWorkEnded"
                    @attendance-updated="handleAttendanceUpdated"
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
        <!-- Attendance Records Component -->
        <v-row>
            <v-col cols="12">
                <AttendantRecords
                    :employee-id="userInfo.employeeId"
                    :can-manage="false"
                />
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
