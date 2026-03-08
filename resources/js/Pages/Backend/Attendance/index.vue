<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head, Link} from '@inertiajs/vue3';
import {reactive, ref} from 'vue';
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

const expanded = ref([]);

const state = reactive({
    headers: [
        {title: '', key: 'data-table-expand', sortable: false, width: '40px'},
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
        'Late': 'deeporange',
        'Half Day': 'orange',
        'Leave': 'info',
        'Holiday': 'purple',
        'Weekend': 'grey',
        'WFH': 'teal',
    };
    return colors[status] || 'default';
};

const getSessionStatusColor = (status) => {
    const colors = {
        'active': 'success',
        'completed': 'primary',
        'auto_closed': 'warning',
    };
    return colors[status] || 'grey';
};

const getSessionStatusLabel = (status) => {
    const labels = {
        'active': 'Active',
        'completed': 'Completed',
        'auto_closed': 'Auto Closed',
    };
    return labels[status] || status;
};

const getBreakTypeLabel = (type) => {
    const labels = {
        'lunch': 'Lunch',
        'tea': 'Tea',
        'personal': 'Personal',
        'prayer': 'Prayer',
        'other': 'Other',
    };
    return labels[type] || type;
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
                            v-model:expanded="expanded"
                            :headers="state.headers"
                            :items="state.serverItems"
                            :items-length="state.pagination.totalItems"
                            :items-per-page="state.pagination.itemsPerPage"
                            :loading="state.loading"
                            :search="state.filters.search"
                            density="compact"
                            item-value="id"
                            show-expand
                            @update:options="getData"
                        >
                            <template v-slot:item.id="{ index }">
                                {{ index + 1 }}
                            </template>
                            <template v-slot:item.employee_name="{ item }">
                                <div v-if="item.employee" class="d-flex align-center ga-2">
                                    <v-avatar size="28" color="primary" variant="tonal">
                                        <v-img v-if="item.avatar_url" :src="item.avatar_url" cover/>
                                        <span v-else class="text-caption text-uppercase">{{ item.employee.first_name?.charAt(0) }}{{ item.employee.last_name?.charAt(0) }}</span>
                                    </v-avatar>
                                    <span>{{ item.employee.first_name }} {{ item.employee.last_name }}</span>
                                </div>
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

                            <!-- Expanded Row: Sessions & Breaks -->
                            <template v-slot:expanded-row="{ columns, item }">
                                <tr>
                                    <td :colspan="columns.length" class="pa-0">
                                        <div class="bg-grey-lighten-5 pa-4">
                                            <div v-if="item.sessions && item.sessions.length > 0">
                                                <div class="text-subtitle-2 font-weight-bold mb-2">
                                                    <v-icon size="small" class="me-1">mdi-clock-outline</v-icon>
                                                    Sessions
                                                </div>
                                                <v-table density="compact" class="bg-white rounded mb-2">
                                                    <thead>
                                                    <tr class="bg-grey-lighten-4">
                                                        <th class="text-center">#</th>
                                                        <th class="text-center">Check In</th>
                                                        <th class="text-center">Check Out</th>
                                                        <th class="text-center">Duration</th>
                                                        <th class="text-center">Status</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <template v-for="session in item.sessions" :key="session.session_number">
                                                        <tr>
                                                            <td class="text-center">{{ session.session_number }}</td>
                                                            <td class="text-center">{{ session.check_in_time }}</td>
                                                            <td class="text-center">{{ session.check_out_time }}</td>
                                                            <td class="text-center">{{ session.duration }}</td>
                                                            <td class="text-center">
                                                                <v-chip
                                                                    :color="getSessionStatusColor(session.status)"
                                                                    size="x-small"
                                                                    variant="tonal"
                                                                >
                                                                    {{ getSessionStatusLabel(session.status) }}
                                                                </v-chip>
                                                            </td>
                                                        </tr>
                                                        <!-- Breaks for this session -->
                                                        <tr v-for="(brk, bIdx) in session.breaks" :key="'b-' + bIdx" class="bg-orange-lighten-5">
                                                            <td class="text-center">
                                                                <v-icon size="x-small" color="warning">mdi-coffee</v-icon>
                                                            </td>
                                                            <td class="text-center text-caption">{{ brk.break_start }}</td>
                                                            <td class="text-center text-caption">{{ brk.break_end }}</td>
                                                            <td class="text-center text-caption">{{ brk.duration }}</td>
                                                            <td class="text-center">
                                                                <v-chip size="x-small" variant="tonal" color="warning">
                                                                    {{ getBreakTypeLabel(brk.break_type) }}
                                                                </v-chip>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                    </tbody>
                                                </v-table>
                                            </div>
                                            <div v-else class="text-center text-medium-emphasis py-2">
                                                No session data available
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
