<script setup>
import {Head} from '@inertiajs/vue3';
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import {onMounted, ref} from "vue";

const stats = ref([
    {
        id: 1,
        label: 'Total employee',
        value: 30,
        icon: 'mdi-account-group',
        iconColor: 'blue',
        bgColor: 'blue-lighten-5'
    },
    {
        id: 2,
        label: 'Today presents',
        value: 4,
        icon: 'mdi-fingerprint',
        iconColor: 'green',
        bgColor: 'green-lighten-5'
    },
    {
        id: 3,
        label: 'Today absents',
        value: 20,
        icon: 'mdi-account-multiple-minus',
        iconColor: 'orange',
        bgColor: 'orange-lighten-5'
    },
    {
        id: 4,
        label: 'Today leave',
        value: 6,
        icon: 'mdi-account-clock',
        iconColor: 'purple',
        bgColor: 'purple-lighten-5'
    }
])

const leaveRequests = ref([
    {
        id: 1,
        name: 'Maisha Lucy Zamora Gonzales',
        avatar: 'https://randomuser.me/api/portraits/men/1.jpg',
        status: 'Approved'
    },
    {
        id: 2,
        name: 'Maisha Lucy Zamora Gonzales',
        avatar: 'https://randomuser.me/api/portraits/men/2.jpg',
        status: 'Approved'
    },
    {
        id: 3,
        name: 'Maisha Lucy Zamora Gonzales',
        avatar: 'https://randomuser.me/api/portraits/men/3.jpg',
        status: 'Approved'
    },
    {
        id: 4,
        name: 'Amy Aphrodite Zamora Peck',
        avatar: 'https://randomuser.me/api/portraits/men/4.jpg',
        status: 'Approved'
    }
])

// Chart data
const chartData = ref({
    labels: ['Sales', 'Accounts\nand Finance', 'Supply\nChain', 'Information\nTechnology', 'HR', 'Production', 'Electrical'],
    datasets: [
        {
            label: 'Leave %',
            data: [0, 0, 0, 0, 0, 10, 14],
            backgroundColor: '#EF4444',
            barThickness: 30,
        },
        {
            label: 'Present %',
            data: [0, 0, 0, 0, 0, 10, 14],
            backgroundColor: '#10B981',
            barThickness: 30,
        },
        {
            label: 'Absent %',
            data: [0, 0, 0, 0, 0, 80, 72],
            backgroundColor: '#F59E0B',
            barThickness: 30,
        }
    ]
})

const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        title: {
            display: false
        },
        legend: {
            display: true,
            position: 'top',
            align: 'end',
            labels: {
                usePointStyle: true,
                pointStyle: 'rect',
                font: {
                    size: 12
                }
            }
        }
    },
    scales: {
        x: {
            stacked: true,
            grid: {
                display: false
            },
            ticks: {
                font: {
                    size: 11
                }
            }
        },
        y: {
            stacked: true,
            beginAtZero: true,
            max: 120,
            ticks: {
                stepSize: 30,
                callback: function (value) {
                    return value + '%'
                }
            },
            grid: {
                color: '#e5e7eb'
            }
        }
    }
})

let chartInstance = null

onMounted(() => {
    // Initialize Chart.js
    import('https://cdn.jsdelivr.net/npm/chart.js').then((Chart) => {
        const ctx = document.getElementById('attendanceChart')
        chartInstance = new Chart.default(ctx, {
            type: 'bar',
            data: chartData.value,
            options: chartOptions.value
        })
    })
})
</script>

<template>
    <Head title="Dashboard"/>
    <DefaultLayout>
        <v-row>
            <!-- Left Column: Employee Stats (Material Dashboard Style) -->
            <v-col cols="12" lg="3" md="4">
                <v-row>
                    <v-col v-for="stat in stats" :key="stat.id" cols="12">
                        <v-card class="stat-card" elevation="4">
                            <v-card-text class="pa-4">
                                <v-row align="center" no-gutters>
                                    <v-col>
                                        <div class="text-overline text-grey-darken-1 mb-1 font-weight-medium">
                                            {{ stat.label.toUpperCase() }}
                                        </div>
                                        <div class="text-h3 font-weight-bold text-grey-darken-3">
                                            {{ stat.value }}
                                        </div>
                                    </v-col>
                                    <v-col cols="auto">
                                        <v-sheet
                                            :color="stat.bgColor"
                                            class="icon-container d-flex align-center justify-center"
                                            height="60"
                                            rounded="lg"
                                            width="60"
                                        >
                                            <v-icon
                                                :color="stat.iconColor"
                                                :icon="stat.icon"
                                                size="30"
                                            ></v-icon>
                                        </v-sheet>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-col>

            <!-- Middle Column: Bar Chart -->
            <v-col cols="12" lg="6" md="4">
                <v-card class="chart-card" elevation="4">
                    <v-card-title class="pa-4 pb-2">
                        <div class="d-flex align-center">
                            <v-icon class="me-2" color="grey-darken-1">mdi-chart-bar</v-icon>
                            <span
                                class="text-h6 font-weight-medium">Daily attendance statistic (department wise)</span>
                        </div>
                    </v-card-title>
                    <v-card-text class="pa-4">
                        <div class="chart-container">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Right Column: Leave Application -->
            <v-col cols="12" lg="3" md="4">
                <v-card class="leave-card" elevation="4">
                    <v-card-title class="pa-4 pb-2">
                        <div class="d-flex align-center">
                            <v-icon class="me-2" color="teal">mdi-file-document-outline</v-icon>
                            <span class="text-h6 font-weight-medium">Leave Application</span>
                        </div>
                    </v-card-title>

                    <v-card-text class="pa-4 pt-2">
                        <v-list class="pa-0">
                            <v-list-item
                                v-for="(request, index) in leaveRequests"
                                :key="request.id"
                                class="pa-2 mb-2 leave-item"
                                rounded="lg"
                            >
                                <template v-slot:prepend>
                                    <v-avatar class="me-3" size="40">
                                        <v-img :alt="request.name" :src="request.avatar"></v-img>
                                    </v-avatar>
                                </template>

                                <v-list-item-title class="text-body-2 font-weight-medium">
                                    {{ request.name }}
                                </v-list-item-title>
                                <v-list-item-subtitle class="text-caption text-grey-darken-1">
                                    Reason :
                                </v-list-item-subtitle>

                                <template v-slot:append>
                                    <v-chip
                                        class="text-caption"
                                        color="success"
                                        size="small"
                                        variant="flat"
                                    >
                                        Approved
                                    </v-chip>
                                </template>
                            </v-list-item>
                        </v-list>
                    </v-card-text>

                    <v-divider></v-divider>

                    <v-card-actions class="pa-4 justify-center">
                        <v-btn
                            append-icon="mdi-arrow-right"
                            color="teal"
                            size="small"
                            variant="text"
                        >
                            See All Request
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>

<style scoped>
/* Material Dashboard Style Cards */
.stat-card {
    border-radius: 12px !important;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

.icon-container {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

/* Chart and Leave Application Cards */
.chart-card,
.leave-card {
    border-radius: 12px !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.chart-card:hover,
.leave-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1) !important;
}

.chart-container {
    height: 400px;
    position: relative;
}

.leave-item {
    background-color: rgba(0, 0, 0, 0.02);
    transition: background-color 0.2s ease;
}

.leave-item:hover {
    background-color: rgba(0, 0, 0, 0.06);
}
</style>
