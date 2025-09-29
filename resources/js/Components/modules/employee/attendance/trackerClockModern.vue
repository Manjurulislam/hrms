<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const toast = useToast()

// Props
const props = defineProps({
    officeHours: {
        type: Object,
        required: true
    },
    todayData: {
        type: Object,
        default: null
    }
})

// Emits
const emit = defineEmits(['work-started', 'work-ended', 'attendance-updated'])

// Reactive state
const currentTime = ref('00:00:00')
const currentDate = ref('')
const isWorking = ref(false)
const workStartTime = ref(null)
const startTime = ref('--:--')
const endTime = ref('--:--')
const totalHours = ref('0h 0m')
const workTimer = ref('00:00:00')
const progressPercentage = ref(0)
const totalWorkedSeconds = ref(0)
const isLoading = ref(false)
const showBreakDialog = ref(false)
const breakType = ref('tea')
const isOnBreak = ref(false)
const breakStartTime = ref(null)
const breakTimer = ref('00:00')

// Timer intervals
let clockInterval = null
let workInterval = null
let breakInterval = null

// Initialize from server data
const initializeFromServerData = () => {
    if (props.todayData) {
        totalWorkedSeconds.value = props.todayData.totalWorkedSeconds || 0

        if (props.todayData.currentSession) {
            isWorking.value = true
            workStartTime.value = new Date(props.todayData.currentSession.startTime)
            workInterval = setInterval(updateWorkTimer, 1000)
            updateWorkTimer()
        }

        if (props.todayData.summary) {
            startTime.value = props.todayData.summary.firstCheckIn || '--:--'
            endTime.value = props.todayData.summary.lastCheckOut || '--:--'
            totalHours.value = props.todayData.summary.totalHours || '0h 0m'
        }

        updateProgress()
    }
}

// Computed properties
const currentWeekday = computed(() => {
    if (!currentDate.value) return ''
    return currentDate.value.split(', ')[0]
})

const currentDateOnly = computed(() => {
    if (!currentDate.value) return ''
    const parts = currentDate.value.split(', ')
    return parts.slice(1).join(', ')
})

const progressColor = computed(() => {
    if (progressPercentage.value >= 100) return '#4caf50'
    if (progressPercentage.value >= 75) return '#2196f3'
    if (progressPercentage.value >= 50) return '#ff9800'
    return '#9e9e9e'
})

const statusMessage = computed(() => {
    if (isOnBreak.value) return 'On Break'
    if (isWorking.value) return 'Working'
    if (totalWorkedSeconds.value > 0) return 'Work Completed'
    return 'Ready to Start'
})

const statusIcon = computed(() => {
    if (isOnBreak.value) return 'mdi-coffee'
    if (isWorking.value) return 'mdi-briefcase-check'
    if (totalWorkedSeconds.value > 0) return 'mdi-check-circle'
    return 'mdi-clock-outline'
})

const statusColor = computed(() => {
    if (isOnBreak.value) return 'warning'
    if (isWorking.value) return 'success'
    if (totalWorkedSeconds.value > 0) return 'primary'
    return 'grey'
})

// Helper functions
const formatTime = (date, includeSeconds = false) => {
    return date.toLocaleTimeString('en-US', {
        hour12: true,
        hour: 'numeric',
        minute: '2-digit',
        ...(includeSeconds && { second: '2-digit' })
    })
}

const parseTimeToMinutes = (timeString) => {
    const [time, period] = timeString.split(' ')
    let [hours, minutes] = time.split(':').map(Number)
    if (period === 'PM' && hours !== 12) hours += 12
    if (period === 'AM' && hours === 12) hours = 0
    return hours * 60 + minutes
}

const getTotalOfficeHours = () => {
    const startMinutes = parseTimeToMinutes(props.officeHours.start)
    const endMinutes = parseTimeToMinutes(props.officeHours.end)
    return (endMinutes - startMinutes) * 60
}

const updateClock = () => {
    const now = new Date()
    currentTime.value = formatTime(now, true)
    currentDate.value = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const updateWorkTimer = () => {
    if (!isWorking.value || !workStartTime.value) return

    const now = new Date()
    const currentSessionSeconds = Math.floor((now - workStartTime.value) / 1000)
    const totalSecondsToday = totalWorkedSeconds.value + currentSessionSeconds

    const hours = Math.floor(currentSessionSeconds / 3600)
    const minutes = Math.floor((currentSessionSeconds % 3600) / 60)
    const seconds = currentSessionSeconds % 60
    workTimer.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`

    const totalHoursCalc = Math.floor(totalSecondsToday / 3600)
    const totalMinutes = Math.floor((totalSecondsToday % 3600) / 60)
    totalHours.value = `${totalHoursCalc}h ${totalMinutes}m`

    updateProgress()

    emit('attendance-updated', {
        isWorking: isWorking.value,
        duration: currentSessionSeconds,
        totalDuration: totalSecondsToday,
        progressPercentage: progressPercentage.value
    })
}

const updateBreakTimer = () => {
    if (!isOnBreak.value || !breakStartTime.value) return

    const now = new Date()
    const breakSeconds = Math.floor((now - breakStartTime.value) / 1000)
    const minutes = Math.floor(breakSeconds / 60)
    const seconds = breakSeconds % 60
    breakTimer.value = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
}

const updateProgress = () => {
    let totalSeconds = totalWorkedSeconds.value

    if (isWorking.value && workStartTime.value) {
        const currentSessionSeconds = Math.floor((new Date() - workStartTime.value) / 1000)
        totalSeconds += currentSessionSeconds
    }

    const totalOfficeSeconds = getTotalOfficeHours()
    progressPercentage.value = Math.min((totalSeconds / totalOfficeSeconds) * 100, 100)
}

// Actions
const startWork = async () => {
    if (isLoading.value) return
    isLoading.value = true

    try {
        const response = await axios.post('/emp-attendance/start-work', {
            location: 'office',
            note: null
        })

        if (response.data.success) {
            const todayData = response.data.todayData
            totalWorkedSeconds.value = todayData.totalWorkedSeconds || 0

            if (todayData.currentSession) {
                isWorking.value = true
                workStartTime.value = new Date(todayData.currentSession.startTime)

                if (todayData.summary) {
                    startTime.value = todayData.summary.firstCheckIn || formatTime(workStartTime.value)
                    endTime.value = '--:--'
                }

                workInterval = setInterval(updateWorkTimer, 1000)
                updateWorkTimer()

                toast.success(response.data.message || 'Work started successfully!')
                emit('work-started', { startTime: workStartTime.value })
            }
        }
    } catch (error) {
        const message = error.response?.data?.message || 'Failed to start work'
        toast.error(message)
    } finally {
        isLoading.value = false
    }
}

const endWork = async () => {
    if (isLoading.value) return
    isLoading.value = true

    try {
        const response = await axios.post('/emp-attendance/end-work', {
            location: 'office',
            note: null
        })

        if (response.data.success) {
            const todayData = response.data.todayData
            isWorking.value = false
            totalWorkedSeconds.value = todayData.totalWorkedSeconds || 0

            if (todayData.summary) {
                endTime.value = todayData.summary.lastCheckOut || formatTime(new Date())
                totalHours.value = todayData.summary.totalHours || totalHours.value
            }

            clearInterval(workInterval)
            workInterval = null
            workTimer.value = '00:00:00'

            toast.success(response.data.message || 'Work ended successfully!')
            emit('work-ended', {
                startTime: workStartTime.value,
                endTime: new Date(),
                totalHours: totalHours.value,
                totalWorkedSeconds: totalWorkedSeconds.value
            })

            workStartTime.value = null
        }
    } catch (error) {
        const message = error.response?.data?.message || 'Failed to end work'
        toast.error(message)
    } finally {
        isLoading.value = false
    }
}

const startBreak = async () => {
    if (isLoading.value || !isWorking.value) return

    try {
        const response = await axios.post('/emp-attendance/start-break', {
            break_type: breakType.value,
            reason: null
        })

        if (response.data.success) {
            isOnBreak.value = true
            breakStartTime.value = new Date()
            breakInterval = setInterval(updateBreakTimer, 1000)
            showBreakDialog.value = false
            toast.success('Break started')
        }
    } catch (error) {
        toast.error('Failed to start break')
    }
}

const endBreak = async () => {
    if (isLoading.value || !isOnBreak.value) return

    try {
        const response = await axios.post('/emp-attendance/end-break')

        if (response.data.success) {
            isOnBreak.value = false
            clearInterval(breakInterval)
            breakTimer.value = '00:00'
            breakStartTime.value = null
            toast.success('Break ended')
        }
    } catch (error) {
        toast.error('Failed to end break')
    }
}

const syncWithServer = async () => {
    try {
        const response = await axios.get('/emp-attendance/current-status')
        if (response.data.success && response.data.data) {
            const serverData = response.data.data
            totalWorkedSeconds.value = serverData.totalWorkedSeconds || 0

            if (serverData.currentSession && !isWorking.value) {
                isWorking.value = true
                workStartTime.value = new Date(serverData.currentSession.startTime)
                if (!workInterval) {
                    workInterval = setInterval(updateWorkTimer, 1000)
                }
                updateWorkTimer()

                if (serverData.summary) {
                    startTime.value = serverData.summary.firstCheckIn || '--:--'
                    endTime.value = '--:--'
                    totalHours.value = serverData.summary.totalHours || '0h 0m'
                }
            } else if (!serverData.currentSession && isWorking.value) {
                isWorking.value = false
                clearInterval(workInterval)
                workInterval = null
                workTimer.value = '00:00:00'

                if (serverData.summary) {
                    endTime.value = serverData.summary.lastCheckOut || '--:--'
                    totalHours.value = serverData.summary.totalHours || '0h 0m'
                }
            } else if (serverData.summary) {
                totalHours.value = serverData.summary.totalHours || totalHours.value
            }
        }
    } catch (error) {
        console.error('Failed to sync with server:', error)
    }
}

// Lifecycle
onMounted(() => {
    updateClock()
    clockInterval = setInterval(updateClock, 1000)
    initializeFromServerData()
    const syncInterval = setInterval(syncWithServer, 30000)
    window.attendanceSyncInterval = syncInterval
})

onUnmounted(() => {
    clearInterval(clockInterval)
    clearInterval(workInterval)
    clearInterval(breakInterval)
    if (window.attendanceSyncInterval) {
        clearInterval(window.attendanceSyncInterval)
    }
})
</script>

<template>
    <!-- Modern Header Section -->
    <v-card class="mb-4" elevation="0" color="transparent">
        <v-card-text class="pa-0">
            <div class="text-h5 font-weight-medium mb-1">{{ currentWeekday }}</div>
            <div class="text-subtitle-1 text-medium-emphasis">{{ currentDateOnly }}</div>
        </v-card-text>
    </v-card>

    <!-- Main Clock Card -->
    <v-card class="clock-card mb-4" elevation="2">
        <v-card-text class="text-center py-8">
            <!-- Large Digital Clock Display -->
            <div class="digital-clock mb-6">
                <span class="clock-time">{{ currentTime }}</span>
            </div>

            <!-- Circular Progress with Timer -->
            <div class="progress-container mx-auto mb-6">
                <svg class="progress-ring" width="240" height="240">
                    <!-- Background circle -->
                    <circle
                        cx="120"
                        cy="120"
                        r="110"
                        fill="none"
                        stroke="#e0e0e0"
                        stroke-width="8"
                    />
                    <!-- Progress circle -->
                    <circle
                        cx="120"
                        cy="120"
                        r="110"
                        fill="none"
                        :stroke="progressColor"
                        stroke-width="8"
                        stroke-linecap="round"
                        :stroke-dasharray="`${progressPercentage * 6.91} 691`"
                        transform="rotate(-90 120 120)"
                        class="progress-circle"
                    />
                </svg>
                <div class="progress-content">
                    <v-icon :color="statusColor" size="40">{{ statusIcon }}</v-icon>
                    <div class="timer-text mt-2">{{ isWorking ? workTimer : totalHours }}</div>
                    <div class="status-text">{{ statusMessage }}</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <v-row justify="center" class="mb-4">
                <v-col cols="auto">
                    <v-btn
                        v-if="!isWorking"
                        @click="startWork"
                        :loading="isLoading"
                        color="success"
                        size="x-large"
                        rounded="pill"
                        elevation="2"
                        class="px-8"
                    >
                        <v-icon start>mdi-play-circle</v-icon>
                        Check In
                    </v-btn>

                    <div v-else class="d-flex gap-3">
                        <v-btn
                            @click="showBreakDialog = true"
                            :disabled="isOnBreak || isLoading"
                            color="warning"
                            size="large"
                            rounded="pill"
                            variant="outlined"
                        >
                            <v-icon start>mdi-coffee</v-icon>
                            {{ isOnBreak ? breakTimer : 'Take Break' }}
                        </v-btn>

                        <v-btn
                            v-if="isOnBreak"
                            @click="endBreak"
                            color="success"
                            size="large"
                            rounded="pill"
                        >
                            <v-icon start>mdi-play</v-icon>
                            Resume Work
                        </v-btn>

                        <v-btn
                            v-else
                            @click="endWork"
                            :loading="isLoading"
                            color="error"
                            size="large"
                            rounded="pill"
                            elevation="2"
                        >
                            <v-icon start>mdi-stop-circle</v-icon>
                            Check Out
                        </v-btn>
                    </div>
                </v-col>
            </v-row>
        </v-card-text>
    </v-card>

    <!-- Today's Summary Cards -->
    <v-row>
        <v-col cols="12" md="4">
            <v-card class="stat-card" elevation="1">
                <v-card-text class="d-flex align-center">
                    <div class="stat-icon-wrapper green">
                        <v-icon color="green">mdi-login</v-icon>
                    </div>
                    <div class="ml-4">
                        <div class="text-caption text-medium-emphasis">Check In</div>
                        <div class="text-h6 font-weight-medium">{{ startTime }}</div>
                    </div>
                </v-card-text>
            </v-card>
        </v-col>

        <v-col cols="12" md="4">
            <v-card class="stat-card" elevation="1">
                <v-card-text class="d-flex align-center">
                    <div class="stat-icon-wrapper red">
                        <v-icon color="red">mdi-logout</v-icon>
                    </div>
                    <div class="ml-4">
                        <div class="text-caption text-medium-emphasis">Check Out</div>
                        <div class="text-h6 font-weight-medium">{{ endTime }}</div>
                    </div>
                </v-card-text>
            </v-card>
        </v-col>

        <v-col cols="12" md="4">
            <v-card class="stat-card" elevation="1">
                <v-card-text class="d-flex align-center">
                    <div class="stat-icon-wrapper blue">
                        <v-icon color="blue">mdi-clock-time-four</v-icon>
                    </div>
                    <div class="ml-4">
                        <div class="text-caption text-medium-emphasis">Total Hours</div>
                        <div class="text-h6 font-weight-medium">{{ totalHours }}</div>
                    </div>
                </v-card-text>
            </v-card>
        </v-col>
    </v-row>

    <!-- Break Type Dialog -->
    <v-dialog v-model="showBreakDialog" max-width="400">
        <v-card>
            <v-card-title>Select Break Type</v-card-title>
            <v-card-text>
                <v-radio-group v-model="breakType">
                    <v-radio label="Tea Break" value="tea"></v-radio>
                    <v-radio label="Lunch Break" value="lunch"></v-radio>
                    <v-radio label="Personal Break" value="personal"></v-radio>
                </v-radio-group>
            </v-card-text>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn @click="showBreakDialog = false" text>Cancel</v-btn>
                <v-btn @click="startBreak" color="primary" variant="flat">Start Break</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.clock-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.digital-clock {
    position: relative;
}

.clock-time {
    font-size: 3.5rem;
    font-weight: 300;
    font-family: 'Roboto Mono', monospace;
    letter-spacing: 0.05em;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.progress-container {
    position: relative;
    width: 240px;
    height: 240px;
}

.progress-ring {
    transform: rotate(-90deg);
}

.progress-circle {
    transition: stroke-dasharray 0.3s ease;
}

.progress-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.timer-text {
    font-size: 1.75rem;
    font-weight: 500;
}

.status-text {
    font-size: 0.875rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
}

.stat-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.1;
}

.stat-icon-wrapper.green {
    background: rgba(76, 175, 80, 0.1);
}

.stat-icon-wrapper.red {
    background: rgba(244, 67, 54, 0.1);
}

.stat-icon-wrapper.blue {
    background: rgba(33, 150, 243, 0.1);
}

.stat-icon-wrapper .v-icon {
    opacity: 1;
}

/* Animations */
@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

.progress-content .v-icon {
    animation: pulse 2s infinite;
}
</style>