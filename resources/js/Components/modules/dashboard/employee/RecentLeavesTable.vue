<script setup>
import {Link} from '@inertiajs/vue3';

defineProps({
    items: Array,
});

const headers = [
    {title: 'Type', key: 'leave_type'},
    {title: 'From', key: 'started_at'},
    {title: 'To', key: 'ended_at'},
    {title: 'Days', key: 'total_days'},
    {title: 'Status', key: 'status', sortable: false},
];

const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'in_review': 'info',
        'approved': 'success',
        'rejected': 'error',
        'cancelled': 'grey',
    };
    return colors[status] || 'default';
};

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'Pending',
        'in_review': 'In Review',
        'approved': 'Approved',
        'rejected': 'Rejected',
        'cancelled': 'Cancelled',
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
                    <span class="text-h6 font-weight-medium">Recent Leave Requests</span>
                </div>
                <Link :href="route('emp-leave.index')">
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
                <template v-slot:no-data>
                    <div class="text-center text-medium-emphasis py-4">No leave requests yet</div>
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
