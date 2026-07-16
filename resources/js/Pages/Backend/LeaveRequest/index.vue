<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import LeaveRequestFilter from '@/Components/common/filter/LeaveRequestFilter.vue';
import {Head, Link} from '@inertiajs/vue3';
import {reactive} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const toast = useToast();

const props = defineProps({
    companies: Array,
    employees: Array,
    leaveTypes: Array,
    statusOptions: Array,
    defaultCompanyId: Number,
});

const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Name', key: 'employee_name'},
        {title: 'Phone', key: 'employee_phone', sortable: false},
        {title: 'Department', key: 'employee_department', sortable: false},
        {title: 'Designation', key: 'employee_designation', sortable: false},
        {title: 'Reason', key: 'title'},
        {title: 'Leave', key: 'leave_type'},
        {title: 'Approver', key: 'current_approver'},
        {title: 'Days', key: 'total_days'},
        {title: 'Start', key: 'started_at'},
        {title: 'End', key: 'ended_at'},
        {title: 'Status', key: 'status', sortable: false},
        {title: 'Actions', key: 'actions', sortable: false, width: '5%'},
    ],
    pagination: {
        itemsPerPage: 50,
        totalItems: 0,
    },
    filters: {
        search: '',
        company_id: props.defaultCompanyId,
        leave_type_id: null,
        date_from: null,
        date_to: null,
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
    axios.get(route('leave-requests.get'), {params: state.filters}).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    });
};

const refreshData = () => {
    getData({page: 1, itemsPerPage: state.pagination.itemsPerPage, sortBy: []});
};

const exportData = () => {
    if (state.pagination.totalItems === 0) {
        toast.warning('No data available to export.');
        return;
    }
    const params = new URLSearchParams();
    if (state.filters.search) params.append('search', state.filters.search);
    if (state.filters.company_id) params.append('company_id', state.filters.company_id);
    if (state.filters.leave_type_id) params.append('leave_type_id', state.filters.leave_type_id);
    if (state.filters.date_from) params.append('date_from', state.filters.date_from);
    if (state.filters.date_to) params.append('date_to', state.filters.date_to);
    if (state.filters.status) params.append('status', state.filters.status);
    window.location.href = route('leave-requests.export') + '?' + params.toString();
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'error',
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
                    <CardTitle icon="mdi-calendar-check" title="Leave Requests">
                        <template #action>
                            <v-btn
                                class="text-none"
                                color="success"
                                prepend-icon="mdi-download"
                                size="small"
                                variant="flat"
                                @click="exportData"
                            >
                                Export
                            </v-btn>
                        </template>
                    </CardTitle>

                    <LeaveRequestFilter
                        :filters="state.filters"
                        :companies="companies"
                        :leaveTypes="leaveTypes"
                        :statusOptions="statusOptions"
                        @refresh="refreshData"
                    />

                    <v-card-text>
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
                                <span v-if="item.employee" class="font-weight-medium">{{ item.employee.first_name }} {{ item.employee.last_name }}</span>
                                <span v-else class="text-medium-emphasis">-</span>
                            </template>
                            <template v-slot:item.employee_phone="{ item }">
                                <span v-if="item.employee?.phone">{{ item.employee.phone }}</span>
                                <span v-else class="text-medium-emphasis">-</span>
                            </template>
                            <template v-slot:item.employee_department="{ item }">
                                <span v-if="item.employee?.department">{{ item.employee.department.name }}</span>
                                <span v-else class="text-medium-emphasis">-</span>
                            </template>
                            <template v-slot:item.employee_designation="{ item }">
                                <span v-if="item.employee?.designation">{{ item.employee.designation.title }}</span>
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
                                    size="x-small"
                                    variant="outlined"
                                >
                                    {{ getStatusLabel(item.status) }}
                                </v-chip>
                            </template>
                            <template v-slot:item.current_approver="{ item }">
                                <v-chip
                                    v-if="item.current_approver"
                                    class="font-weight-regular"
                                    size="x-small"
                                    variant="outlined"
                                >
                                    {{ item.current_approver.first_name }} {{ item.current_approver.last_name }}
                                </v-chip>
                                <span v-else class="text-medium-emphasis">-</span>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <Link :href="route('leave-requests.show', item.id)">
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
