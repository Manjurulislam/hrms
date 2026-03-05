<script setup>
import {computed} from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    data: Array,
});

const chartOptions = computed(() => ({
    chart: {
        type: 'area',
        height: 350,
        toolbar: {show: false},
        stacked: true,
    },
    colors: ['#4CAF50', '#FF5252', '#FFC107', '#2196F3'],
    xaxis: {
        categories: props.data.map(d => d.date),
        labels: {
            rotate: -45,
            style: {fontSize: '11px'},
        },
    },
    yaxis: {
        title: {text: 'Employees'},
    },
    stroke: {curve: 'smooth', width: 2},
    fill: {type: 'gradient', gradient: {opacityFrom: 0.5, opacityTo: 0.1}},
    legend: {position: 'top'},
    dataLabels: {enabled: false},
    tooltip: {shared: true, intersect: false},
}));

const chartSeries = computed(() => [
    {name: 'Present', data: props.data.map(d => d.present)},
    {name: 'Absent', data: props.data.map(d => d.absent)},
    {name: 'Late', data: props.data.map(d => d.late)},
    {name: 'Leave', data: props.data.map(d => d.leave)},
]);
</script>

<template>
    <v-card class="dashboard-card" elevation="4">
        <v-card-title class="pa-4 pb-2">
            <div class="d-flex align-center">
                <v-icon class="me-2" color="grey-darken-1">mdi-chart-areaspline</v-icon>
                <span class="text-subtitle-1 font-weight-light">Attendance States</span>
            </div>
        </v-card-title>
        <v-card-text class="pa-4">
            <VueApexCharts
                v-if="data.length"
                :options="chartOptions"
                :series="chartSeries"
                height="350"
                type="area"
            />
            <div v-else class="text-center text-medium-emphasis py-10">
                No attendance data for this month
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
