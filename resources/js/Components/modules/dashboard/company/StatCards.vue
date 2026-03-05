<script setup>
defineProps({
    stats: Object,
});

const cards = [
    {key: 'total_employees', label: 'Total Employees', icon: 'mdi-account-group', iconColor: 'blue', bgColor: 'blue-lighten-5'},
    {key: 'present', label: 'Present Today', icon: 'mdi-fingerprint', iconColor: 'green', bgColor: 'green-lighten-5'},
    {key: 'absent', label: 'Absent Today', icon: 'mdi-account-multiple-minus', iconColor: 'orange', bgColor: 'orange-lighten-5'},
    {key: 'on_leave', label: 'On Leave', icon: 'mdi-account-clock', iconColor: 'purple', bgColor: 'purple-lighten-5'},
    {key: 'pending_leaves', label: 'Pending Leaves', icon: 'mdi-clock-alert', iconColor: 'red', bgColor: 'red-lighten-5'},
    {key: 'avg_hours', label: 'Avg Hours', icon: 'mdi-timer', iconColor: 'teal', bgColor: 'teal-lighten-5', suffix: 'h'},
];
</script>

<template>
    <v-row dense>
        <v-col v-for="card in cards" :key="card.key" cols="6" md="2">
            <v-card class="stat-card" elevation="4">
                <v-card-text class="pa-4">
                    <v-row align="center" no-gutters>
                        <v-col>
                            <div class="text-overline text-grey-darken-1 mb-1 font-weight-medium">
                                {{ card.label.toUpperCase() }}
                            </div>
                            <div class="text-h4 font-weight-bold text-grey-darken-3">
                                {{ stats[card.key] }}{{ card.suffix || '' }}
                            </div>
                        </v-col>
                        <v-col cols="auto">
                            <v-sheet
                                :color="card.bgColor"
                                class="icon-container d-flex align-center justify-center"
                                height="50"
                                rounded="lg"
                                width="50"
                            >
                                <v-icon
                                    :color="card.iconColor"
                                    :icon="card.icon"
                                    size="26"
                                />
                            </v-sheet>
                        </v-col>
                    </v-row>
                </v-card-text>
            </v-card>
        </v-col>
    </v-row>
</template>

<style scoped>
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
</style>
