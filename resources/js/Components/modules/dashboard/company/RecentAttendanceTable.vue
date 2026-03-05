<script setup>
import {Link} from '@inertiajs/vue3';

defineProps({
    items: Array,
});

const headers = [
    {title: 'Employee', key: 'employee_name'},
    {title: 'Check In', key: 'check_in'},
    {title: 'Check Out', key: 'check_out'},
    {title: 'Status', key: 'status', sortable: false},
];

const getStatusColor = (status) => {
    const colors = {
        'Present': 'success',
        'Absent': 'error',
        'Late': 'warning',
        'Half Day': 'orange',
        'Leave': 'info',
        'Holiday': 'purple',
        'Weekend': 'grey',
        'WFH': 'teal',
    };
    return colors[status] || 'default';
};
</script>

<template>
    <v-card class="dashboard-card" elevation="4">
        <v-card-title class="pa-4 pb-2">
            <div class="d-flex align-center justify-space-between">
                <div class="d-flex align-center">
                    <v-icon class="me-2" color="grey-darken-1">mdi-clipboard-list</v-icon>
                    <span class="text-subtitle-1 font-weight-light">Attendance</span>
                </div>
                <Link :href="route('attendance.index')">
                    <v-btn
                        append-icon="mdi-arrow-right"
                        color="primary"
                        size="small"
                        variant="text"
                    >
                        View All
                    </v-btn>
                </Link>
            </div>
        </v-card-title>
        <v-card-text class="pa-4 pt-2">
            <v-data-table
                :headers="headers"
                :items="items"
                density="compact"
                hide-default-footer
                items-per-page="-1"
            >
                <template v-slot:item.employee_name="{ item }">
                    <div>{{ item.employee_name }}</div>
                    <div class="text-caption text-medium-emphasis">{{ item.emp_id }}</div>
                </template>
                <template v-slot:item.status="{ item }">
                    <v-chip
                        :color="getStatusColor(item.status)"
                        class="font-weight-regular"
                        size="x-small"
                        variant="tonal"
                    >
                        {{ item.status }}
                    </v-chip>
                </template>
                <template v-slot:no-data>
                    <div class="text-center text-medium-emphasis py-4">No attendance records today</div>
                </template>
            </v-data-table>
        </v-card-text>
    </v-card>
</template>

<style scoped>
.dashboard-card {
    border-radius: 12px !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1) !important;
}
</style>
