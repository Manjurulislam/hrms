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
    },
    // Public client IP resolved once at the page level (used in check-in/out payloads)
    clientIp: {
        type: String,
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

// Timer intervals
let clockInterval = null
let workInterval = null
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

        // Set summary times
        if (props.todayData.summary) {
            startTime.value = props.todayData.summary.firstCheckIn || '--:--'
            endTime.value = props.todayData.summary.lastCheckOut || '--:--'
            totalHours.value = props.todayData.summary.totalHours || '0h 0m'
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

// Hero progress ring (r=104 → circumference ≈ 653)
const RING_CIRCUMFERENCE = 653
const ringDashoffset = computed(() =>
    RING_CIRCUMFERENCE * (1 - Math.min(progressPercentage.value, 100) / 100)
)

const ctaLabel = computed(() => (isWorking.value ? 'CHECK OUT' : 'CHECK IN'))
const ctaSub = computed(() => (isWorking.value ? 'Tap to end work' : 'Tap to start work'))

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
            note: null,
            client_ip: props.clientIp
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
            note: null,
            client_ip: props.clientIp
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


// Toggle attendance
const handleToggleAttendance = () => {
    if (isLoading.value) return
    isWorking.value ? endWork() : startWork()
}

// Sync with server periodically
const syncWithServer = async () => {
    // Skip while a check-in/out mutation is in flight — stale poll data
    // can otherwise flip local state back before the mutation response lands.
    if (isLoading.value) return

    try {
        const response = await axios.get(route('emp-attendance.current-status'))

        // A mutation may have started during the network round-trip — drop the
        // now-stale response rather than applying it over fresh local state.
        if (isLoading.value) return

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
    clearInterval(syncInterval)
})
</script>

<template>
    <section class="card hero" :class="{ working: isWorking }">
        <div class="hero-col">
            <div class="clock tnum">{{ currentTime }}</div>
            <div class="date">{{ currentWeekday }}, {{ currentDateOnly }}</div>

            <div class="tapwrap">
                <span class="pulse" v-if="!isLoading"></span>
                <svg class="ring" viewBox="0 0 224 224" aria-hidden="true">
                    <circle cx="112" cy="112" r="104" fill="none" class="ring-track" stroke-width="7"/>
                    <circle cx="112" cy="112" r="104" fill="none" stroke="url(#ring-grad)" stroke-width="7"
                            stroke-linecap="round" stroke-dasharray="653" :stroke-dashoffset="ringDashoffset"
                            class="ring-fill"/>
                    <defs>
                        <linearGradient id="ring-grad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" :stop-color="isWorking ? '#FF5C93' : '#4F7BFF'"/>
                            <stop offset="1" :stop-color="isWorking ? '#C23CD4' : '#7A5CFF'"/>
                        </linearGradient>
                    </defs>
                </svg>
                <button
                    class="tapbtn"
                    type="button"
                    :disabled="isLoading"
                    @click="handleToggleAttendance"
                >
                    <v-progress-circular v-if="isLoading" indeterminate size="40" width="3" color="white"/>
                    <template v-else>
                        <svg class="hand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 11V5.5a1.5 1.5 0 0 1 3 0V11m0-1.5V4a1.5 1.5 0 0 1 3 0v6m0-1V6a1.5 1.5 0 0 1 3 0v6.5m0 0V9a1.5 1.5 0 0 1 3 0v6.2c0 3.2-2.4 5.8-6 5.8-2.3 0-3.6-.8-4.9-2.2l-3.7-4a1.6 1.6 0 0 1 2.3-2.2L8 15"/>
                        </svg>
                        <span class="cta">{{ ctaLabel }}<small>{{ ctaSub }}</small></span>
                    </template>
                </button>
            </div>

            <div class="worktag" :class="{ off: !isWorking }">
                <span class="live" v-if="isWorking"></span>
                Work duration <b class="tnum">{{ isWorking ? workTimer : totalHours }}</b>
            </div>

            <div v-if="isOfficeHoursComplete && !isWorking" class="done-chip">
                <v-icon size="14" class="me-1">mdi-check-circle</v-icon>
                Office hours completed for today
            </div>

            <div class="today">
                <div class="tstat">
                    <div class="ic ci">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5M15 12H3"/></svg>
                    </div>
                    <div class="tmeta"><div class="val tnum">{{ startTime }}</div><div class="cap">Check In</div></div>
                </div>
                <div class="tstat">
                    <div class="ic co">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>
                    </div>
                    <div class="tmeta"><div class="val tnum">{{ endTime }}</div><div class="cap">Check Out</div></div>
                </div>
                <div class="tstat">
                    <div class="ic wh">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    </div>
                    <div class="tmeta"><div class="val tnum">{{ totalHours }}</div><div class="cap">Working Hours</div></div>
                </div>
            </div>
        </div>
    </section>
</template>

