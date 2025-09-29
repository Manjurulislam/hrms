<script setup>
import { ref, reactive, onMounted } from 'vue'
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

// State
const state = reactive({
    headers: [
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
        itemsPerPage: 15,
        totalItems: 0
    },
    filters: {
        search: '',
        month: getCurrentYearMonth(),
        employee_id: props.employeeId,
        per_page: 15,
        page: 1
    },
    loading: true
})

// Month selector
const selectedMonth = ref(getCurrentYearMonth())
const isCalendarOpen = ref(false)

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
    axios.get('/api/attendance-records', { params: state.filters }).then(({ data }) => {
        state.loading = false
        state.serverItems = data.data
        state.pagination.totalItems = data.total
    }).catch(error => {
        state.loading = false
        toast.error('Failed to load attendance records')
        console.error('Error:', error)
    })
}

// Handle month change
const handleMonthChange = (value) => {
    selectedMonth.value = value
    state.filters.month = value
    state.loading = true
    getData({ page: 1, itemsPerPage: state.filters.per_page, sortBy: [] })
}

// Handle search
const handleSearch = (value) => {
    state.filters.search = value
    state.loading = true
    getData({ page: 1, itemsPerPage: state.filters.per_page, sortBy: [] })
}

// Get status color
const getStatusColor = (status) => {
    const colors = {
        'present': 'success',
        'absent': 'error',
        'late': 'warning',
        'half_day': 'info',
        'leave': 'secondary',
        'holiday': 'primary',
        'weekend': 'grey',
        'work_from_home': 'cyan'
    }
    return colors[status] || 'grey'
}

// Export data
const exportData = () => {
    axios.get('/api/attendance-records/export', {
        params: {
            month: state.filters.month,
            employee_id: state.filters.employee_id
        },
        responseType: 'blob'
    }).then(response => {
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `attendance_${state.filters.month}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        toast.success('Attendance records exported successfully')
    }).catch(error => {
        toast.error('Failed to export attendance records')
    })
}

// Format month for display
const formatMonthDisplay = (monthString) => {
    if (!monthString) return 'Select Month'
    const [year, month] = monthString.split('-')
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December']
    return `${monthNames[parseInt(month) - 1]} ${year}`
}

// Initialize on mount
onMounted(() => {
    getData({ page: 1, itemsPerPage: state.filters.per_page, sortBy: [] })
})
</script>

<template>
    <v-card elevation="3">
        <v-card-title class="d-flex justify-space-between align-center">
            <div class="d-flex align-center">
                <v-icon class="me-2">mdi-calendar-check</v-icon>
                <span>Attendance Records</span>
            </div>
            <div class="d-flex align-center gap-2">
                <!-- Export Button -->
                <v-btn
                    @click="exportData"
                    color="primary"
                    variant="tonal"
                    size="small"
                    prepend-icon="mdi-download"
                >
                    Export
                </v-btn>

                <!-- Month Selector -->
                <v-menu
                    v-model="isCalendarOpen"
                    :close-on-content-click="false"
                    offset-y
                >
                    <template v-slot:activator="{ props }">
                        <v-btn
                            v-bind="props"
                            variant="outlined"
                            prepend-icon="mdi-calendar"
                            size="small"
                        >
                            {{ formatMonthDisplay(selectedMonth) }}
                            <v-icon end>mdi-chevron-down</v-icon>
                        </v-btn>
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
            </div>
        </v-card-title>

        <v-card-text>
            <!-- Search Bar -->
            <v-row class="mb-3">
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="state.filters.search"
                        @update:model-value="handleSearch"
                        prepend-inner-icon="mdi-magnify"
                        placeholder="Search by date or status..."
                        variant="outlined"
                        density="compact"
                        clearable
                        hide-details
                    />
                </v-col>
            </v-row>

            <!-- Data Table -->
            <v-data-table-server
                :headers="state.headers"
                :items="state.serverItems"
                :items-length="state.pagination.totalItems"
                :items-per-page="state.pagination.itemsPerPage"
                :loading="state.loading"
                density="compact"
                item-value="id"
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
                        size="small"
                        label
                    >
                        {{ item.status_label }}
                    </v-chip>
                </template>

                <template v-slot:loading>
                    <v-skeleton-loader type="table-row@10" />
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