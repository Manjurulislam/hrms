<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head} from '@inertiajs/vue3';
import {reactive, ref} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const props = defineProps({
    employee: Object,
    statusOptions: Array,
});

const now = new Date();
const currentMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');

const expanded = ref([]);

const state = reactive({
    headers: [
        {title: '', key: 'data-table-expand', sortable: false, width: '40px'},
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Date', key: 'attendance_date_display'},
        {title: 'Day', key: 'day'},
        {title: 'Check In', key: 'first_check_in_display'},
        {title: 'Check Out', key: 'last_check_out_display'},
        {title: 'Working Hours', key: 'working_hours'},
        {title: 'Break Hours', key: 'break_hours'},
        {title: 'Sessions', key: 'total_sessions'},
        {title: 'Status', key: 'status_label', sortable: false},
    ],
    pagination: {
        itemsPerPage: 50,
        totalItems: 0
    },
    filters: {
        month: currentMonth,
        status: null,
        per_page: 50
    },
    serverItems: [],
    loading: true,
    stats: {
        present: 0,
        absent: 0,
        late: 0,
        half_day: 0,
        wfh: 0,
        avg_hours: 0,
    },
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
    axios.get(route('attendance.records', props.employee.id), {params: state.filters}).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
        state.stats = data.stats;
    });
};

const refreshData = () => {
    getData({page: 1, itemsPerPage: state.pagination.itemsPerPage, sortBy: []});
};

const exportData = () => {
    const params = {
        employee_id: props.employee.id,
        month: state.filters.month,
    };
    if (state.filters.status) {
        params.status = state.filters.status;
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

const statCards = [
    {key: 'present', label: 'Present', color: 'success', icon: 'mdi-check-circle'},
    {key: 'absent', label: 'Absent', color: 'error', icon: 'mdi-close-circle'},
    {key: 'late', label: 'Late', color: 'warning', icon: 'mdi-clock-alert'},
    {key: 'half_day', label: 'Half Day', color: 'orange', icon: 'mdi-circle-half-full'},
    {key: 'wfh', label: 'WFH', color: 'teal', icon: 'mdi-home'},
    {key: 'avg_hours', label: 'Avg Hours', color: 'primary', icon: 'mdi-timer', suffix: 'h'},
];
</script>

<template>
    <DefaultLayout>
        <Head :title="`Attendance - ${employee.name}`"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back', route: 'attendance.index', icon: 'mdi-arrow-left-bold'}"
                        icon="mdi-account-clock"
                        :title="`Attendance - ${employee.name}`"
                    >
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
                        <!-- Employee Info -->
                        <v-row class="mb-4" dense>
                            <v-col cols="12">
                                <v-card variant="tonal" color="primary" class="pa-3">
                                    <div class="d-flex flex-wrap ga-6">
                                        <div>
                                            <span class="text-caption text-medium-emphasis">Employee</span>
                                            <div class="font-weight-bold">{{ employee.name }}</div>
                                        </div>
                                        <div>
                                            <span class="text-caption text-medium-emphasis">Emp ID</span>
                                            <div class="font-weight-bold">{{ employee.id_no }}</div>
                                        </div>
                                        <div>
                                            <span class="text-caption text-medium-emphasis">Department</span>
                                            <div class="font-weight-bold">{{ employee.department }}</div>
                                        </div>
                                        <div>
                                            <span class="text-caption text-medium-emphasis">Designation</span>
                                            <div class="font-weight-bold">{{ employee.designation }}</div>
                                        </div>
                                    </div>
                                </v-card>
                            </v-col>
                        </v-row>

                        <!-- Summary Stats -->
                        <v-row class="mb-4" dense>
                            <v-col v-for="card in statCards" :key="card.key" cols="6" md="2">
                                <v-card variant="outlined" class="text-center pa-3">
                                    <v-icon :color="card.color" size="24">{{ card.icon }}</v-icon>
                                    <div class="text-h6 mt-1">
                                        {{ state.stats[card.key] }}{{ card.suffix || '' }}
                                    </div>
                                    <div class="text-caption text-medium-emphasis">{{ card.label }}</div>
                                </v-card>
                            </v-col>
                        </v-row>

                        <!-- Filters -->
                        <v-row class="mb-4" dense>
                            <v-col cols="12" md="3">
                                <el-date-picker
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
                            <v-col cols="12" md="3">
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

                        <!-- Data Table -->
                        <v-data-table-server
                            v-model:expanded="expanded"
                            :headers="state.headers"
                            :items="state.serverItems"
                            :items-length="state.pagination.totalItems"
                            :items-per-page="state.pagination.itemsPerPage"
                            :loading="state.loading"
                            density="compact"
                            item-value="id"
                            show-expand
                            @update:options="getData"
                        >
                            <template v-slot:item.id="{ index }">
                                {{ index + 1 }}
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
