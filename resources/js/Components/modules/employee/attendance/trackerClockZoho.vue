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
    },
    userInfo: {
        type: Object,
        required: true
    }
})

// Emits
const emit = defineEmits(['work-started', 'work-ended', 'attendance-updated'])

// Reactive state
const currentTime = ref('')
const currentDate = ref('')
const currentDay = ref('')
const isWorking = ref(false)
const workStartTime = ref(null)
const startTime = ref('--:--')
const endTime = ref('--:--')
const totalHours = ref('00:00')
const workTimer = ref('00:00:00')
const totalWorkedSeconds = ref(0)
const isLoading = ref(false)
const showNoteDialog = ref(false)
const checkNote = ref('')
const noteType = ref('checkin')
const recentActivities = ref([])
const locationStatus = ref('Office')
const shiftTiming = ref('')

// Break management
const showBreakDialog = ref(false)
const breakType = ref('lunch')
const breakReason = ref('')
const isOnBreak = ref(false)
const breakStartTime = ref(null)
const breakTimer = ref('00:00')
const activeBreak = ref(null)

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
            totalHours.value = props.todayData.summary.totalHours || '00:00'
        }

        // Load sessions as activities
        if (props.todayData.sessions) {
            recentActivities.value = props.todayData.sessions.map(session => ({
                type: session.status === 'active' ? 'checkin' : 'checkout',
                time: new Date(session.startTime).toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                }),
                status: session.status,
                duration: session.duration
            }))
        }
    }

    // Set shift timing
    shiftTiming.value = `${props.officeHours.start} - ${props.officeHours.end}`
}

// Computed properties
const formattedDate = computed(() => {
    if (!currentDate.value) return ''
    const date = new Date(currentDate.value)
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
})

const greetingMessage = computed(() => {
    const hour = new Date().getHours()
    const name = props.userInfo?.name?.split(' ')[0] || 'there'

    if (hour < 12) return `Good Morning, ${name}!`
    if (hour < 17) return `Good Afternoon, ${name}!`
    return `Good Evening, ${name}!`
})

const attendanceStatus = computed(() => {
    if (isOnBreak.value) return { text: 'On Break', color: 'warning', icon: 'mdi-coffee' }
    if (isWorking.value) return { text: 'Checked In', color: 'success', icon: 'mdi-check-circle' }
    if (totalWorkedSeconds.value > 0) return { text: 'Checked Out', color: 'info', icon: 'mdi-clock-check' }
    return { text: 'Not Checked In', color: 'error', icon: 'mdi-clock-alert' }
})

const progressPercentage = computed(() => {
    const totalOfficeSeconds = 8 * 60 * 60 // 8 hours
    let totalSeconds = totalWorkedSeconds.value

    if (isWorking.value && workStartTime.value) {
        const currentSessionSeconds = Math.floor((new Date() - workStartTime.value) / 1000)
        totalSeconds += currentSessionSeconds
    }

    return Math.min(Math.round((totalSeconds / totalOfficeSeconds) * 100), 100)
})

const canCheckIn = computed(() => !isWorking.value && !isOnBreak.value)
const canCheckOut = computed(() => isWorking.value && !isOnBreak.value)
const canStartBreak = computed(() => isWorking.value && !isOnBreak.value)
const canEndBreak = computed(() => isOnBreak.value)

// Helper functions
const updateClock = () => {
    const now = new Date()
    currentTime.value = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    })
    currentDate.value = now.toDateString()
    currentDay.value = now.toLocaleDateString('en-US', { weekday: 'long' })
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
    totalHours.value = `${totalHoursCalc.toString().padStart(2, '0')}:${totalMinutes.toString().padStart(2, '0')}`

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

const addActivity = (type, message) => {
    const now = new Date()
    recentActivities.value.unshift({
        type,
        time: now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
        message,
        timestamp: now
    })

    // Keep only last 5 activities
    if (recentActivities.value.length > 5) {
        recentActivities.value.pop()
    }
}

// Actions
const showCheckInDialog = () => {
    noteType.value = 'checkin'
    checkNote.value = ''
    showNoteDialog.value = true
}

const showCheckOutDialog = () => {
    noteType.value = 'checkout'
    checkNote.value = ''
    showNoteDialog.value = true
}

const performCheckAction = async () => {
    showNoteDialog.value = false

    if (noteType.value === 'checkin') {
        await startWork()
    } else {
        await endWork()
    }
}

const startWork = async () => {
    if (isLoading.value) return
    isLoading.value = true

    try {
        const response = await axios.post('/emp-attendance/start-work', {
            location: locationStatus.value.toLowerCase(),
            note: checkNote.value || null
        })

        if (response.data.success) {
            const todayData = response.data.todayData
            totalWorkedSeconds.value = todayData.totalWorkedSeconds || 0

            if (todayData.currentSession) {
                isWorking.value = true
                workStartTime.value = new Date(todayData.currentSession.startTime)

                if (todayData.summary) {
                    startTime.value = todayData.summary.firstCheckIn
                }

                workInterval = setInterval(updateWorkTimer, 1000)
                updateWorkTimer()

                addActivity('checkin', 'Checked in successfully')
                toast.success('Checked in successfully!')
                emit('work-started', { startTime: workStartTime.value })
            }
        }
    } catch (error) {
        const message = error.response?.data?.message || 'Failed to check in'
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
            location: locationStatus.value.toLowerCase(),
            note: checkNote.value || null
        })

        if (response.data.success) {
            const todayData = response.data.todayData
            isWorking.value = false
            totalWorkedSeconds.value = todayData.totalWorkedSeconds || 0

            if (todayData.summary) {
                endTime.value = todayData.summary.lastCheckOut
                totalHours.value = todayData.summary.totalHours
            }

            clearInterval(workInterval)
            workInterval = null
            workTimer.value = '00:00:00'

            addActivity('checkout', `Checked out - Total: ${totalHours.value}`)
            toast.success('Checked out successfully!')
            emit('work-ended', {
                startTime: workStartTime.value,
                endTime: new Date(),
                totalHours: totalHours.value,
                totalWorkedSeconds: totalWorkedSeconds.value
            })

            workStartTime.value = null
        }
    } catch (error) {
        const message = error.response?.data?.message || 'Failed to check out'
        toast.error(message)
    } finally {
        isLoading.value = false
    }
}

const startBreak = async () => {
    if (isLoading.value || !isWorking.value) return
    showBreakDialog.value = false
    isLoading.value = true

    try {
        const response = await axios.post('/emp-attendance/start-break', {
            break_type: breakType.value,
            reason: breakReason.value || null
        })

        if (response.data.success) {
            isOnBreak.value = true
            breakStartTime.value = new Date()
            activeBreak.value = response.data.break
            breakInterval = setInterval(updateBreakTimer, 1000)

            addActivity('break', `${breakType.value} break started`)
            toast.success('Break started')
        }
    } catch (error) {
        toast.error('Failed to start break')
    } finally {
        isLoading.value = false
    }
}

const endBreak = async () => {
    if (isLoading.value || !isOnBreak.value) return
    isLoading.value = true

    try {
        const response = await axios.post('/emp-attendance/end-break')

        if (response.data.success) {
            isOnBreak.value = false
            clearInterval(breakInterval)
            breakTimer.value = '00:00'

            addActivity('resume', `Resumed work after ${response.data.breakDuration || 'break'}`)
            toast.success('Break ended')

            breakStartTime.value = null
            activeBreak.value = null
        }
    } catch (error) {
        toast.error('Failed to end break')
    } finally {
        isLoading.value = false
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
            } else if (!serverData.currentSession && isWorking.value) {
                isWorking.value = false
                clearInterval(workInterval)
                workInterval = null
                workTimer.value = '00:00:00'
            }

            if (serverData.summary) {
                startTime.value = serverData.summary.firstCheckIn || '--:--'
                endTime.value = serverData.summary.lastCheckOut || '--:--'
                totalHours.value = serverData.summary.totalHours || '00:00'
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
    <v-container fluid class="pa-0">
        <!-- Top Header with Greeting -->
        <v-card flat class="mb-4 greeting-card" color="primary">
            <v-card-text class="pa-6">
                <v-row align="center">
                    <v-col cols="12" md="8">
                        <h1 class="text-h4 font-weight-medium white--text mb-2">
                            {{ greetingMessage }}
                        </h1>
                        <p class="text-subtitle-1 white--text opacity-90">
                            {{ currentDay }}, {{ formattedDate }}
                        </p>
                    </v-col>
                    <v-col cols="12" md="4" class="text-md-right">
                        <div class="digital-time white--text">
                            {{ currentTime }}
                        </div>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <!-- Main Content Area -->
        <v-row>
            <!-- Left Column - Check In/Out Card -->
            <v-col cols="12" md="8">
                <!-- Primary Action Card -->
                <v-card class="mb-4 action-card" elevation="2">
                    <v-card-text class="pa-6">
                        <!-- Status Header -->
                        <div class="d-flex align-center mb-6">
                            <v-avatar :color="attendanceStatus.color" size="56" class="mr-4">
                                <v-icon color="white" size="28">{{ attendanceStatus.icon }}</v-icon>
                            </v-avatar>
                            <div>
                                <h2 class="text-h5 font-weight-medium">{{ attendanceStatus.text }}</h2>
                                <p class="text-body-2 text-medium-emphasis mb-0">
                                    Shift: {{ shiftTiming }} | Location: {{ locationStatus }}
                                </p>
                            </div>
                            <v-spacer></v-spacer>
                            <v-chip v-if="isWorking" color="success" variant="tonal" size="large">
                                <v-icon start>mdi-timer</v-icon>
                                {{ workTimer }}
                            </v-chip>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-6">
                            <div class="d-flex justify-space-between mb-2">
                                <span class="text-body-2">Today's Progress</span>
                                <span class="text-body-2 font-weight-medium">{{ progressPercentage }}%</span>
                            </div>
                            <v-progress-linear
                                :model-value="progressPercentage"
                                height="8"
                                rounded
                                :color="progressPercentage >= 100 ? 'success' : 'primary'"
                            ></v-progress-linear>
                        </div>

                        <!-- Time Stats -->
                        <v-row class="mb-6">
                            <v-col cols="4">
                                <div class="stat-box text-center pa-3">
                                    <v-icon color="success" size="24" class="mb-2">mdi-login</v-icon>
                                    <div class="text-caption text-medium-emphasis">Check In</div>
                                    <div class="text-h6 font-weight-medium">{{ startTime }}</div>
                                </div>
                            </v-col>
                            <v-col cols="4">
                                <div class="stat-box text-center pa-3">
                                    <v-icon color="error" size="24" class="mb-2">mdi-logout</v-icon>
                                    <div class="text-caption text-medium-emphasis">Check Out</div>
                                    <div class="text-h6 font-weight-medium">{{ endTime }}</div>
                                </div>
                            </v-col>
                            <v-col cols="4">
                                <div class="stat-box text-center pa-3">
                                    <v-icon color="primary" size="24" class="mb-2">mdi-clock-time-eight</v-icon>
                                    <div class="text-caption text-medium-emphasis">Total Hours</div>
                                    <div class="text-h6 font-weight-medium">{{ totalHours }}</div>
                                </div>
                            </v-col>
                        </v-row>

                        <!-- Action Buttons -->
                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-btn
                                    v-if="canCheckIn"
                                    @click="showCheckInDialog"
                                    color="success"
                                    size="x-large"
                                    block
                                    elevation="0"
                                    class="text-none"
                                    :loading="isLoading"
                                >
                                    <v-icon start>mdi-login</v-icon>
                                    Check In
                                </v-btn>
                                <v-btn
                                    v-else-if="canCheckOut"
                                    @click="showCheckOutDialog"
                                    color="error"
                                    size="x-large"
                                    block
                                    elevation="0"
                                    class="text-none"
                                    :loading="isLoading"
                                >
                                    <v-icon start>mdi-logout</v-icon>
                                    Check Out
                                </v-btn>
                                <v-btn
                                    v-else
                                    disabled
                                    size="x-large"
                                    block
                                    elevation="0"
                                    class="text-none"
                                >
                                    Already Checked Out
                                </v-btn>
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-btn
                                    v-if="canStartBreak"
                                    @click="showBreakDialog = true"
                                    color="warning"
                                    size="x-large"
                                    block
                                    variant="outlined"
                                    class="text-none"
                                >
                                    <v-icon start>mdi-coffee</v-icon>
                                    Take Break
                                </v-btn>
                                <v-btn
                                    v-else-if="canEndBreak"
                                    @click="endBreak"
                                    color="success"
                                    size="x-large"
                                    block
                                    elevation="0"
                                    class="text-none"
                                    :loading="isLoading"
                                >
                                    <v-icon start>mdi-play</v-icon>
                                    Resume Work ({{ breakTimer }})
                                </v-btn>
                                <v-btn
                                    v-else
                                    disabled
                                    size="x-large"
                                    block
                                    variant="outlined"
                                    class="text-none"
                                >
                                    Break Not Available
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Quick Stats Cards -->
                <v-row>
                    <v-col cols="6" sm="3">
                        <v-card class="stat-card" flat>
                            <v-card-text class="text-center pa-4">
                                <v-icon color="primary" size="32" class="mb-2">mdi-calendar-check</v-icon>
                                <div class="text-h6 font-weight-medium">22</div>
                                <div class="text-caption text-medium-emphasis">Present Days</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <v-card class="stat-card" flat>
                            <v-card-text class="text-center pa-4">
                                <v-icon color="error" size="32" class="mb-2">mdi-calendar-remove</v-icon>
                                <div class="text-h6 font-weight-medium">2</div>
                                <div class="text-caption text-medium-emphasis">Absent Days</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <v-card class="stat-card" flat>
                            <v-card-text class="text-center pa-4">
                                <v-icon color="warning" size="32" class="mb-2">mdi-clock-alert</v-icon>
                                <div class="text-h6 font-weight-medium">3</div>
                                <div class="text-caption text-medium-emphasis">Late Days</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="3">
                        <v-card class="stat-card" flat>
                            <v-card-text class="text-center pa-4">
                                <v-icon color="success" size="32" class="mb-2">mdi-percent</v-icon>
                                <div class="text-h6 font-weight-medium">91%</div>
                                <div class="text-caption text-medium-emphasis">Attendance</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-col>

            <!-- Right Column - Activity Feed -->
            <v-col cols="12" md="4">
                <!-- Today's Activity -->
                <v-card class="activity-card" elevation="2">
                    <v-card-title class="text-h6 font-weight-medium">
                        <v-icon start color="primary">mdi-history</v-icon>
                        Today's Activity
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text class="pa-0">
                        <v-list v-if="recentActivities.length > 0" density="compact">
                            <v-list-item
                                v-for="(activity, index) in recentActivities"
                                :key="index"
                                class="py-3"
                            >
                                <template v-slot:prepend>
                                    <v-avatar size="32" :color="activity.type === 'checkin' ? 'success' : activity.type === 'checkout' ? 'error' : 'warning'">
                                        <v-icon size="18" color="white">
                                            {{
                                                activity.type === 'checkin' ? 'mdi-login' :
                                                activity.type === 'checkout' ? 'mdi-logout' :
                                                activity.type === 'break' ? 'mdi-coffee' :
                                                'mdi-play'
                                            }}
                                        </v-icon>
                                    </v-avatar>
                                </template>
                                <v-list-item-title class="text-body-2">
                                    {{ activity.message || (activity.type === 'checkin' ? 'Checked In' : 'Checked Out') }}
                                </v-list-item-title>
                                <v-list-item-subtitle class="text-caption">
                                    {{ activity.time }}
                                </v-list-item-subtitle>
                            </v-list-item>
                        </v-list>
                        <div v-else class="text-center py-8">
                            <v-icon size="48" color="grey-lighten-1">mdi-calendar-blank</v-icon>
                            <p class="text-body-2 text-medium-emphasis mt-2">No activity today</p>
                        </div>
                    </v-card-text>
                </v-card>

                <!-- Quick Actions -->
                <v-card class="mt-4" elevation="2">
                    <v-card-title class="text-h6 font-weight-medium">
                        <v-icon start color="primary">mdi-lightning-bolt</v-icon>
                        Quick Actions
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                        <v-btn
                            block
                            variant="tonal"
                            color="primary"
                            class="mb-2 text-none"
                            prepend-icon="mdi-calendar-month"
                        >
                            View Monthly Report
                        </v-btn>
                        <v-btn
                            block
                            variant="tonal"
                            color="primary"
                            class="mb-2 text-none"
                            prepend-icon="mdi-file-document"
                        >
                            Apply Leave
                        </v-btn>
                        <v-btn
                            block
                            variant="tonal"
                            color="primary"
                            class="text-none"
                            prepend-icon="mdi-clock-edit"
                        >
                            Regularization Request
                        </v-btn>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Check In/Out Note Dialog -->
        <v-dialog v-model="showNoteDialog" max-width="500">
            <v-card>
                <v-card-title class="text-h6">
                    {{ noteType === 'checkin' ? 'Check In' : 'Check Out' }} Confirmation
                </v-card-title>
                <v-card-text>
                    <v-textarea
                        v-model="checkNote"
                        :label="`${noteType === 'checkin' ? 'Check In' : 'Check Out'} Note (Optional)`"
                        rows="3"
                        variant="outlined"
                        density="compact"
                        :placeholder="`Add any notes for your ${noteType === 'checkin' ? 'check in' : 'check out'}...`"
                    ></v-textarea>
                    <v-alert type="info" variant="tonal" density="compact" class="mb-0">
                        <strong>Current Time:</strong> {{ currentTime }}<br>
                        <strong>Location:</strong> {{ locationStatus }}
                    </v-alert>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="showNoteDialog = false" variant="text">Cancel</v-btn>
                    <v-btn
                        @click="performCheckAction"
                        :color="noteType === 'checkin' ? 'success' : 'error'"
                        variant="flat"
                        :loading="isLoading"
                    >
                        Confirm {{ noteType === 'checkin' ? 'Check In' : 'Check Out' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Break Dialog -->
        <v-dialog v-model="showBreakDialog" max-width="500">
            <v-card>
                <v-card-title class="text-h6">Start Break</v-card-title>
                <v-card-text>
                    <v-select
                        v-model="breakType"
                        label="Break Type"
                        :items="[
                            { title: 'Lunch Break', value: 'lunch' },
                            { title: 'Tea Break', value: 'tea' },
                            { title: 'Personal', value: 'personal' },
                            { title: 'Prayer', value: 'prayer' },
                            { title: 'Other', value: 'other' }
                        ]"
                        variant="outlined"
                        density="compact"
                    ></v-select>
                    <v-textarea
                        v-model="breakReason"
                        label="Reason (Optional)"
                        rows="2"
                        variant="outlined"
                        density="compact"
                        placeholder="Add reason for break..."
                    ></v-textarea>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="showBreakDialog = false" variant="text">Cancel</v-btn>
                    <v-btn @click="startBreak" color="warning" variant="flat" :loading="isLoading">
                        Start Break
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style scoped>
.greeting-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px !important;
}

.digital-time {
    font-size: 2.5rem;
    font-weight: 300;
    font-family: 'Roboto Mono', monospace;
    letter-spacing: 0.02em;
}

.action-card {
    border-radius: 16px !important;
    transition: all 0.3s ease;
}

.action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
}

.stat-box {
    background: rgba(0,0,0,0.02);
    border-radius: 12px;
    transition: all 0.2s ease;
}

.stat-box:hover {
    background: rgba(0,0,0,0.04);
}

.stat-card {
    border-radius: 12px !important;
    border: 1px solid rgba(0,0,0,0.08);
    transition: all 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
}

.activity-card {
    border-radius: 16px !important;
    max-height: 500px;
    overflow-y: auto;
}

.activity-card::-webkit-scrollbar {
    width: 6px;
}

.activity-card::-webkit-scrollbar-track {
    background: #f5f5f5;
}

.activity-card::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 3px;
}

.activity-card::-webkit-scrollbar-thumb:hover {
    background: #ccc;
}

/* Smooth animations */
.v-btn {
    transition: all 0.2s ease;
}

.v-btn:hover {
    transform: translateY(-1px);
}

/* Responsive adjustments */
@media (max-width: 960px) {
    .digital-time {
        font-size: 2rem;
    }

    .greeting-card {
        text-align: center;
    }
}
</style>