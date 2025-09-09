<script setup>
import {computed, onMounted, onUnmounted, ref} from 'vue'

// Props
const props = defineProps({
    officeHours: {
        type: Object,
        required: true
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
const totalWorkedSeconds = ref(0) // Accumulated work time for today

// Timer intervals
let clockInterval = null
let workInterval = null

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

const progressLabel = computed(() => {
    if (isWorking.value) {
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

// Get today's date key for localStorage
const getTodayKey = () => {
    return new Date().toDateString()
}

// Save work session to localStorage
const saveWorkSession = () => {
    const todayKey = getTodayKey()
    const workSessions = JSON.parse(localStorage.getItem('workSessions') || '{}')

    if (!workSessions[todayKey]) {
        workSessions[todayKey] = {
            sessions: [],
            totalWorkedSeconds: 0,
            currentSession: null
        }
    }

    workSessions[todayKey].currentSession = {
        startTime: workStartTime.value?.toISOString(),
        isWorking: isWorking.value
    }
    workSessions[todayKey].totalWorkedSeconds = totalWorkedSeconds.value

    localStorage.setItem('workSessions', JSON.stringify(workSessions))
}

// Load work session from localStorage
const loadWorkSession = () => {
    const todayKey = getTodayKey()
    const workSessions = JSON.parse(localStorage.getItem('workSessions') || '{}')
    const todayData = workSessions[todayKey]

    if (todayData) {
        totalWorkedSeconds.value = todayData.totalWorkedSeconds || 0

        // Show the first start time of the day
        if (todayData.sessions && todayData.sessions.length > 0) {
            const firstSession = todayData.sessions[0]
            startTime.value = formatTime(new Date(firstSession.startTime))

            // Show the last end time if all sessions are completed
            if (!todayData.currentSession || !todayData.currentSession.isWorking) {
                const lastSession = todayData.sessions[todayData.sessions.length - 1]
                if (lastSession.endTime) {
                    endTime.value = formatTime(new Date(lastSession.endTime))
                }
            }
        }

        // Check if there's an active session
        if (todayData.currentSession && todayData.currentSession.isWorking) {
            isWorking.value = true
            workStartTime.value = new Date(todayData.currentSession.startTime)

            // Don't override startTime.value if it's already set from first session
            if (startTime.value === '--:--') {
                startTime.value = formatTime(workStartTime.value)
            }

            // Start the work timer
            workInterval = setInterval(updateWorkTimer, 1000)
            updateWorkTimer()
        }

        // Calculate and display total hours for the day
        updateTotalHoursDisplay()
    }
}

// Update total hours display
const updateTotalHoursDisplay = () => {
    const totalHoursCalc = Math.floor(totalWorkedSeconds.value / 3600)
    const totalMinutes = Math.floor((totalWorkedSeconds.value % 3600) / 60)
    totalHours.value = `${totalHoursCalc}h ${totalMinutes}m`
}

// Complete a work session
const completeWorkSession = (sessionEndTime) => {
    const todayKey = getTodayKey()
    const workSessions = JSON.parse(localStorage.getItem('workSessions') || '{}')

    if (!workSessions[todayKey]) {
        workSessions[todayKey] = {sessions: [], totalWorkedSeconds: 0}
    }

    // Add completed session
    const sessionDuration = Math.floor((sessionEndTime - workStartTime.value) / 1000)
    workSessions[todayKey].sessions.push({
        startTime: workStartTime.value.toISOString(),
        endTime: sessionEndTime.toISOString(),
        duration: sessionDuration
    })

    // Update total worked time
    totalWorkedSeconds.value += sessionDuration
    workSessions[todayKey].totalWorkedSeconds = totalWorkedSeconds.value
    workSessions[todayKey].currentSession = null

    localStorage.setItem('workSessions', JSON.stringify(workSessions))
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
    const totalSecondsToday = totalWorkedSeconds.value + currentSessionSeconds

    // Update current session timer
    const hours = Math.floor(currentSessionSeconds / 3600)
    const minutes = Math.floor((currentSessionSeconds % 3600) / 60)
    const seconds = currentSessionSeconds % 60
    workTimer.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`

    // Calculate progress based on total worked time today vs office hours
    const totalOfficeSeconds = getTotalOfficeHours()
    progressPercentage.value = Math.min((totalSecondsToday / totalOfficeSeconds) * 100, 100)

    // Update total hours display with accumulated time
    const totalHoursCalc = Math.floor(totalSecondsToday / 3600)
    const totalMinutes = Math.floor((totalSecondsToday % 3600) / 60)
    totalHours.value = `${totalHoursCalc}h ${totalMinutes}m`

    // Save current state
    saveWorkSession()

    emit('attendance-updated', {
        isWorking: isWorking.value,
        duration: currentSessionSeconds,
        totalDuration: totalSecondsToday,
        progressPercentage: progressPercentage.value
    })
}

// Start work session
const startWork = () => {
    isWorking.value = true
    workStartTime.value = new Date()

    // If this is the first session of the day, set the start time
    if (startTime.value === '--:--') {
        startTime.value = formatTime(workStartTime.value)
    }

    endTime.value = '--:--' // Reset end time for new session

    workInterval = setInterval(updateWorkTimer, 1000)
    updateWorkTimer()

    // Save to localStorage
    saveWorkSession()

    emit('work-started', {
        startTime: workStartTime.value
    })
}

// End work session
const endWork = () => {
    isWorking.value = false
    const workEndTime = new Date()
    endTime.value = formatTime(workEndTime)

    // Complete this session and save to localStorage
    completeWorkSession(workEndTime)

    // Clear work timer but keep accumulated time
    clearInterval(workInterval)
    workInterval = null
    workTimer.value = '00:00:00'

    // Update display with total accumulated time
    updateTotalHoursDisplay()

    emit('work-ended', {
        startTime: workStartTime.value,
        endTime: workEndTime,
        totalHours: totalHours.value,
        totalWorkedSeconds: totalWorkedSeconds.value
    })
}

// Toggle attendance
const handleToggleAttendance = () => {
    isWorking.value ? endWork() : startWork()
}

// Lifecycle
onMounted(() => {
    updateClock()
    clockInterval = setInterval(updateClock, 1000)

    // Load any existing work session for today
    loadWorkSession()
})

onUnmounted(() => {
    clearInterval(clockInterval)
    clearInterval(workInterval)
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
                <v-btn
                    :color="isWorking ? 'error' : 'success'"
                    class="px-8 mt-4"
                    elevation="2"
                    size="small"
                    @click="handleToggleAttendance"
                >
                    <v-icon class="me-2">{{ isWorking ? 'mdi-stop' : 'mdi-play' }}</v-icon>
                    {{ isWorking ? 'End Work' : 'Start Work' }}
                </v-btn>
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
                <v-col cols="4">
                    <div class="text-h6">{{ startTime }}</div>
                    <div class="text-caption text-medium-emphasis">Start Time</div>
                </v-col>
                <v-col cols="4">
                    <div class="text-h6">{{ endTime }}</div>
                    <div class="text-caption text-medium-emphasis">End Time</div>
                </v-col>
                <v-col cols="4">
                    <div class="text-h6 text-primary">{{ totalHours }}</div>
                    <div class="text-caption text-medium-emphasis">Total Hours</div>
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
