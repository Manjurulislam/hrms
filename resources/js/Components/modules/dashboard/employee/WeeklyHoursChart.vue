<script setup>
import {computed} from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    data: Array,
});

const chartOptions = computed(() => ({
    chart: {type: 'bar', height: 300, toolbar: {show: false}},
    plotOptions: {bar: {borderRadius: 6, columnWidth: '50%'}},
    colors: ['#2196F3'],
    xaxis: {
        categories: props.data.map(d => d.day),
    },
    yaxis: {
        title: {text: 'Hours'},
        min: 0,
    },
    dataLabels: {enabled: true, formatter: (val) => val + 'h'},
    tooltip: {y: {formatter: (val) => val + ' hours'}},
}));

const chartSeries = computed(() => [
    {name: 'Hours', data: props.data.map(d => d.hours)},
]);
</script>

<template>
    <v-card class="dashboard-card" elevation="4">
        <v-card-title class="pa-4 pb-2">
            <div class="d-flex align-center">
                <v-icon class="me-2" color="grey-darken-1">mdi-chart-bar</v-icon>
                <span class="text-h6 font-weight-medium">This Week's Working Hours</span>
            </div>
        </v-card-title>
        <v-card-text class="pa-4">
            <VueApexCharts
                v-if="data.length"
                :options="chartOptions"
                :series="chartSeries"
                height="300"
                type="bar"
            />
            <div v-else class="text-center text-medium-emphasis py-10">
                No data for this week
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
