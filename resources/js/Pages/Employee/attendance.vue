<script setup>
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import AttendantRecords from "@/Components/modules/employee/attendance/attendantRecords.vue";
import TrackerClock from "@/Components/modules/employee/attendance/trackerClock.vue";
import EmpPerformance from "@/Components/modules/employee/attendance/empPerformance.vue";
import TeamMembers from "@/Components/modules/employee/attendance/teamMembers.vue";

import {ref, onMounted} from 'vue'
import {Head, router} from "@inertiajs/vue3";

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
    performance: {
        type: Object,
        required: true
    },
    holidays: {
        type: Array,
        default: () => []
    },
    teamToday: {
        type: Object,
        default: null
    },
    todayData: {
        type: Object,
        default: null
    }
})

const recordsRef = ref(null)

// Resolve the public client IP once, then share it with the tracker + performance cards
// (server only sees the proxy/LAN IP). Avoids each component fetching it separately.
const clientIp = ref(null)

onMounted(async () => {
    try {
        const res = await fetch('https://api.ipify.org?format=json')
        clientIp.value = (await res.json()).ip || null
    } catch (e) {
        clientIp.value = null
    }
})

const onAttendanceChanged = () => {
    router.reload({ only: ['todayData', 'performance', 'teamToday'] })
    recordsRef.value?.reload()
}
</script>

<template>
    <DefaultLayout>
        <Head title="Attendance"/>

        <div class="attendance">
            <!-- Row 1: Performance (left) + Clock hero (right); hero first on mobile -->
            <v-row>
                <v-col cols="12" md="6" class="d-none d-md-block">
                    <emp-performance :performance="performance" :office-hours="officeHours" :holidays="holidays" :client-ip="clientIp"/>
                </v-col>
                <v-col cols="12" md="6" order="first" order-md="last">
                    <tracker-clock
                        :office-hours="officeHours"
                        :today-data="todayData"
                        :client-ip="clientIp"
                        @work-started="onAttendanceChanged"
                        @work-ended="onAttendanceChanged"
                    />
                </v-col>
            </v-row>

            <!-- Row 2: My Attendance (left) + Team Members (right, managers only) -->
            <v-row>
                <v-col cols="12" :md="teamToday ? 6 : 12">
                    <AttendantRecords
                        ref="recordsRef"
                        :can-manage="false"
                        :employee-id="userInfo.employeeId"
                    />
                </v-col>
                <v-col v-if="teamToday" cols="12" md="6">
                    <team-members :team="teamToday"/>
                </v-col>
            </v-row>
        </div>
    </DefaultLayout>
</template>
