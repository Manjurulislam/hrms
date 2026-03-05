<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head, Link} from '@inertiajs/vue3';
import {reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Employee', key: 'employee'},
        {title: 'Emp ID', key: 'emp_id'},
        {title: 'Leave Type', key: 'leave_type'},
        {title: 'Start Date', key: 'started_at'},
        {title: 'End Date', key: 'ended_at'},
        {title: 'Days', key: 'total_days'},
        {title: 'Status', key: 'status', sortable: false},
        {title: 'Actions', key: 'actions', sortable: false, width: '5%'},
    ],
    serverItems: [],
    loading: true,
});

const loadData = () => {
    state.loading = true;
    axios.get(route('emp-leave.approvals.get')).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
    });
};

loadData();

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

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'});
};
</script>

<template>
    <DefaultLayout>
        <Head title="Pending Approvals"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle icon="mdi-clipboard-check-outline" title="Pending Approvals"/>

                    <v-card-text>
                        <v-data-table
                            :headers="state.headers"
                            :items="state.serverItems"
                            :loading="state.loading"
                            density="compact"
                            item-value="id"
                        >
                            <template v-slot:item.id="{ index }">
                                {{ index + 1 }}
                            </template>
                            <template v-slot:item.employee="{ item }">
                                {{ item.employee?.first_name }} {{ item.employee?.last_name }}
                            </template>
                            <template v-slot:item.emp_id="{ item }">
                                {{ item.employee?.id_no || '-' }}
                            </template>
                            <template v-slot:item.leave_type="{ item }">
                                {{ item.leave_type?.name || '-' }}
                            </template>
                            <template v-slot:item.started_at="{ item }">
                                {{ formatDate(item.started_at) }}
                            </template>
                            <template v-slot:item.ended_at="{ item }">
                                {{ formatDate(item.ended_at) }}
                            </template>
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
                                <Link :href="route('emp-leave.approvals.show', item.id)">
                                    <v-btn
                                        color="primary"
                                        icon="mdi-eye"
                                        size="x-small"
                                        variant="text"
                                    />
                                </Link>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
