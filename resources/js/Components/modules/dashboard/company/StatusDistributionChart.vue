<script setup>
import {computed} from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    data: Array,
});

const colorMap = {
    'Present': '#4CAF50',
    'Absent': '#FF5252',
    'Late': '#FFC107',
    'Half Day': '#FF9800',
    'Leave': '#2196F3',
    'Holiday': '#9C27B0',
    'Weekend': '#9E9E9E',
    'WFH': '#009688',
};

const chartOptions = computed(() => ({
    chart: {type: 'donut', height: 350},
    labels: props.data.map(s => s.status),
    colors: props.data.map(s => colorMap[s.status] || '#757575'),
    legend: {position: 'bottom'},
    dataLabels: {enabled: true, formatter: (val) => Math.round(val) + '%'},
    plotOptions: {
        pie: {
            donut: {
                size: '55%',
                labels: {
                    show: true,
                    total: {show: true, label: 'Total', fontSize: '14px'},
                },
            },
        },
    },
}));

const chartSeries = computed(() => props.data.map(s => s.count));
</script>

<template>
    <v-card class="dashboard-card" elevation="4">
        <v-card-title class="pa-4 pb-2">
            <div class="d-flex align-center">
                <v-icon class="me-2" color="grey-darken-1">mdi-chart-donut</v-icon>
                <span class="text-h6 font-weight-medium">Attendance Distribution (Today)</span>
            </div>
        </v-card-title>
        <v-card-text class="pa-4">
            <VueApexCharts
                v-if="data.length"
                :options="chartOptions"
                :series="chartSeries"
                height="350"
                type="donut"
            />
            <div v-else class="text-center text-medium-emphasis py-10">
                No attendance data for today
            </div>
        </v-card-text>
    </v-card>
</template>

<style scoped>
.dashboard-card {
    border-radius: 12px !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1) !important;
}
</style>
