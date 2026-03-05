<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head, Link} from '@inertiajs/vue3';
import {reactive} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const toast = useToast();

const props = defineProps({
    companies: Array,
    departments: Array,
    employees: Array,
    statusOptions: Array,
    defaultCompanyId: Number,
});

const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Employee', key: 'employee_name'},
        {title: 'Emp ID', key: 'emp_id'},
        {title: 'Department', key: 'department_name'},
        {title: 'Date', key: 'attendance_date_display'},
        {title: 'Day', key: 'day'},
        {title: 'Check In', key: 'first_check_in_display'},
        {title: 'Check Out', key: 'last_check_out_display'},
        {title: 'Working', key: 'working_hours'},
        {title: 'Break', key: 'break_hours'},
        {title: 'Sessions', key: 'total_sessions'},
        {title: 'Status', key: 'status_label', sortable: false},
        {title: 'Actions', key: 'actions', sortable: false, width: '5%'},
    ],
    pagination: {
        itemsPerPage: 50,
        totalItems: 0
    },
    filters: {
        search: '',
        date: [new Date().getFullYear(), String(new Date().getMonth() + 1).padStart(2, '0'), String(new Date().getDate()).padStart(2, '0')].join('-'),
        month: null,
        company_id: props.defaultCompanyId,
        department_id: null,
        employee_id: null,
        status: null,
        per_page: 50
    },
    serverItems: [],
    loading: true,
    filterMode: 'date',
});

const setLimit = (obj) => {
    const {page, itemsPerPage, sortBy} = obj;
    state.filters.page = page;
    state.filters.sort = sortBy;
    state.filters.per_page = itemsPerPage === 'All' ? -1 : itemsPerPage;
};

const getData = (obj) => {
    setLimit(obj);
    const params = {...state.filters};

    if (state.filterMode === 'date') {
        delete params.month;
    } else {
        delete params.date;
    }

    axios.get(route('attendance.get', params)).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    });
};

const refreshData = () => {
    getData({page: 1, itemsPerPage: state.pagination.itemsPerPage, sortBy: []});
};

const switchFilterMode = (mode) => {
    state.filterMode = mode;
    if (mode === 'date') {
        state.filters.month = null;
        if (!state.filters.date) {
            state.filters.date = new Date().toISOString().slice(0, 10);
        }
    } else {
        state.filters.date = null;
        if (!state.filters.month) {
            const now = new Date();
            state.filters.month = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
        }
    }
    refreshData();
};

const exportData = () => {
    if (state.pagination.totalItems === 0) {
        toast.warning('No data available to export.');
        return;
    }
    const params = {...state.filters};
    delete params.page;
    delete params.sort;
    delete params.per_page;

    if (state.filterMode === 'date') {
        delete params.month;
    } else {
        delete params.date;
    }

    window.location.href = route('attendance.export', params);
};

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
    <DefaultLayout>
        <Head title="Attendance"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle icon="mdi-clock-outline" title="Attendance">
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

                    <v-card-text>
                        <v-row class="mb-4" dense>
                            <v-col cols="12" md="2">
                                <v-btn-toggle
                                    :model-value="state.filterMode"
                                    color="primary"
                                    density="compact"
                                    mandatory
                                    variant="outlined"
                                >
                                    <v-btn size="small" value="date" @click="switchFilterMode('date')">Date</v-btn>
                                    <v-btn size="small" value="month" @click="switchFilterMode('month')">Month</v-btn>
                                </v-btn-toggle>
                            </v-col>
                            <v-col cols="12" md="2">
                                <el-date-picker
                                    v-if="state.filterMode === 'date'"
                                    v-model="state.filters.date"
                                    format="YYYY-MM-DD"
                                    placeholder="Select date"
                                    size="large"
                                    style="width: 100%"
                                    type="date"
                                    value-format="YYYY-MM-DD"
                                    @change="refreshData"
                                />
                                <el-date-picker
                                    v-else
                                    v-model="state.filters.month"
                                    format="YYYY-MM"
                                    placeholder="Select month"
                                    size="large"
                                    style="width: 100%"
                                    type="month"
                                    value-format="YYYY-MM"
                                    @change="refreshData"
                                />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-select
                                    v-model="state.filters.company_id"
                                    :items="companies"
                                    clearable
                                    density="compact"
                                    hide-details
                                    item-title="name"
                                    item-value="id"
                                    label="Company"
                                    variant="outlined"
                                    @update:model-value="refreshData"
                                />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-select
                                    v-model="state.filters.department_id"
                                    :items="departments"
                                    clearable
                                    density="compact"
                                    hide-details
                                    item-title="name"
                                    item-value="id"
                                    label="Department"
                                    variant="outlined"
                                    @update:model-value="refreshData"
                                />
                            </v-col>
                            <v-col cols="12" md="2">
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
                            :search="state.filters.search"
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
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.emp_id="{ item }">
                                {{ item.employee?.id_no || '-' }}
                            </template>
                            <template v-slot:item.department_name="{ item }">
                                <v-chip v-if="item.department" class="font-weight-regular" color="primary" size="x-small" variant="tonal">
                                    {{ item.department.name }}
                                </v-chip>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.status_label="{ item }">
                                <v-chip
                                    :color="getStatusColor(item.status_label)"
                                    class="font-weight-regular"
                                    size="x-small"
                                    variant="tonal"
                                >
                                    {{ item.status_label }}
                                </v-chip>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <Link :href="route('attendance.show', item.employee_id)">
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
