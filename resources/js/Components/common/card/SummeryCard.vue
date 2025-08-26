<template>


    <v-container class="pa-3" fluid style="background-color: #f8f9fa;">
        <v-row align="stretch" class="fill-height">
            <!-- Welcome Section with Illustration -->
            <v-col cols="12" lg="6" xl="5">
                <v-card
                    class="pa-4 h-100 d-flex align-center"
                    color="white"
                    elevation="2"
                    rounded="lg"
                >
                    <v-row align="center" no-gutters>
                        <!-- Welcome Text -->
                        <v-col cols="12" md="6">
                            <div class="pr-3">
                                <h1 class="text-h5 font-weight-bold text-grey-darken-3 mb-1">
                                    Welcome Jonathan Deo
                                </h1>
                                <p class="text-body-2 text-grey mb-4">
                                    Check all the statistics
                                </p>
                                <v-btn
                                    class="px-6"
                                    color="blue"
                                    elevation="0"
                                    rounded="lg"
                                    size="default"
                                >
                                    Visit Now
                                </v-btn>
                            </div>
                        </v-col>

                        <!-- Business Person Illustration -->
                        <v-col class="text-center" cols="12" md="6">
                            <div class="position-relative d-inline-block">
                                <!-- Person with beard and glasses -->
                                <div class="person-container">
                                    <div class="person-body">
                                        <!-- Head with beard and glasses -->
                                        <div class="person-head">
                                            <div class="glasses"></div>
                                            <div class="beard"></div>
                                        </div>

                                        <!-- Suit/shirt -->
                                        <div class="person-shirt"></div>
                                        <div class="person-tie"></div>

                                        <!-- Arms -->
                                        <div class="person-arm-left"></div>
                                        <div class="person-arm-right"></div>

                                        <!-- Coffee cup -->
                                        <div class="coffee-cup"></div>
                                    </div>

                                    <!-- Plant decoration -->
                                    <div class="plant-left"></div>
                                    <div class="plant-right"></div>
                                </div>
                            </div>
                        </v-col>
                    </v-row>
                </v-card>
            </v-col>

            <!-- Statistics Cards -->
            <v-col cols="12" lg="6" xl="7">
                <v-row class="h-100">
                    <!-- Sales Card -->
                    <v-col class="d-flex" cols="12" sm="4">
                        <v-card
                            class="pa-4 text-white flex-grow-1 position-relative overflow-hidden"
                            elevation="3"
                            rounded="lg"
                            style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);"
                        >
                            <!-- Orange accent -->
                            <div
                                class="position-absolute"
                                style="top: 0; right: 0; width: 40px; height: 40px; background: #FF9800; border-radius: 0 12px 0 30px;"
                            ></div>

                            <!-- Icon -->
                            <v-avatar
                                class="mb-3"
                                size="32"
                                style="background: rgba(255,255,255,0.2);"
                            >
                                <v-icon color="white" size="16">mdi-chart-pie</v-icon>
                            </v-avatar>

                            <!-- Value -->
                            <div class="mb-1">
                                <h2 class="text-h5 font-weight-bold">2358</h2>
                                <div class="d-flex align-center mt-1">
                                    <v-icon class="mr-1" size="12">mdi-trending-up</v-icon>
                                    <span class="text-caption">+23%</span>
                                </div>
                            </div>

                            <!-- Label -->
                            <p class="text-caption mb-0 opacity-90">Sales</p>
                        </v-card>
                    </v-col>

                    <!-- Refunds Card -->
                    <v-col class="d-flex" cols="12" sm="4">
                        <v-card
                            class="pa-4 text-white flex-grow-1 position-relative overflow-hidden"
                            elevation="3"
                            rounded="lg"
                            style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);"
                        >
                            <!-- Orange-red accent -->
                            <div
                                class="position-absolute"
                                style="top: 0; right: 0; width: 40px; height: 40px; background: #FF5722; border-radius: 0 12px 0 30px;"
                            ></div>

                            <!-- Icon -->
                            <v-avatar
                                class="mb-3"
                                size="32"
                                style="background: rgba(255,255,255,0.2);"
                            >
                                <v-icon color="white" size="16">mdi-download</v-icon>
                            </v-avatar>

                            <!-- Value -->
                            <div class="mb-1">
                                <h2 class="text-h5 font-weight-bold">356</h2>
                                <div class="d-flex align-center mt-1">
                                    <v-icon class="mr-1" size="12">mdi-trending-up</v-icon>
                                    <span class="text-caption">+8%</span>
                                </div>
                            </div>

                            <!-- Label -->
                            <p class="text-caption mb-0 opacity-90">Refunds</p>
                        </v-card>
                    </v-col>

                    <!-- Earnings Card -->
                    <v-col class="d-flex" cols="12" sm="4">
                        <v-card
                            class="pa-4 text-white flex-grow-1 position-relative overflow-hidden"
                            elevation="3"
                            rounded="lg"
                            style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);"
                        >
                            <!-- Teal accent -->
                            <div
                                class="position-absolute"
                                style="top: 0; right: 0; width: 40px; height: 40px; background: #00BCD4; border-radius: 0 12px 0 30px;"
                            ></div>

                            <!-- Icon -->
                            <v-avatar
                                class="mb-3"
                                size="32"
                                style="background: rgba(255,255,255,0.2);"
                            >
                                <v-icon color="white" size="16">mdi-currency-usd</v-icon>
                            </v-avatar>

                            <!-- Value -->
                            <div class="mb-1">
                                <h2 class="text-h5 font-weight-bold">$23.8K</h2>
                                <div class="d-flex align-center mt-1">
                                    <v-icon class="mr-1" size="12">mdi-trending-down</v-icon>
                                    <span class="text-caption">-3%</span>
                                </div>
                            </div>

                            <!-- Label -->
                            <p class="text-caption mb-0 opacity-90">Earnings</p>
                        </v-card>
                    </v-col>
                </v-row>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
// Vue 3 Composition API setup
</script>

<style scoped>
/* Business Person Illustration Styles */
.person-container {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto;
}

.person-head {
    position: absolute;
    top: 15px;
    left: 50%;
    transform: translateX(-50%);
    width: 35px;
    height: 35px;
    background: #F4C2A1;
    border-radius: 50%;
}

.glasses {
    position: absolute;
    top: 10px;
    left: 6px;
    width: 24px;
    height: 8px;
    border: 1.5px solid #333;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
}

.glasses::before {
    content: '';
    position: absolute;
    top: -1.5px;
    left: 10px;
    width: 4px;
    height: 1.5px;
    background: #333;
}

.beard {
    position: absolute;
    bottom: -3px;
    left: 8px;
    width: 18px;
    height: 14px;
    background: #8B4513;
    border-radius: 0 0 50% 50%;
}

.person-body {
    position: absolute;
    top: 42px;
    left: 50%;
    transform: translateX(-50%);
    width: 55px;
    height: 70px;
}

.person-shirt {
    position: absolute;
    top: 8px;
    left: 7px;
    width: 42px;
    height: 50px;
    background: #1976D2;
    border-radius: 8px 8px 0 0;
}

.person-tie {
    position: absolute;
    top: 12px;
    left: 24px;
    width: 7px;
    height: 28px;
    background: #333;
    clip-path: polygon(0 0, 100% 0, 80% 100%, 20% 100%);
}

.person-arm-left {
    position: absolute;
    top: 15px;
    left: -7px;
    width: 18px;
    height: 35px;
    background: #1976D2;
    border-radius: 10px;
    transform: rotate(-20deg);
}

.person-arm-right {
    position: absolute;
    top: 15px;
    right: -7px;
    width: 18px;
    height: 35px;
    background: #1976D2;
    border-radius: 10px;
    transform: rotate(30deg);
}

.coffee-cup {
    position: absolute;
    top: 32px;
    right: -18px;
    width: 14px;
    height: 18px;
    background: white;
    border-radius: 0 0 7px 7px;
    border: 1.5px solid #ddd;
}

.coffee-cup::before {
    content: '';
    position: absolute;
    top: 3px;
    right: -6px;
    width: 6px;
    height: 8px;
    border: 1.5px solid #ddd;
    border-left: none;
    border-radius: 0 50% 50% 0;
}

.plant-left {
    position: absolute;
    bottom: 15px;
    left: 15px;
    width: 20px;
    height: 28px;
    background: #4CAF50;
    border-radius: 50% 50% 0 0;
    transform: rotate(-15deg);
}

.plant-right {
    position: absolute;
    bottom: 18px;
    right: 20px;
    width: 18px;
    height: 25px;
    background: #66BB6A;
    border-radius: 50% 50% 0 0;
    transform: rotate(20deg);
}

/* Card hover effects */
.v-card {
    transition: all 0.3s ease;
}

.v-card:hover {
    transform: translateY(-2px);
}

/* Responsive adjustments */
@media (max-width: 960px) {
    .person-container {
        width: 100px;
        height: 120px;
    }

    .person-head {
        width: 30px;
        height: 30px;
    }

    .person-body {
        width: 45px;
        height: 60px;
    }
}

@media (max-width: 600px) {
    .text-h5 {
        font-size: 1.25rem !important;
    }
}
</style>
