<script setup>
defineProps({
    balances: Array,
});

const colors = ['primary', 'success', 'warning', 'info', 'teal', 'purple'];
</script>

<template>
    <v-card class="dashboard-card" elevation="4">
        <v-card-title class="pa-4 pb-2">
            <div class="d-flex align-center">
                <v-icon class="me-2" color="grey-darken-1">mdi-calendar-check</v-icon>
                <span class="text-h6 font-weight-medium">Leave Balances</span>
            </div>
        </v-card-title>
        <v-card-text class="pa-4 pt-2">
            <v-row dense>
                <v-col v-for="(balance, index) in balances" :key="balance.name" cols="6">
                    <v-card
                        :color="colors[index % colors.length]"
                        class="balance-card pa-3 text-center"
                        variant="tonal"
                    >
                        <div class="text-h5 font-weight-bold">{{ balance.remaining }}</div>
                        <div class="text-caption font-weight-medium">{{ balance.name }}</div>
                        <div class="text-caption text-medium-emphasis">{{ balance.used }}/{{ balance.total }} used</div>
                    </v-card>
                </v-col>
            </v-row>
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

.balance-card {
    border-radius: 8px !important;
    transition: transform 0.2s ease;
}

.balance-card:hover {
    transform: translateY(-2px);
}
</style>
