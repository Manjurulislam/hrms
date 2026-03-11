<script setup>
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import AttendantRecords from "@/Components/modules/employee/attendance/attendantRecords.vue";
import {computed, onMounted, onUnmounted, ref} from 'vue'
import {Head} from "@inertiajs/vue3";
import axios from 'axios'
import {useToast} from 'vue-toastification'

const toast = useToast()

const props = defineProps({
    userInfo: {type: Object, required: true},
    officeHours: {type: Object, required: true},
    monthlyStats: {type: Object, required: true},
    todayData: {type: Object, default: null},
})

// ─── Clock ──────────────────────────────────────────
const currentTime = ref('00:00:00')
const currentDate = ref('')
const currentWeekday = computed(() => currentDate.value?.split(', ')[0] || '')
const currentDateOnly = computed(() => {
    if (!currentDate.value) return ''
    return currentDate.value.split(', ').slice(1).join(', ')
})

const updateClock = () => {
    const now = new Date()
    currentTime.value = now.toLocaleTimeString('en-US', {hour12: true, hour: 'numeric', minute: '2-digit', second: '2-digit'})
    currentDate.value = now.toLocaleDateString('en-US', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'})
}

// ─── Attendance State ───────────────────────────────
const isWorking = ref(false)
const isOnBreak = ref(false)
const isLoading = ref(false)
const workStartTime = ref(null)
const breakStartTime = ref(null)
const totalWorkedSeconds = ref(0)
const startTime = ref('--:--')
const endTime = ref('--:--')
const totalHours = ref('0h 0m')
const workTimer = ref('00:00:00')
const breakTimer = ref('00:00:00')
const progressPercentage = ref(0)

let clockInterval = null
let workInterval = null
let breakInterval = null
let syncInterval = null

// ─── Helpers ────────────────────────────────────────
const parseTimeToMinutes = (timeString) => {
    const [time, period] = timeString.split(' ')
    let [hours, minutes] = time.split(':').map(Number)
    if (period === 'PM' && hours !== 12) hours += 12
    if (period === 'AM' && hours === 12) hours = 0
    return hours * 60 + minutes
}

const getTotalOfficeSeconds = () => {
    const startMin = parseTimeToMinutes(props.officeHours.start)
    const endMin = parseTimeToMinutes(props.officeHours.end)
    return (endMin - startMin) * 60
}

const formatTimer = (seconds) => {
    const h = Math.floor(seconds / 3600)
    const m = Math.floor((seconds % 3600) / 60)
    const s = seconds % 60
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
}

// ─── Computed ───────────────────────────────────────
const isOfficeHoursComplete = computed(() => {
    let total = totalWorkedSeconds.value
    if (isWorking.value && workStartTime.value) {
        total += Math.floor((new Date() - workStartTime.value) / 1000)
    }
    return total >= getTotalOfficeSeconds()
})

const progressColor = computed(() => {
    if (progressPercentage.value >= 75) return 'success'
    if (progressPercentage.value >= 50) return 'info'
    return 'warning'
})

const statusText = computed(() => {
    if (isOnBreak.value) return 'On Break'
    if (isWorking.value) return 'Working'
    if (isOfficeHoursComplete.value) return 'Completed'
    if (totalWorkedSeconds.value > 0) return 'Paused'
    return 'Not Started'
})

const statusColor = computed(() => {
    if (isOnBreak.value) return 'warning'
    if (isWorking.value) return 'success'
    if (isOfficeHoursComplete.value) return 'success'
    if (totalWorkedSeconds.value > 0) return 'info'
    return 'grey'
})

// ─── Timer Updates ──────────────────────────────────
const updateWorkTimer = () => {
    if (!isWorking.value || !workStartTime.value) return
    const currentSessionSec = Math.floor((new Date() - workStartTime.value) / 1000)
    const totalToday = totalWorkedSeconds.value + currentSessionSec
    workTimer.value = formatTimer(currentSessionSec)
    totalHours.value = `${Math.floor(totalToday / 3600)}h ${Math.floor((totalToday % 3600) / 60)}m`
    updateProgress()
}

const updateBreakTimer = () => {
    if (!isOnBreak.value || !breakStartTime.value) return
    breakTimer.value = formatTimer(Math.floor((new Date() - breakStartTime.value) / 1000))
}

const updateProgress = () => {
    let total = totalWorkedSeconds.value
    if (isWorking.value && workStartTime.value) {
        total += Math.floor((new Date() - workStartTime.value) / 1000)
    }
    progressPercentage.value = Math.min((total / getTotalOfficeSeconds()) * 100, 100)
}

// ─── Initialize ─────────────────────────────────────
const initFromServer = () => {
    if (!props.todayData) return
    totalWorkedSeconds.value = props.todayData.totalWorkedSeconds || 0

    if (props.todayData.currentSession) {
        isWorking.value = true
        workStartTime.value = new Date(props.todayData.currentSession.startTime)
        workInterval = setInterval(updateWorkTimer, 1000)
        updateWorkTimer()
    }
    if (props.todayData.currentBreak) {
        isOnBreak.value = true
        breakStartTime.value = new Date(props.todayData.currentBreak.startTime)
        breakInterval = setInterval(updateBreakTimer, 1000)
        updateBreakTimer()
    }
    if (props.todayData.summary) {
        startTime.value = props.todayData.summary.firstCheckIn || '--:--'
        endTime.value = props.todayData.summary.lastCheckOut || '--:--'
        totalHours.value = props.todayData.summary.totalHours || '0h 0m'
    }
    updateProgress()
}

// ─── Actions ────────────────────────────────────────
const startWork = async () => {
    if (isLoading.value) return
    isLoading.value = true
    try {
        const {data} = await axios.post('/emp-attendance/start-work', {location: 'office', note: null})
        if (data.success) {
            const td = data.todayData
            totalWorkedSeconds.value = td.totalWorkedSeconds || 0
            if (td.currentSession) {
                isWorking.value = true
                workStartTime.value = new Date(td.currentSession.startTime)
                if (td.summary) {
                    startTime.value = td.summary.firstCheckIn
                    endTime.value = '--:--'
                }
                workInterval = setInterval(updateWorkTimer, 1000)
                updateWorkTimer()
                toast.success(data.message)
                recordsRef.value?.reload()
            }
        }
    } catch (e) {
        toast.error(e.response?.data?.message || e.response?.data?.errors?.session?.[0] || 'Failed to start work')
    } finally {
        isLoading.value = false
    }
}

const endWork = async () => {
    if (isLoading.value) return
    isLoading.value = true
    try {
        const {data} = await axios.post('/emp-attendance/end-work', {location: 'office', note: null})
        if (data.success) {
            const td = data.todayData
            isWorking.value = false
            totalWorkedSeconds.value = td.totalWorkedSeconds || 0
            if (td.summary) {
                endTime.value = td.summary.lastCheckOut
                totalHours.value = td.summary.totalHours || totalHours.value
            }
            clearInterval(workInterval)
            workInterval = null
            workTimer.value = '00:00:00'
            workStartTime.value = null
            toast.success(data.message)
            recordsRef.value?.reload()
        }
    } catch (e) {
        toast.error(e.response?.data?.message || 'Failed to end work')
    } finally {
        isLoading.value = false
    }
}

const startBreak = async () => {
    if (isLoading.value || !isWorking.value) return
    isLoading.value = true
    try {
        const {data} = await axios.post('/emp-attendance/start-break', {break_type: 'personal', reason: null})
        if (data.success) {
            isOnBreak.value = true
            breakStartTime.value = new Date()
            breakInterval = setInterval(updateBreakTimer, 1000)
            updateBreakTimer()
            toast.success(data.message)
        }
    } catch (e) {
        toast.error(e.response?.data?.message || 'Failed to start break')
    } finally {
        isLoading.value = false
    }
}

const endBreak = async () => {
    if (isLoading.value || !isOnBreak.value) return
    isLoading.value = true
    try {
        const {data} = await axios.post('/emp-attendance/end-break')
        if (data.success) {
            isOnBreak.value = false
            clearInterval(breakInterval)
            breakInterval = null
            breakTimer.value = '00:00:00'
            breakStartTime.value = null
            toast.success(data.message)
        }
    } catch (e) {
        toast.error(e.response?.data?.message || 'Failed to end break')
    } finally {
        isLoading.value = false
    }
}

// ─── Sync ───────────────────────────────────────────
const syncWithServer = async () => {
    try {
        const {data} = await axios.get('/emp-attendance/current-status')
        if (!data.success || !data.data) return
        const s = data.data
        totalWorkedSeconds.value = s.totalWorkedSeconds || 0

        if (s.currentSession && !isWorking.value) {
            isWorking.value = true
            workStartTime.value = new Date(s.currentSession.startTime)
            if (!workInterval) workInterval = setInterval(updateWorkTimer, 1000)
            updateWorkTimer()
            if (s.summary) {
                startTime.value = s.summary.firstCheckIn || '--:--'
                totalHours.value = s.summary.totalHours || '0h 0m'
            }
        } else if (!s.currentSession && isWorking.value) {
            isWorking.value = false
            clearInterval(workInterval)
            workInterval = null
            workTimer.value = '00:00:00'
            if (s.summary) {
                endTime.value = s.summary.lastCheckOut || '--:--'
                totalHours.value = s.summary.totalHours || '0h 0m'
            }
        } else if (s.summary) {
            totalHours.value = s.summary.totalHours || totalHours.value
        }

        if (s.currentBreak && !isOnBreak.value) {
            isOnBreak.value = true
            breakStartTime.value = new Date(s.currentBreak.startTime)
            if (!breakInterval) breakInterval = setInterval(updateBreakTimer, 1000)
            updateBreakTimer()
        } else if (!s.currentBreak && isOnBreak.value) {
            isOnBreak.value = false
            clearInterval(breakInterval)
            breakInterval = null
            breakTimer.value = '00:00:00'
            breakStartTime.value = null
        }
    } catch (e) {
        console.error('Sync failed:', e)
    }
}

// ─── Records Ref ────────────────────────────────────
const recordsRef = ref(null)

// ─── Lifecycle ──────────────────────────────────────
onMounted(() => {
    updateClock()
    clockInterval = setInterval(updateClock, 1000)
    initFromServer()
    syncInterval = setInterval(syncWithServer, 30000)
})

onUnmounted(() => {
    clearInterval(clockInterval)
    clearInterval(workInterval)
    clearInterval(breakInterval)
    clearInterval(syncInterval)
})
</script>

<template>
    <DefaultLayout>
        <Head title="Attendance"/>

        <!-- Top: Clock + Timer + Profile in one row -->
        <v-row>
            <!-- Clock & Timer Section -->
            <v-col cols="12" lg="8">
                <v-card variant="flat" class="border rounded-lg">
                    <v-card-text class="pa-0">
                        <v-row no-gutters>
                            <!-- Live Clock -->
                            <v-col cols="12" md="4" class="d-flex align-center justify-center pa-6 border-e">
                                <div class="text-center">
                                    <div class="text-h4 font-weight-light text-primary">{{ currentTime }}</div>
                                    <div class="text-body-2 text-medium-emphasis mt-1">{{ currentWeekday }}</div>
                                    <div class="text-caption text-medium-emphasis">{{ currentDateOnly }}</div>
                                    <v-chip
                                        variant="tonal"
                                        color="primary"
                                        size="small"
                                        class="mt-3"
                                        prepend-icon="mdi-clock-outline"
                                    >
                                        {{ officeHours.start }} - {{ officeHours.end }}
                                    </v-chip>
                                </div>
                            </v-col>

                            <!-- Timer & Actions -->
                            <v-col cols="12" md="8" class="d-flex flex-column align-center justify-center pa-6">
                                <!-- Progress Ring -->
                                <div class="d-flex align-center ga-8">
                                    <v-progress-circular
                                        :model-value="progressPercentage"
                                        :color="progressColor"
                                        :size="160"
                                        :width="8"
                                        bg-color="grey-lighten-3"
                                    >
                                        <div class="text-center">
                                            <div class="text-h5 font-weight-medium">
                                                {{ isWorking ? workTimer : totalHours }}
                                            </div>
                                            <div class="text-caption text-medium-emphasis">
                                                {{ isWorking ? 'Session Time' : 'Total Today' }}
                                            </div>
                                        </div>
                                    </v-progress-circular>

                                    <!-- Today's Stats -->
                                    <div>
                                        <div class="d-flex align-center ga-2 mb-3">
                                            <v-chip :color="statusColor" size="small" variant="flat" label>
                                                {{ statusText }}
                                            </v-chip>
                                            <v-chip
                                                v-if="isOnBreak"
                                                color="warning"
                                                size="small"
                                                variant="tonal"
                                                prepend-icon="mdi-coffee"
                                            >
                                                {{ breakTimer }}
                                            </v-chip>
                                        </div>

                                        <v-list density="compact" class="bg-transparent pa-0">
                                            <v-list-item class="px-0" density="compact">
                                                <template #prepend>
                                                    <v-icon size="16" color="success" class="mr-2">mdi-login</v-icon>
                                                </template>
                                                <v-list-item-title class="text-body-2">Check In</v-list-item-title>
                                                <template #append>
                                                    <span class="text-body-2 font-weight-medium">{{ startTime }}</span>
                                                </template>
                                            </v-list-item>
                                            <v-list-item class="px-0" density="compact">
                                                <template #prepend>
                                                    <v-icon size="16" color="error" class="mr-2">mdi-logout</v-icon>
                                                </template>
                                                <v-list-item-title class="text-body-2">Check Out</v-list-item-title>
                                                <template #append>
                                                    <span class="text-body-2 font-weight-medium">{{ endTime }}</span>
                                                </template>
                                            </v-list-item>
                                            <v-list-item class="px-0" density="compact">
                                                <template #prepend>
                                                    <v-icon size="16" color="primary" class="mr-2">mdi-clock-check-outline</v-icon>
                                                </template>
                                                <v-list-item-title class="text-body-2">Total Hours</v-list-item-title>
                                                <template #append>
                                                    <span class="text-body-2 font-weight-medium text-primary">{{ totalHours }}</span>
                                                </template>
                                            </v-list-item>
                                        </v-list>

                                        <!-- Action Buttons -->
                                        <div class="d-flex ga-2 mt-3">
                                            <template v-if="isOfficeHoursComplete && !isWorking">
                                                <v-chip color="success" variant="flat" size="small" prepend-icon="mdi-check-circle">
                                                    Office hours completed
                                                </v-chip>
                                            </template>
                                            <template v-else>
                                                <v-btn
                                                    :color="isWorking ? 'error' : 'success'"
                                                    :disabled="isLoading || isOnBreak"
                                                    :loading="isLoading && !isOnBreak"
                                                    size="small"
                                                    variant="flat"
                                                    :prepend-icon="isWorking ? 'mdi-stop' : 'mdi-play'"
                                                    class="text-none"
                                                    @click="isWorking ? endWork() : startWork()"
                                                >
                                                    {{ isWorking ? 'End Work' : 'Start Work' }}
                                                </v-btn>
                                                <v-btn
                                                    v-if="isWorking"
                                                    :color="isOnBreak ? 'warning' : 'info'"
                                                    :disabled="isLoading"
                                                    :loading="isLoading && isOnBreak"
                                                    size="small"
                                                    variant="tonal"
                                                    :prepend-icon="isOnBreak ? 'mdi-play-circle' : 'mdi-coffee'"
                                                    class="text-none"
                                                    @click="isOnBreak ? endBreak() : startBreak()"
                                                >
                                                    {{ isOnBreak ? 'End Break' : 'Take Break' }}
                                                </v-btn>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Profile & Monthly Stats -->
            <v-col cols="12" lg="4">
                <v-card variant="flat" class="border rounded-lg h-100">
                    <v-card-text>
                        <!-- Profile -->
                        <div class="d-flex align-center ga-3 mb-4">
                            <v-avatar color="primary" size="48" variant="tonal">
                                <v-icon size="28">mdi-account</v-icon>
                            </v-avatar>
                            <div>
                                <div class="text-body-1 font-weight-medium">{{ userInfo.name }}</div>
                                <div class="text-caption text-medium-emphasis">{{ userInfo.position }}</div>
                                <div class="text-caption text-medium-emphasis">{{ officeHours.department }} &middot; {{ officeHours.company }}</div>
                            </div>
                        </div>

                        <v-divider class="mb-4"/>

                        <!-- Monthly Stats -->
                        <div class="text-caption font-weight-bold text-medium-emphasis text-uppercase mb-3">This Month</div>
                        <v-row dense>
                            <v-col cols="6">
                                <v-card color="success" variant="tonal" class="pa-3 text-center rounded-lg">
                                    <div class="text-h5 font-weight-medium text-success">{{ monthlyStats.present }}</div>
                                    <div class="text-caption">Present</div>
                                </v-card>
                            </v-col>
                            <v-col cols="6">
                                <v-card color="error" variant="tonal" class="pa-3 text-center rounded-lg">
                                    <div class="text-h5 font-weight-medium text-error">{{ monthlyStats.absent }}</div>
                                    <div class="text-caption">Absent</div>
                                </v-card>
                            </v-col>
                            <v-col cols="6">
                                <v-card color="warning" variant="tonal" class="pa-3 text-center rounded-lg">
                                    <div class="text-h5 font-weight-medium text-warning">{{ monthlyStats.late }}</div>
                                    <div class="text-caption">Late</div>
                                </v-card>
                            </v-col>
                            <v-col cols="6">
                                <v-card color="info" variant="tonal" class="pa-3 text-center rounded-lg">
                                    <div class="text-h5 font-weight-medium text-info">{{ monthlyStats.rate }}%</div>
                                    <div class="text-caption">Rate</div>
                                </v-card>
                            </v-col>
                        </v-row>

                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="d-flex justify-space-between text-caption mb-1">
                                <span>Today's Progress</span>
                                <span class="font-weight-medium">{{ Math.round(progressPercentage) }}%</span>
                            </div>
                            <v-progress-linear
                                :model-value="progressPercentage"
                                :color="progressColor"
                                height="6"
                                rounded
                            />
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Records Table -->
        <v-row class="mt-1">
            <v-col cols="12">
                <AttendantRecords
                    ref="recordsRef"
                    :can-manage="false"
                    :employee-id="userInfo.employeeId"
                />
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
