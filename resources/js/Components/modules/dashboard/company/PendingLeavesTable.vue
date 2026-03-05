<script setup>
import {Link} from '@inertiajs/vue3';

defineProps({
    items: Array,
});

const headers = [
    {title: 'Employee', key: 'employee_name'},
    {title: 'Type', key: 'leave_type'},
    {title: 'Days', key: 'total_days'},
    {title: 'From', key: 'started_at'},
    {title: 'Status', key: 'status', sortable: false},
    {title: '', key: 'actions', sortable: false, width: '5%'},
];

const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'in_review': 'info',
    };
    return colors[status] || 'default';
};

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'Pending',
        'in_review': 'In Review',
    };
    return labels[status] || status;
};
</script>

<template>
    <v-card class="dashboard-card" elevation="4">
        <v-card-title class="pa-4 pb-2">
            <div class="d-flex align-center justify-space-between">
                <div class="d-flex align-center">
                    <v-icon class="me-2" color="teal">mdi-file-document-outline</v-icon>
                    <span class="text-subtitle-1 font-weight-light">Leave Requests</span>
                </div>
                <Link :href="route('leave-requests.index')">
                    <v-btn
                        append-icon="mdi-arrow-right"
                        color="teal"
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
                <template v-slot:item.status="{ item }">
                    <v-chip
                        :color="getStatusColor(item.status)"
                        class="font-weight-regular"
                        size="x-small"
                        variant="tonal"
                    >
                        {{ getStatusLabel(item.status) }}
                    </v-chip>
                </template>
                <template v-slot:item.actions="{ item }">
                    <Link :href="route('leave-requests.show', item.id)">
                        <v-icon color="primary" size="small">mdi-eye</v-icon>
                    </Link>
                </template>
                <template v-slot:no-data>
                    <div class="text-center text-medium-emphasis py-4">No pending leave requests</div>
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
