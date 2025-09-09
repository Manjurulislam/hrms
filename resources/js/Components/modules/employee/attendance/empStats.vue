<script setup>
// Props
const props = defineProps({
    userInfo: {
        type: Object,
        required: true,
        default: () => ({
            name: 'John Doe',
            position: 'Software Developer'
        })
    },
    monthlyStats: {
        type: Object,
        required: true,
        default: () => ({
            present: 18,
            absent: 2,
            late: 3,
            rate: 90
        })
    },
    userStatus: {
        type: String,
        required: true,
        default: 'Ready to Start'
    },
    statusColor: {
        type: String,
        required: true,
        default: 'primary'
    }
})
</script>

<template>
    <div>
        <!-- User Profile Card -->
        <v-card class="mb-3" elevation="3">
            <v-card-text class="text-center">
                <v-avatar class="mb-3" color="primary" size="70">
                    <v-icon color="white" size="40">mdi-account</v-icon>
                </v-avatar>
                <div class="text-h6 mb-1">{{ userInfo.name }}</div>
                <div class="text-body-2 text-medium-emphasis mb-2">{{ userInfo.position }}</div>
                <v-chip
                    :color="statusColor"
                    class="status-chip"
                >
                    {{ userStatus }}
                </v-chip>
            </v-card-text>
        </v-card>

        <!-- Monthly Statistics Card -->
        <v-card elevation="3">
            <v-card-text class="py-4">
                <v-card-title class="text-h6 pa-0 mb-2">This Month</v-card-title>
                <v-row>
                    <v-col class="text-center" cols="6">
                        <v-card
                            class="attendance-summary-card pa-2"
                            color="success"
                            variant="tonal"
                        >
                            <div class="text-h6 text-success">{{ monthlyStats.present }}</div>
                            <div class="text-caption">Present</div>
                        </v-card>
                    </v-col>
                    <v-col class="text-center" cols="6">
                        <v-card
                            class="attendance-summary-card pa-2"
                            color="error"
                            variant="tonal"
                        >
                            <div class="text-h6 text-error">{{ monthlyStats.absent }}</div>
                            <div class="text-caption">Absent</div>
                        </v-card>
                    </v-col>
                    <v-col class="text-center" cols="6">
                        <v-card
                            class="attendance-summary-card pa-2"
                            color="warning"
                            variant="tonal"
                        >
                            <div class="text-h6 text-warning">{{ monthlyStats.late }}</div>
                            <div class="text-caption">Late</div>
                        </v-card>
                    </v-col>
                    <v-col class="text-center" cols="6">
                        <v-card
                            class="attendance-summary-card pa-2"
                            color="info"
                            variant="tonal"
                        >
                            <div class="text-h6 text-info">{{ monthlyStats.rate }}%</div>
                            <div class="text-caption">Rate</div>
                        </v-card>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>
    </div>
</template>

<style scoped>
.attendance-summary-card {
    transition: transform 0.2s ease;
    cursor: pointer;
}

.attendance-summary-card:hover {
    transform: translateY(-2px);
}

.status-chip {
    font-weight: 500;
    text-transform: capitalize;
}

.work-status-active {
    animation: pulse 2s infinite;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .v-avatar {
        size: 60px !important;
    }

    .text-h6 {
        font-size: 1.1rem !important;
    }

    .attendance-summary-card {
        min-height: 70px;
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}
</style>
