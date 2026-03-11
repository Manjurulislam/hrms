<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const toast = useToast()

// Props
const props = defineProps({
    canManage: {
        type: Boolean,
        default: false
    },
    employeeId: {
        type: [Number, String],
        default: null
    }
})

// Status options
const statusOptions = [
    { title: 'All', value: '' },
    { title: 'Present', value: 'present' },
    { title: 'Absent', value: 'absent' },
    { title: 'Late', value: 'late' },
    { title: 'Half Day', value: 'half_day' },
    { title: 'Leave', value: 'leave' },
    { title: 'Holiday', value: 'holiday' },
    { title: 'Weekend', value: 'weekend' },
    { title: 'WFH', value: 'work_from_home' },
]

// Expanded rows
const expanded = ref([])

// State
const state = reactive({
    headers: [
        { title: '', key: 'data-table-expand', sortable: false, width: '40px' },
        { title: 'Date', key: 'attendance_date_display', sortable: true },
        { title: 'Day', key: 'day', sortable: false },
        { title: 'Check In', key: 'first_check_in_display', sortable: false },
        { title: 'Check Out', key: 'last_check_out_display', sortable: false },
        { title: 'Working Hours', key: 'working_hours', sortable: false },
        { title: 'Break Hours', key: 'break_hours', sortable: false },
        { title: 'Sessions', key: 'total_sessions', sortable: false },
        { title: 'Status', key: 'status', sortable: false, width: '10%' }
    ],
    serverItems: [],
    pagination: {
        itemsPerPage: 50,
        totalItems: 0
    },
    filters: {
        month: getCurrentYearMonth(),
        status: '',
        date: null,
        employee_id: props.employeeId,
        per_page: 50,
        page: 1
    },
    loading: true
})

// Month selector
const selectedMonth = ref(getCurrentYearMonth())

// Debounce timer
let debounceTimer = null

// Get current year-month
function getCurrentYearMonth() {
    const now = new Date()
    const year = now.getFullYear()
    const month = (now.getMonth() + 1).toString().padStart(2, '0')
    return `${year}-${month}`
}

// Set pagination and sorting
const setLimit = (obj) => {
    const { page, itemsPerPage, sortBy } = obj
    state.filters.page = page
    state.filters.sort = sortBy
    state.filters.per_page = itemsPerPage === 'All' ? -1 : itemsPerPage
}

// Fetch data from server
const getData = (obj) => {
    setLimit(obj)
    state.loading = true
    axios.get('/api/attendance-records', { params: state.filters }).then(({ data }) => {
        state.serverItems = data.data
        state.pagination.totalItems = data.total
    }).catch(() => {
        toast.error('Failed to load attendance records')
    }).finally(() => {
        state.loading = false
    })
}

// Reload with page reset
const reload = () => {
    getData({ page: 1, itemsPerPage: state.filters.per_page, sortBy: [] })
}

// Handle month change
const handleMonthChange = (value) => {
    selectedMonth.value = value
    state.filters.month = value
    state.filters.date = null
    reload()
}

// Handle status change
const handleStatusChange = () => {
    reload()
}

// Handle date change
const handleDateChange = (value) => {
    state.filters.date = value
    if (value) {
        // When a specific date is selected, clear month filter
        state.filters.month = null
        selectedMonth.value = null
    } else {
        // When date is cleared, revert to current month
        state.filters.month = getCurrentYearMonth()
        selectedMonth.value = getCurrentYearMonth()
    }
    reload()
}

// Get status color
const getStatusColor = (status) => {
    const colors = {
        'present': 'success',
        'absent': 'error',
        'late': 'deeporange',
        'half_day': 'info',
        'leave': 'secondary',
        'holiday': 'primary',
        'weekend': 'grey',
        'work_from_home': 'cyan'
    }
    return colors[status] || 'grey'
}

// Session status helpers
const getSessionStatusColor = (status) => {
    const colors = {
        'active': 'success',
        'completed': 'primary',
        'auto_closed': 'warning',
    }
    return colors[status] || 'grey'
}

const getSessionStatusLabel = (status) => {
    const labels = {
        'active': 'Active',
        'completed': 'Completed',
        'auto_closed': 'Auto Closed',
    }
    return labels[status] || status
}

const getBreakTypeLabel = (type) => {
    const labels = {
        'lunch': 'Lunch',
        'tea': 'Tea',
        'personal': 'Personal',
        'prayer': 'Prayer',
        'other': 'Other',
    }
    return labels[type] || type
}

// Export data
const exportData = () => {
    const month = state.filters.month || getCurrentYearMonth()
    axios.get('/api/attendance-records/export', {
        params: {
            month,
            employee_id: state.filters.employee_id
        },
        responseType: 'blob'
    }).then(response => {
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `attendance_${month}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        toast.success('Attendance records exported successfully')
    }).catch(() => {
        toast.error('Failed to export attendance records')
    })
}

// Format month for display
const formatMonthDisplay = (monthString) => {
    if (!monthString) return 'All'
    const [year, month] = monthString.split('-')
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December']
    return `${monthNames[parseInt(month) - 1]} ${year}`
}

// Cleanup
onUnmounted(() => {
    clearTimeout(debounceTimer)
})

// Expose reload for parent to call
defineExpose({ reload })

// Initialize on mount
onMounted(() => {
    getData({ page: 1, itemsPerPage: state.filters.per_page, sortBy: [] })
})
</script>

<template>
    <v-card elevation="3">
        <v-card-title class="d-flex justify-space-between align-center">
            <div class="d-flex align-center">
                <v-icon class="me-2" size="small">mdi-calendar-check</v-icon>
                <span class="text-body-2 font-weight-light">Attendance</span>
            </div>
            <v-btn
                @click="exportData"
                color="primary"
                variant="tonal"
                size="small"
                prepend-icon="mdi-download"
            >
                Export
            </v-btn>
        </v-card-title>

        <v-card-text>
            <!-- Filters -->
            <v-row class="mb-3" dense>
                <!-- Month Selector -->
                <v-col cols="12" sm="4" md="3">
                    <v-menu :close-on-content-click="false" offset-y>
                        <template v-slot:activator="{ props }">
                            <v-text-field
                                v-bind="props"
                                :model-value="formatMonthDisplay(selectedMonth)"
                                label="Month"
                                prepend-inner-icon="mdi-calendar"
                                variant="outlined"
                                density="compact"
                                readonly
                                hide-details
                            />
                        </template>
                        <v-card min-width="250">
                            <v-card-text>
                                <input
                                    type="month"
                                    v-model="selectedMonth"
                                    @change="handleMonthChange(selectedMonth)"
                                    class="month-picker"
                                />
                            </v-card-text>
                        </v-card>
                    </v-menu>
                </v-col>

                <!-- Date Picker -->
                <v-col cols="12" sm="4" md="3">
                    <v-text-field
                        v-model="state.filters.date"
                        @update:model-value="handleDateChange"
                        label="Specific Date"
                        type="date"
                        variant="outlined"
                        density="compact"
                        clearable
                        hide-details
                    />
                </v-col>

                <!-- Status Filter -->
                <v-col cols="12" sm="4" md="3">
                    <v-select
                        v-model="state.filters.status"
                        @update:model-value="handleStatusChange"
                        :items="statusOptions"
                        label="Status"
                        variant="outlined"
                        density="compact"
                        hide-details
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
                class="elevation-0 custom-table"
            >
                <template v-slot:item.attendance_date_display="{ item }">
                    <span class="font-weight-medium">{{ item.attendance_date_display }}</span>
                </template>

                <template v-slot:item.day="{ item }">
                    <v-chip
                        size="x-small"
                        :color="item.day === 'Sun' || item.day === 'Sat' ? 'grey' : 'default'"
                        variant="text"
                    >
                        {{ item.day }}
                    </v-chip>
                </template>

                <template v-slot:item.first_check_in_display="{ item }">
                    <span :class="item.late_minutes > 0 ? 'text-warning' : ''">{{ item.first_check_in_display }}</span>
                </template>

                <template v-slot:item.last_check_out_display="{ item }">
                    {{ item.last_check_out_display }}
                </template>

                <template v-slot:item.working_hours="{ item }">
                    {{ item.working_hours }}
                </template>

                <template v-slot:item.break_hours="{ item }">
                    {{ item.break_hours }}
                </template>

                <template v-slot:item.total_sessions="{ item }">
                    <v-chip size="x-small" variant="tonal">
                        {{ item.total_sessions || 0 }}
                    </v-chip>
                </template>

                <template v-slot:item.status="{ item }">
                    <v-chip
                        :color="getStatusColor(item.status)"
                        size="x-small"
                        label
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

                <template v-slot:no-data>
                    <div class="text-center py-8">
                        <v-icon size="48" color="grey">mdi-calendar-blank</v-icon>
                        <p class="text-grey mt-2">No attendance records found</p>
                    </div>
                </template>
            </v-data-table-server>
        </v-card-text>
    </v-card>
</template>

<style scoped>
.custom-table :deep(.v-data-table__td) {
    white-space: nowrap;
}

.custom-table :deep(.v-data-table-header__content) {
    font-weight: 600;
}

.month-picker {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 100%;
    font-size: 14px;
}

.month-picker:focus {
    outline: none;
    border-color: #1976D2;
}
</style>
