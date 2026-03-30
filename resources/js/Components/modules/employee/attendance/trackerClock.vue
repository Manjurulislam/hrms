<script setup>
import {computed, onMounted, onUnmounted, ref} from 'vue'
import axios from 'axios'
import {useToast} from 'vue-toastification'

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
const isOnBreak = ref(false)
const workStartTime = ref(null)
const breakStartTime = ref(null)
const startTime = ref('--:--')
const endTime = ref('--:--')
const totalHours = ref('0h 0m')
const workTimer = ref('00:00:00')
const breakTimer = ref('00:00:00')
const progressPercentage = ref(0)
const totalWorkedSeconds = ref(0)
const totalBreakTime = ref(null)
const isLoading = ref(false)

// Timer intervals
let clockInterval = null
let workInterval = null
let breakInterval = null
let syncInterval = null

// Initialize from server data
const initializeFromServerData = () => {
    if (props.todayData) {
        // Set total worked seconds from server
        totalWorkedSeconds.value = props.todayData.totalWorkedSeconds || 0

        // If there's an active session
        if (props.todayData.currentSession) {
            isWorking.value = true
            workStartTime.value = new Date(props.todayData.currentSession.startTime)

            // Start the work timer
            workInterval = setInterval(updateWorkTimer, 1000)
            updateWorkTimer()
        }

        // Check for active break
        if (props.todayData.currentBreak) {
            isOnBreak.value = true
            breakStartTime.value = new Date(props.todayData.currentBreak.startTime)

            // Start the break timer
            breakInterval = setInterval(updateBreakTimer, 1000)
            updateBreakTimer()
        }

        // Set summary times
        if (props.todayData.summary) {
            startTime.value = props.todayData.summary.firstCheckIn || '--:--'
            endTime.value = props.todayData.summary.lastCheckOut || '--:--'
            totalHours.value = props.todayData.summary.totalHours || '0h 0m'
            totalBreakTime.value = props.todayData.summary.totalBreakTime || null
        }

        // Update progress
        updateProgress()
    }
}

// Computed properties for separated date display
const currentWeekday = computed(() => {
    if (!currentDate.value) return ''
    return currentDate.value.split(', ')[0]
})

const currentDateOnly = computed(() => {
    if (!currentDate.value) return ''
    const parts = currentDate.value.split(', ')
    return parts.slice(1).join(', ')
})

const isOfficeHoursComplete = computed(() => {
    const totalOfficeSeconds = getTotalOfficeHours()
    let totalSeconds = totalWorkedSeconds.value
    if (isWorking.value && workStartTime.value) {
        totalSeconds += Math.floor((new Date() - workStartTime.value) / 1000)
    }
    return totalSeconds >= totalOfficeSeconds
})

const progressLabel = computed(() => {
    if (isOfficeHoursComplete.value && !isWorking.value) {
        return 'Completed'
    } else if (isWorking.value) {
        return 'Work Duration'
    } else if (totalWorkedSeconds.value > 0) {
        return 'Total Today'
    } else {
        return 'Ready to Work'
    }
})

const progressColor = computed(() => {
    if (progressPercentage.value >= 75) return '#27AE60' // Green
    if (progressPercentage.value >= 50) return '#3498DB' // Blue
    return '#F39C12' // Orange
})

const progressOffset = computed(() => {
    const circumference = 534
    return circumference - (progressPercentage.value / 100) * circumference
})

// Helper function for time formatting
const formatTime = (date, includeSeconds = false) => {
    return date.toLocaleTimeString('en-US', {
        hour12: true,
        hour: 'numeric',
        minute: '2-digit',
        ...(includeSeconds && {second: '2-digit'})
    })
}

// Helper function to parse time string to minutes
const parseTimeToMinutes = (timeString) => {
    const [time, period] = timeString.split(' ')
    let [hours, minutes] = time.split(':').map(Number)

    if (period === 'PM' && hours !== 12) hours += 12
    if (period === 'AM' && hours === 12) hours = 0

    return hours * 60 + minutes
}

// Calculate total office hours in seconds
const getTotalOfficeHours = () => {
    const startMinutes = parseTimeToMinutes(props.officeHours.start)
    const endMinutes = parseTimeToMinutes(props.officeHours.end)
    return (endMinutes - startMinutes) * 60 // Convert to seconds
}

// Update current time and date
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

// Update work timer and progress
const updateWorkTimer = () => {
    if (!isWorking.value || !workStartTime.value) return

    const now = new Date()
    const currentSessionSeconds = Math.floor((now - workStartTime.value) / 1000)

    // Total seconds including previous sessions
    const totalSecondsToday = totalWorkedSeconds.value + currentSessionSeconds

    // Update current session timer
    const hours = Math.floor(currentSessionSeconds / 3600)
    const minutes = Math.floor((currentSessionSeconds % 3600) / 60)
    const seconds = currentSessionSeconds % 60
    workTimer.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`

    // Update total hours display
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

// Update break timer
const updateBreakTimer = () => {
    if (!isOnBreak.value || !breakStartTime.value) return

    const now = new Date()
    const breakSeconds = Math.floor((now - breakStartTime.value) / 1000)

    const hours = Math.floor(breakSeconds / 3600)
    const minutes = Math.floor((breakSeconds % 3600) / 60)
    const seconds = breakSeconds % 60
    breakTimer.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
}

// Update progress calculation
const updateProgress = () => {
    let totalSeconds = totalWorkedSeconds.value

    if (isWorking.value && workStartTime.value) {
        const currentSessionSeconds = Math.floor((new Date() - workStartTime.value) / 1000)
        totalSeconds += currentSessionSeconds
    }

    const totalOfficeSeconds = getTotalOfficeHours()
    progressPercentage.value = Math.min((totalSeconds / totalOfficeSeconds) * 100, 100)
}

// Start work session using axios
const startWork = async () => {
    if (isLoading.value) return

    isLoading.value = true

    try {
        const response = await axios.post(route('emp-attendance.start-work'), {
            location: 'office',
            note: null
        })

        if (response.data.success) {
            // Update from server response
            const todayData = response.data.todayData
            totalWorkedSeconds.value = todayData.totalWorkedSeconds || 0

            if (todayData.currentSession) {
                isWorking.value = true
                workStartTime.value = new Date(todayData.currentSession.startTime)

                // Update display times
                if (todayData.summary) {
                    startTime.value = todayData.summary.firstCheckIn || formatTime(workStartTime.value)
                    endTime.value = '--:--'
                }

                // Start timer
                workInterval = setInterval(updateWorkTimer, 1000)
                updateWorkTimer()

                toast.success(response.data.message || 'Work started successfully!')
                emit('work-started', {
                    startTime: workStartTime.value
                })
            }
        }
    } catch (error) {
        if (error.response && error.response.status === 422) {
            const message = error.response.data.message || error.response.data.errors?.session?.[0] || 'Failed to start work'
            toast.error(message)
        } else {
            toast.error('An error occurred while starting work')
        }
    } finally {
        isLoading.value = false
    }
}

// End work session using axios
const endWork = async () => {
    if (isLoading.value) return

    isLoading.value = true

    try {
        const response = await axios.post(route('emp-attendance.end-work'), {
            location: 'office',
            note: null
        })

        if (response.data.success) {
            // Update from server response
            const todayData = response.data.todayData
            isWorking.value = false

            // Update total worked seconds
            totalWorkedSeconds.value = todayData.totalWorkedSeconds || 0

            // Update display times
            if (todayData.summary) {
                endTime.value = todayData.summary.lastCheckOut || formatTime(new Date())
                totalHours.value = todayData.summary.totalHours || totalHours.value
                totalBreakTime.value = todayData.summary.totalBreakTime || totalBreakTime.value
            }

            // Clear work timer
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
        console.error('End work error:', error)
        if (error.response && error.response.status === 422) {
            const message = error.response.data.message || error.response.data.errors?.session?.[0] || 'Failed to end work'
            toast.error(message)
        } else {
            toast.error('An error occurred while ending work: ' + (error.message || 'Unknown error'))
        }
    } finally {
        isLoading.value = false
    }
}

// Start break using axios
const startBreak = async () => {
    if (isLoading.value || !isWorking.value) return

    isLoading.value = true

    try {
        const response = await axios.post(route('emp-attendance.start-break'), {
            break_type: 'personal',
            reason: null
        })

        if (response.data.success) {
            isOnBreak.value = true
            breakStartTime.value = new Date()

            // Start break timer
            breakInterval = setInterval(updateBreakTimer, 1000)
            updateBreakTimer()

            toast.success(response.data.message || 'Break started successfully!')
        }
    } catch (error) {
        console.error('Start break error:', error)
        if (error.response && error.response.status === 422) {
            const message = error.response.data.message || error.response.data.errors?.break?.[0] || 'Failed to start break'
            toast.error(message)
        } else {
            toast.error('An error occurred while starting break: ' + (error.message || 'Unknown error'))
        }
    } finally {
        isLoading.value = false
    }
}

// End break using axios
const endBreak = async () => {
    if (isLoading.value || !isOnBreak.value) return

    isLoading.value = true

    try {
        const response = await axios.post(route('emp-attendance.end-break'))

        if (response.data.success) {
            isOnBreak.value = false

            // Clear break timer
            clearInterval(breakInterval)
            breakInterval = null
            breakTimer.value = '00:00:00'
            breakStartTime.value = null

            toast.success(response.data.message || 'Break ended successfully!')
        }
    } catch (error) {
        console.error('End break error:', error)
        if (error.response && error.response.status === 422) {
            const message = error.response.data.message || error.response.data.errors?.break?.[0] || 'Failed to end break'
            toast.error(message)
        } else {
            toast.error('An error occurred while ending break: ' + (error.message || 'Unknown error'))
        }
    } finally {
        isLoading.value = false
    }
}

// Toggle attendance
const handleToggleAttendance = () => {
    if (isLoading.value) return
    isWorking.value ? endWork() : startWork()
}

// Sync with server periodically
const syncWithServer = async () => {
    try {
        const response = await axios.get(route('emp-attendance.current-status'))

        if (response.data.success && response.data.data) {
            const serverData = response.data.data

            // Update local state from server
            totalWorkedSeconds.value = serverData.totalWorkedSeconds || 0

            // Check if session status changed
            if (serverData.currentSession && !isWorking.value) {
                // Session started elsewhere
                isWorking.value = true
                workStartTime.value = new Date(serverData.currentSession.startTime)

                // Start timer
                if (!workInterval) {
                    workInterval = setInterval(updateWorkTimer, 1000)
                }
                updateWorkTimer()

                // Update display
                if (serverData.summary) {
                    startTime.value = serverData.summary.firstCheckIn || '--:--'
                    endTime.value = '--:--'
                    totalHours.value = serverData.summary.totalHours || '0h 0m'
                }
            } else if (!serverData.currentSession && isWorking.value) {
                // Session ended elsewhere
                isWorking.value = false
                clearInterval(workInterval)
                workInterval = null
                workTimer.value = '00:00:00'

                if (serverData.summary) {
                    endTime.value = serverData.summary.lastCheckOut || '--:--'
                    totalHours.value = serverData.summary.totalHours || '0h 0m'
                }
            } else if (serverData.summary) {
                // Just update totals
                totalHours.value = serverData.summary.totalHours || totalHours.value
            }

            // Check if break status changed
            if (serverData.currentBreak && !isOnBreak.value) {
                // Break started elsewhere
                isOnBreak.value = true
                breakStartTime.value = new Date(serverData.currentBreak.startTime)

                if (!breakInterval) {
                    breakInterval = setInterval(updateBreakTimer, 1000)
                }
                updateBreakTimer()
            } else if (!serverData.currentBreak && isOnBreak.value) {
                // Break ended elsewhere
                isOnBreak.value = false
                clearInterval(breakInterval)
                breakInterval = null
                breakTimer.value = '00:00:00'
                breakStartTime.value = null
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

    // Initialize from server data
    initializeFromServerData()

    // Sync with server every 30 seconds
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
    <v-row>
        <v-col cols="4">
            <v-card
                class="custom-blue text-white text-center d-flex flex-column justify-center align-center"
                elevation="3">
                <div class="text-h4 font-weight-light mb-2">{{ currentTime }}</div>
                <div class="text-body-1 mb-1">{{ currentWeekday }},</div>
                <div class="text-body-1 mb-3">{{ currentDateOnly }}</div>
                <div class="text-body-2 office-hours-inline">
                    <v-icon class="me-1" size="small">mdi-clock-outline</v-icon>
                    Office Time : {{ officeHours.start }} - {{ officeHours.end }}
                </div>
                <div v-if="officeHours.office_ip" class="text-body-2 office-hours-inline mt-2">
                    <v-icon class="me-1" size="small">mdi-ip-network</v-icon>
                    Office IP : {{ officeHours.office_ip }}
                </div>
            </v-card>
        </v-col>
        <v-col cols="8">
            <v-card class="text-center py-5" elevation="3">
                <div class="circular-progress-container">
                    <svg height="200" style="transform: rotate(-90deg);" width="200">
                        <circle cx="100" cy="100" fill="none"
                                r="85"
                                stroke="#E0E0E0"
                                stroke-width="10">
                        </circle>
                        <circle :stroke="progressColor" :stroke-dashoffset="progressOffset" cx="100"
                                cy="100"
                                fill="none"
                                r="85"
                                stroke-dasharray="534"
                                stroke-linecap="round"
                                stroke-width="10"
                                style="transition: stroke-dashoffset 0.3s ease, stroke 0.3s ease;">
                        </circle>
                    </svg>
                    <div class="progress-content">
                        <div class="timer-display">
                            {{ isWorking ? workTimer : totalHours }}
                        </div>
                        <div class="progress-label">{{ progressLabel }}</div>
                    </div>
                </div>
                <div v-if="isOfficeHoursComplete && !isWorking" class="mt-4">
                    <v-chip color="success" variant="flat" size="small">
                        <v-icon start size="small">mdi-check-circle</v-icon>
                        Office hours completed for today
                    </v-chip>
                </div>
                <div v-else class="d-flex gap-2 mt-4 flex-wrap justify-center">
                    <v-btn
                        :color="isWorking ? 'error' : 'success'"
                        :disabled="isLoading || isOnBreak"
                        :loading="isLoading && !isOnBreak"
                        class="px-6"
                        elevation="2"
                        size="small"
                        @click="handleToggleAttendance"
                    >
                        <v-icon class="me-2">{{ isWorking ? 'mdi-stop' : 'mdi-play' }}</v-icon>
                        {{ isWorking ? 'End Work' : 'Start Work' }}
                    </v-btn>

                    <v-btn
                        v-if="isWorking"
                        :color="isOnBreak ? 'warning' : 'info'"
                        :disabled="isLoading"
                        :loading="isLoading && isOnBreak"
                        class="px-6"
                        elevation="2"
                        size="small"
                        @click="isOnBreak ? endBreak() : startBreak()"
                    >
                        <v-icon class="me-2">{{ isOnBreak ? 'mdi-play-circle' : 'mdi-coffee' }}</v-icon>
                        {{ isOnBreak ? 'End Break' : 'Start Break' }}
                    </v-btn>
                </div>
            </v-card>
        </v-col>
    </v-row>
    <v-card class="mt-3" elevation="3">
        <!-- Today's Summary -->
        <v-card-text>
            <v-card-title class="text-subtitle-2 pa-0 mb-3">
                <v-icon class="me-2">mdi-calendar-today</v-icon>
                Today's Summary
            </v-card-title>
            <v-row class="text-center">
                <v-col cols="3">
                    <div class="text-h6">{{ startTime }}</div>
                    <div class="text-caption text-medium-emphasis">Start Time</div>
                </v-col>
                <v-col cols="3">
                    <div class="text-h6">{{ endTime }}</div>
                    <div class="text-caption text-medium-emphasis">End Time</div>
                </v-col>
                <v-col cols="3">
                    <div class="text-h6 text-primary">{{ totalHours }}</div>
                    <div class="text-caption text-medium-emphasis">Total Hours</div>
                </v-col>
                <v-col cols="3">
                    <div class="text-h6" :class="isOnBreak ? 'text-warning' : ''">
                        {{ isOnBreak ? breakTimer : (totalBreakTime || '--:--') }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                        {{ isOnBreak ? 'On Break' : (totalBreakTime ? 'Break Time' : 'No Break') }}
                    </div>
                </v-col>
            </v-row>
        </v-card-text>
    </v-card>
</template>

<style scoped>
.custom-blue {
    background: linear-gradient(135deg, #1B4F72 0%, #3498DB 100%) !important;
    height: 100%;
}

.circular-progress-container {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
}

.progress-content {
    position: absolute;
    text-align: center;
    z-index: 1;
}

.timer-display {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1B4F72;
    margin-bottom: 0.25rem;
}

.progress-label {
    font-size: 0.875rem;
    color: #6C757D;
}

.office-hours-inline {
    opacity: 0.9;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 8px 16px;
    display: inline-block;
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