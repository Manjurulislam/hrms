<template>
    <v-container class="pa-4" fluid style="background-color: #f8fafc;">
        <v-row>
            <!-- On Route Vehicles -->
            <v-col cols="12" lg="3" sm="6">
                <v-card
                    class="pa-6"
                    color="white"
                    elevation="3"
                    rounded="lg"
                >
                    <!-- Icon -->
                    <div
                        class="d-inline-flex align-center justify-center mb-4"
                        style="width: 48px; height: 48px; background-color: #e0e7ff; border-radius: 12px;"
                    >
                        <v-icon size="24" style="color: #6366f1;">mdi-truck</v-icon>
                    </div>

                    <!-- Number -->
                    <div class="mb-2">
                        <h2 class="text-h3 font-weight-bold text-grey-darken-4">42</h2>
                    </div>

                    <!-- Label -->
                    <p class="text-body-1 text-grey-darken-1 mb-3">On route vehicles</p>

                    <!-- Change Percentage -->
                    <div class="d-flex align-center">
                        <span class="text-body-2 font-weight-medium" style="color: #22c55e;">+18.2%</span>
                        <span class="text-body-2 text-grey ml-2">than last week</span>
                    </div>
                </v-card>
            </v-col>

            <!-- Vehicles with Errors -->
            <v-col cols="12" lg="3" sm="6">
                <v-card
                    class="pa-6"
                    color="white"
                    elevation="3"
                    rounded="lg"
                >
                    <!-- Icon -->
                    <div
                        class="d-inline-flex align-center justify-center mb-4"
                        style="width: 48px; height: 48px; background-color: #fed7aa; border-radius: 12px;"
                    >
                        <v-icon size="24" style="color: #ea580c;">mdi-alert-triangle</v-icon>
                    </div>

                    <!-- Number -->
                    <div class="mb-2">
                        <h2 class="text-h3 font-weight-bold text-grey-darken-4">8</h2>
                    </div>

                    <!-- Label -->
                    <p class="text-body-1 text-grey-darken-1 mb-3">Vehicles with errors</p>

                    <!-- Change Percentage -->
                    <div class="d-flex align-center">
                        <span class="text-body-2 font-weight-medium" style="color: #22c55e;">-8.7%</span>
                        <span class="text-body-2 text-grey ml-2">than last week</span>
                    </div>
                </v-card>
            </v-col>

            <!-- Deviated from Route -->
            <v-col cols="12" lg="3" sm="6">
                <v-card
                    class="pa-6"
                    color="white"
                    elevation="3"
                    rounded="lg"
                >
                    <!-- Icon -->
                    <div
                        class="d-inline-flex align-center justify-center mb-4"
                        style="width: 48px; height: 48px; background-color: #fce7f3; border-radius: 12px;"
                    >
                        <v-icon size="24" style="color: #ec4899;">mdi-source-branch</v-icon>
                    </div>

                    <!-- Number -->
                    <div class="mb-2">
                        <h2 class="text-h3 font-weight-bold text-grey-darken-4">27</h2>
                    </div>

                    <!-- Label -->
                    <p class="text-body-1 text-grey-darken-1 mb-3">Deviated from route</p>

                    <!-- Change Percentage -->
                    <div class="d-flex align-center">
                        <span class="text-body-2 font-weight-medium" style="color: #22c55e;">+4.3%</span>
                        <span class="text-body-2 text-grey ml-2">than last week</span>
                    </div>
                </v-card>
            </v-col>

            <!-- Late Vehicles -->
            <v-col cols="12" lg="3" sm="6">
                <v-card
                    class="pa-6"
                    color="white"
                    elevation="3"
                    rounded="lg"
                >
                    <!-- Icon -->
                    <div
                        class="d-inline-flex align-center justify-center mb-4"
                        style="width: 48px; height: 48px; background-color: #cffafe; border-radius: 12px;"
                    >
                        <v-icon size="24" style="color: #0891b2;">mdi-clock-outline</v-icon>
                    </div>

                    <!-- Number -->
                    <div class="mb-2">
                        <h2 class="text-h3 font-weight-bold text-grey-darken-4">13</h2>
                    </div>

                    <!-- Label -->
                    <p class="text-body-1 text-grey-darken-1 mb-3">Late vehicles</p>

                    <!-- Change Percentage -->
                    <div class="d-flex align-center">
                        <span class="text-body-2 font-weight-medium" style="color: #22c55e;">-2.5%</span>
                        <span class="text-body-2 text-grey ml-2">than last week</span>
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
// Vue 3 Composition API
</script>

<style scoped>
/* Card hover effects */
.v-card {
    transition: all 0.2s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.v-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
}

/* Responsive adjustments */
@media (max-width: 600px) {
    .text-h3 {
        font-size: 1.75rem !important;
    }
}
</style>
