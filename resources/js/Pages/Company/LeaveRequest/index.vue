<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head, Link} from '@inertiajs/vue3';
import {reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const props = defineProps({
    employees: Array,
    leaveTypes: Array,
    statusOptions: Array,
});

const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Employee', key: 'employee_name'},
        {title: 'Leave Type', key: 'leave_type'},
        {title: 'Title', key: 'title'},
        {title: 'Start Date', key: 'started_at'},
        {title: 'End Date', key: 'ended_at'},
        {title: 'Days', key: 'total_days'},
        {title: 'Status', key: 'status', sortable: false},
        {title: 'Current Approver', key: 'current_approver'},
        {title: 'Actions', key: 'actions', sortable: false, width: '5%'},
    ],
    pagination: {
        itemsPerPage: 50,
        totalItems: 0,
    },
    filters: {
        employee_id: null,
        leave_type_id: null,
        status: null,
        per_page: 50,
    },
    serverItems: [],
    loading: true,
});

const setLimit = (obj) => {
    const {page, itemsPerPage, sortBy} = obj;
    state.filters.page = page;
    state.filters.sort = sortBy;
    state.filters.per_page = itemsPerPage === 'All' ? -1 : itemsPerPage;
};

const getData = (obj) => {
    setLimit(obj);
    state.loading = true;
    axios.get(route('company.leave-requests.get'), {params: state.filters}).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    });
};

const refreshData = () => {
    getData({page: 1, itemsPerPage: state.pagination.itemsPerPage, sortBy: []});
};

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
    <DefaultLayout>
        <Head title="Leave Requests"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle icon="mdi-calendar-check" title="Leave Requests"/>

                    <v-card-text>
                        <v-row class="mb-4" dense>
                            <v-col cols="12" md="3">
                                <v-autocomplete
                                    v-model="state.filters.employee_id"
                                    :items="employees"
                                    :item-title="item => item.first_name + ' ' + (item.last_name || '')"
                                    clearable
                                    density="compact"
                                    hide-details
                                    item-value="id"
                                    label="Employee"
                                    variant="outlined"
                                    @update:model-value="refreshData"
                                />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-select
                                    v-model="state.filters.leave_type_id"
                                    :items="leaveTypes"
                                    clearable
                                    density="compact"
                                    hide-details
                                    item-title="name"
                                    item-value="id"
                                    label="Leave Type"
                                    variant="outlined"
                                    @update:model-value="refreshData"
                                />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-select
                                    v-model="state.filters.status"
                                    :items="statusOptions"
                                    clearable
                                    density="compact"
                                    hide-details
                                    item-title="label"
                                    item-value="value"
                                    label="Status"
                                    variant="outlined"
                                    @update:model-value="refreshData"
                                />
                            </v-col>
                        </v-row>

                        <v-data-table-server
                            :headers="state.headers"
                            :items="state.serverItems"
                            :items-length="state.pagination.totalItems"
                            :items-per-page="state.pagination.itemsPerPage"
                            :loading="state.loading"
                            density="compact"
                            item-value="id"
                            @update:options="getData"
                        >
                            <template v-slot:item.id="{ index }">
                                {{ index + 1 }}
                            </template>
                            <template v-slot:item.employee_name="{ item }">
                                <span v-if="item.employee">
                                    {{ item.employee.first_name }} {{ item.employee.last_name }}
                                </span>
                                <span v-else class="text-medium-emphasis">-</span>
                            </template>
                            <template v-slot:item.leave_type="{ item }">
                                {{ item.leave_type?.name || '-' }}
                            </template>
                            <template v-slot:item.started_at="{ item }">
                                {{ item.started_at ? new Date(item.started_at).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}) : '-' }}
                            </template>
                            <template v-slot:item.ended_at="{ item }">
                                {{ item.ended_at ? new Date(item.ended_at).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}) : '-' }}
                            </template>
                            <template v-slot:item.status="{ item }">
                                <v-chip
                                    :color="getStatusColor(item.status)"
                                    class="font-weight-regular"
                                    size="small"
                                    variant="tonal"
                                >
                                    {{ getStatusLabel(item.status) }}
                                </v-chip>
                            </template>
                            <template v-slot:item.current_approver="{ item }">
                                <span v-if="item.current_approver">
                                    {{ item.current_approver.first_name }} {{ item.current_approver.last_name }}
                                </span>
                                <span v-else class="text-medium-emphasis">-</span>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <Link :href="route('company.leave-requests.show', item.id)">
                                    <v-icon color="primary" size="small">mdi-eye</v-icon>
                                </Link>
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
