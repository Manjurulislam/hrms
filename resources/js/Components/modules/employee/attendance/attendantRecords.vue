<script setup>
import {computed, onMounted, ref} from 'vue'

// Reactive state
const search = ref('')
const selectedMonth = ref('')
const availableMonths = ref([])

// Mock attendance data
const attendanceRecords = ref({
    '2025-09': [
        {date: 1, checkIn: '09:15', checkOut: '17:45', hours: 8.5, status: 'Present', late: true},
        {date: 2, checkIn: '09:00', checkOut: '18:00', hours: 9, status: 'Present', late: false},
        {date: 3, checkIn: null, checkOut: null, hours: 0, status: 'Absent', late: false},
        {date: 4, checkIn: '08:45', checkOut: '17:30', hours: 8.75, status: 'Present', late: false},
        {date: 5, checkIn: '09:30', checkOut: '18:15', hours: 8.75, status: 'Present', late: true},
        {date: 6, checkIn: '09:00', checkOut: '17:45', hours: 8.75, status: 'Present', late: false},
        {date: 7, checkIn: null, checkOut: null, hours: 0, status: 'Weekend', late: false},
        {date: 8, checkIn: null, checkOut: null, hours: 0, status: 'Weekend', late: false},
        {date: 9, checkIn: '08:55', checkOut: '17:40', hours: 8.75, status: 'Present', late: false},
    ],
    '2025-08': [
        {date: 1, checkIn: '09:00', checkOut: '18:00', hours: 9, status: 'Present', late: false},
        {date: 2, checkIn: '09:15', checkOut: '17:45', hours: 8.5, status: 'Present', late: true},
        {date: 3, checkIn: null, checkOut: null, hours: 0, status: 'Weekend', late: false},
        {date: 4, checkIn: null, checkOut: null, hours: 0, status: 'Weekend', late: false},
        {date: 5, checkIn: '09:00', checkOut: '17:30', hours: 8.5, status: 'Present', late: false},
    ],
    '2025-07': [
        {date: 1, checkIn: '08:45', checkOut: '17:30', hours: 8.75, status: 'Present', late: false},
        {date: 2, checkIn: '09:30', checkOut: '18:15', hours: 8.75, status: 'Present', late: true},
        {date: 3, checkIn: null, checkOut: null, hours: 0, status: 'Absent', late: false},
    ]
})

// Data table headers
const headers = [
    {title: 'Date', key: 'date', sortable: true, width: '80px'},
    {title: 'Day', key: 'day', sortable: true, width: '100px'},
    {title: 'Check In', key: 'checkIn', sortable: true, width: '120px'},
    {title: 'Check Out', key: 'checkOut', sortable: true, width: '120px'},
    {title: 'Total Hours', key: 'totalHours', sortable: true, width: '130px'},
    {title: 'Status', key: 'status', sortable: true, width: '120px'}
]

// Computed properties
const tableData = computed(() => {
    const monthKey = selectedMonth.value
    if (!monthKey || !attendanceRecords.value[monthKey]) return []

    return attendanceRecords.value[monthKey].map(record => ({
        date: String(record.date).padStart(2, '0'),
        day: getDayName(record.date, monthKey),
        checkIn: record.checkIn || '--:--',
        checkOut: record.checkOut || '--:--',
        totalHours: record.hours > 0 ? record.hours + 'h' : '--',
        status: record.status,
        late: record.late,
        originalRecord: record
    }))
})

const currentMonthDisplay = computed(() => {
    if (!selectedMonth.value) return ''
    const [year, month] = selectedMonth.value.split('-')
    const date = new Date(year, month - 1)
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long'
    })
})

// Methods
const initializeMonths = () => {
    const months = Object.keys(attendanceRecords.value).sort().reverse()
    availableMonths.value = months.map(monthKey => {
        const [year, month] = monthKey.split('-')
        const date = new Date(year, month - 1)
        return {
            value: monthKey,
            title: date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long'
            })
        }
    })

    // Set current month as default
    const now = new Date()
    const currentMonthKey = `${now.getFullYear()}-${(now.getMonth() + 1).toString().padStart(2, '0')}`
    selectedMonth.value = availableMonths.value.find(m => m.value === currentMonthKey)?.value || availableMonths.value[0]?.value || ''
}

const getDayName = (date, monthKey) => {
    if (!monthKey) return ''
    const [year, month] = monthKey.split('-')
    const recordDate = new Date(year, month - 1, date)
    return recordDate.toLocaleDateString('en-US', {weekday: 'short'})
}

const getStatusColor = (status, late) => {
    switch (status) {
        case 'Present':
            return late ? 'warning' : 'success'
        case 'Absent':
            return 'error'
        case 'Weekend':
            return 'grey'
        default:
            return 'grey'
    }
}

const getStatusText = (status, late) => {
    if (status === 'Present') {
        return late ? 'Late' : 'Present'
    }
    return status
}

// Lifecycle
onMounted(() => {
    initializeMonths()
})
</script>

<template>
    <v-card elevation="3">
        <v-card-text>
            <!-- Header with Month Selection and Search -->
            <div
                class="d-flex flex-column flex-md-row justify-space-between align-start align-md-center gap-4 mb-4">
                <div class="text-subtitle-1 text-uppercase">
                    <v-icon class="me-2">mdi-calendar-month</v-icon>
                    Attendance
                </div>

                <div class="d-flex flex-column flex-sm-row gap-3 align-center">
                    <!-- Month Selector -->
                    <v-select
                        v-model="selectedMonth"
                        :items="availableMonths"
                        density="compact"
                        hide-details
                        label="Select Month"
                        prepend-inner-icon="mdi-calendar"
                        style="min-width: 200px;"
                        variant="outlined"
                    ></v-select>

                    <!-- Search Field -->
                    <v-text-field
                        v-model="search"
                        clearable
                        density="compact"
                        hide-details
                        label="Search records..."
                        prepend-inner-icon="mdi-magnify"
                        style="min-width: 250px;"
                        variant="outlined"
                    ></v-text-field>
                </div>
            </div>

            <!-- Current Month Display -->
            <div class="text-subtitle-1 text-medium-emphasis mb-4">
                {{ currentMonthDisplay }}
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="tableData"
                :items-per-page="15"
                :search="search"
                class="elevation-1"
                density="comfortable"
            >
                <!-- Custom Status Column -->
                <template v-slot:item.status="{ item }">
                    <v-chip
                        :color="getStatusColor(item.status, item.late)"
                        class="status-chip"
                        size="small"
                    >
                        {{ getStatusText(item.status, item.late) }}
                    </v-chip>
                </template>

                <!-- Custom Date Column -->
                <template v-slot:item.date="{ item }">
                    <span class="font-weight-bold">{{ item.date }}</span>
                </template>

                <!-- Custom Total Hours Column -->
                <template v-slot:item.totalHours="{ item }">
                    <span :class="item.originalRecord.hours > 8 ? 'text-success font-weight-bold' : ''">
                        {{ item.totalHours }}
                    </span>
                </template>

                <!-- No Data Slot -->
                <template v-slot:no-data>
                    <div class="text-center py-8">
                        <v-icon class="mb-2" color="grey" size="48">mdi-calendar-remove</v-icon>
                        <div class="text-body-1 text-medium-emphasis">
                            No attendance data available for {{ currentMonthDisplay }}
                        </div>
                    </div>
                </template>

                <!-- Bottom Pagination -->
                <template v-slot:bottom>
                    <div class="text-center pt-2">
                        <v-pagination
                            v-model="page"
                            :length="pageCount"
                            rounded="circle"
                        ></v-pagination>
                    </div>
                </template>
            </v-data-table>
        </v-card-text>
    </v-card>
</template>

<style scoped>
.status-chip {
    font-weight: 500;
}

/* Data table styling */
:deep(.v-data-table) {
    border-radius: 8px;
}

:deep(.v-data-table-header) {
    background-color: #f5f5f5;
}

:deep(.v-data-table-header th) {
    font-weight: 600;
    color: #1B4F72;
}

:deep(.v-data-table__td) {
    border-bottom: 1px solid #e0e0e0 !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .d-flex.flex-column.flex-md-row {
        align-items: stretch !important;
    }

    .d-flex.flex-column.flex-sm-row {
        width: 100%;
    }

    .v-select, .v-text-field {
        min-width: 100% !important;
    }
}
</style>
