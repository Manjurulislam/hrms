<script setup>
import {computed, ref, watch} from 'vue'
import {useForm} from '@inertiajs/vue3'
import {useToast} from "vue-toastification";
import axios from 'axios';

const toast = useToast();
const props = defineProps({
    modelValue: {type: Boolean, default: false},
    department: {type: Object, required: true},
})

const emit = defineEmits(['update:modelValue', 'saved'])

const localDialog = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

// Internal state
const isEditMode = ref(false);
const isLoading = ref(false);
const existingScheduleId = ref(null);

// Days of the week options
const daysOfWeek = [
    {value: 'saturday', label: 'Saturday'},
    {value: 'sunday', label: 'Sunday'},
    {value: 'monday', label: 'Monday'},
    {value: 'tuesday', label: 'Tuesday'},
    {value: 'wednesday', label: 'Wednesday'},
    {value: 'thursday', label: 'Thursday'},
    {value: 'friday', label: 'Friday'}
]

const form = useForm({
    work_start_time: '',
    work_end_time: '',
    working_days: []
})

// Watch for dialog opening to check if schedule exists
watch(() => props.modelValue, async (isOpen) => {
    if (isOpen) {
        await checkAndLoadSchedule();
    } else {
        resetComponent();
    }
});

// Check if department has existing schedule and load it
const checkAndLoadSchedule = async () => {
    isLoading.value = true;

    try {
        // First check if department has a schedule
        const checkResponse = await axios.get(route('department-schedules.has-schedule', props.department.id));

        if (checkResponse.data.has_schedule) {
            // Schedule exists - switch to edit mode and load data
            isEditMode.value = true;
            existingScheduleId.value = checkResponse.data.schedule_id;
            await loadExistingSchedule();
        } else {
            // No schedule exists - create mode
            isEditMode.value = false;
            existingScheduleId.value = null;
            form.reset();
            form.working_days = [];
        }
    } catch (error) {
        console.error('Error checking schedule:', error);
        toast.error('Failed to load schedule information');
        isEditMode.value = false;
        form.reset();
        form.working_days = [];
    } finally {
        isLoading.value = false;
    }
};

// Load existing schedule data
const loadExistingSchedule = async () => {
    try {
        const response = await axios.get(route('department-schedules.edit', props.department.id));

        if (response.data.success) {
            const data = response.data.data;
            form.work_start_time = data.work_start_time;
            form.work_end_time = data.work_end_time;
            form.working_days = data.working_days || [];
        } else {
            toast.error('Failed to load schedule data');
        }
    } catch (error) {
        console.error('Error loading schedule:', error);
        toast.error('Failed to load schedule data');
    }
};

// Reset component state
const resetComponent = () => {
    isEditMode.value = false;
    existingScheduleId.value = null;
    form.clearErrors();
    form.reset();
    form.working_days = [];
};

const closeDialog = () => {
    emit('update:modelValue', false);
}

const submitForm = () => {
    if (isEditMode.value) {
        // Update existing schedule
        form.put(route('department-schedules.update', props.department.id), {
            onSuccess: () => {
                closeDialog();
                toast.success('Department schedule has been updated successfully.');
                emit('saved');
            },
            onError: (error) => {
                console.log(error);
                toast.error('Something went wrong. Please try again.');
            }
        });
    } else {
        // Create new schedule
        form.post(route('department-schedules.store', props.department.id), {
            onSuccess: () => {
                closeDialog();
                toast.success('Department schedule has been added successfully.');
                emit('saved');
            },
            onError: (error) => {
                console.log(error);
                toast.error('Something went wrong. Please try again.');
            }
        });
    }
}

// Helper function to toggle day selection
const toggleDay = (dayValue) => {
    const index = form.working_days.indexOf(dayValue)
    if (index > -1) {
        form.working_days.splice(index, 1)
    } else {
        form.working_days.push(dayValue)
    }
}

// Check if a day is selected
const isDaySelected = (dayValue) => {
    return form.working_days.includes(dayValue)
}
</script>

<template>
    <v-dialog
        v-model="localDialog"
        max-width="900px"
    >
        <v-card>
            <v-toolbar color="lvDark" dark height="45">
                <v-toolbar-title class="text-uppercase text-subtitle-1">
                    <template v-if="isLoading">
                        Loading...
                    </template>
                    <template v-else>
                        {{ isEditMode ? 'Edit' : 'Create' }} Schedule - {{ department.title }}
                    </template>
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn dark icon size="small" @click="closeDialog">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>

            <!-- Loading state -->
            <div v-if="isLoading" class="text-center pa-8">
                <v-progress-circular color="primary" indeterminate></v-progress-circular>
                <p class="mt-4">Loading schedule information...</p>
            </div>

            <!-- Form content -->
            <template v-else>
                <v-card-text class="pa-6">
                    <form @submit.prevent="submitForm">
                        <!-- Working Days Selection -->
                        <v-row class="mb-4">
                            <v-col cols="12">
                                <v-label class="mb-3 font-weight-medium">Working Days</v-label>
                                <div class="d-flex flex-wrap gap-2">
                                    <v-chip
                                        v-for="day in daysOfWeek"
                                        :key="day.value"
                                        :color="isDaySelected(day.value) ? 'primary' : 'default'"
                                        :variant="isDaySelected(day.value) ? 'flat' : 'outlined'"
                                        class="ma-1"
                                        clickable
                                        @click="toggleDay(day.value)"
                                    >
                                        {{ day.label }}
                                    </v-chip>
                                </div>

                                <span v-if="form.errors.working_days" class="text-error text-caption d-block mt-1">
                                    {{ form.errors.working_days }}
                                </span>
                            </v-col>
                        </v-row>

                        <!-- Time Selection -->
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-label class="mb-2 font-weight-medium">Work Start Time</v-label>
                                <el-time-select
                                    v-model="form.work_start_time"
                                    :append-to-body="false"
                                    :max-time="form.work_end_time"
                                    end="23:59"
                                    format="hh:mm A"
                                    placeholder="Start time"
                                    popper-class="time-select-dropdown"
                                    size="large"
                                    start="00:00"
                                />
                                <span v-if="form.errors.work_start_time" class="text-error text-caption d-block mt-1">
                                    {{ form.errors.work_start_time }}
                                </span>
                            </v-col>

                            <v-col cols="12" md="6">
                                <v-label class="mb-2 font-weight-medium">Work End Time</v-label>
                                <el-time-select
                                    v-model="form.work_end_time"
                                    :append-to-body="false"
                                    :min-time="form.work_start_time"
                                    end="23:59"
                                    format="hh:mm A"
                                    placeholder="End time"
                                    popper-class="time-select-dropdown"
                                    size="large"
                                    start="00:00"
                                    step="00:15"
                                />
                                <span v-if="form.errors.work_end_time" class="text-error text-caption d-block mt-1">
                                    {{ form.errors.work_end_time }}
                                </span>
                            </v-col>
                        </v-row>
                    </form>
                </v-card-text>

                <v-card-actions class="pa-4">
                    <v-btn
                        :disabled="form.processing"
                        variant="text"
                        @click="closeDialog"
                    >
                        Cancel
                    </v-btn>
                    <v-btn
                        :loading="form.processing"
                        color="primary"
                        variant="flat"
                        @click="submitForm"
                    >
                        {{ isEditMode ? 'Update' : 'Create' }}
                    </v-btn>
                </v-card-actions>
            </template>
        </v-card>
    </v-dialog>
</template>

<style scoped>
/* Ensure time select dropdowns stay within dialog */
:deep(.v-dialog) {
    position: relative;
    z-index: 2400;
}

:deep(.v-card) {
    position: relative;
    z-index: 2401;
}

/* Target the time select dropdown specifically */
:global(.time-select-dropdown) {
    background: #ffffff !important;
    z-index: 2402 !important;
    position: absolute !important;
}

/* Ensure el-select dropdown stays in place */
:deep(.el-select .el-input) {
    position: relative;
}

:deep(.el-select-dropdown) {
    z-index: 2402 !important;
    position: absolute !important;
}

/* Custom gap utility for older Vuetify versions */
.gap-2 > * {
    margin: 0.25rem;
}
</style>
