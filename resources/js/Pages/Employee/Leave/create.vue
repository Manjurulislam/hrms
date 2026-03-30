<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head, useForm} from '@inertiajs/vue3';
import {computed} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {useToast} from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    leaveTypes: Array,
    balances: Array,
});

const form = useForm({
    leave_type_id: null,
    started_at: null,
    ended_at: null,
    title: '',
    notes: '',
});

const selectedBalance = computed(() => {
    if (!form.leave_type_id) return null;
    return props.balances.find(b => b.id === form.leave_type_id);
});

const totalDays = computed(() => {
    if (!form.started_at || !form.ended_at) return 0;
    const start = new Date(form.started_at);
    const end = new Date(form.ended_at);
    const diff = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
    return diff > 0 ? diff : 0;
});

const hasEnoughBalance = computed(() => {
    if (!selectedBalance.value) return true;
    return totalDays.value <= selectedBalance.value.remaining;
});

const submit = () => {
    form.post(route('emp-leave.store'), {
        onSuccess: () => toast.success('Leave request submitted successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.'),
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Apply Leave"/>
        <v-card>
            <CardTitle
                :extra-route="{title: 'Back', route: 'emp-leave.index', icon: 'mdi-arrow-left-bold'}"
                icon="mdi-calendar-plus"
                title="Apply Leave"
            />

            <form @submit.prevent="submit">
                <v-card-text>
                    <v-row>
                        <!-- Left Column: Form -->
                        <v-col cols="12" md="8" order="2" order-md="1">
                            <!-- Leave Details -->
                            <v-card variant="outlined" class="mb-4">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-file-document-edit-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Leave Details</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-row dense>
                                        <v-col cols="12" md="6">
                                            <v-select
                                                v-model="form.leave_type_id"
                                                :items="leaveTypes"
                                                :error-messages="form.errors.leave_type_id"
                                                density="compact"
                                                hide-details="auto"
                                                item-title="name"
                                                item-value="id"
                                                label="Leave Type"
                                                variant="outlined"
                                            >
                                                <template #prepend-inner>
                                                    <v-icon size="18" color="medium-emphasis">mdi-file-tree</v-icon>
                                                </template>
                                            </v-select>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <v-text-field
                                                v-model="form.title"
                                                :error-messages="form.errors.title"
                                                density="compact"
                                                hide-details="auto"
                                                label="Title"
                                                placeholder="e.g., Family vacation, Medical appointment"
                                                variant="outlined"
                                            >
                                                <template #prepend-inner>
                                                    <v-icon size="18" color="medium-emphasis">mdi-format-title</v-icon>
                                                </template>
                                            </v-text-field>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Duration -->
                            <v-card variant="outlined" class="mb-4">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-calendar-range</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Duration</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-row dense>
                                        <v-col cols="12" md="6">
                                            <el-date-picker
                                                v-model="form.started_at"
                                                format="YYYY-MM-DD"
                                                placeholder="Start Date"
                                                size="large"
                                                style="width: 100%"
                                                type="date"
                                                value-format="YYYY-MM-DD"
                                            />
                                            <div v-if="form.errors.started_at" class="text-error text-caption mt-1">
                                                {{ form.errors.started_at }}
                                            </div>
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <el-date-picker
                                                v-model="form.ended_at"
                                                format="YYYY-MM-DD"
                                                placeholder="End Date"
                                                size="large"
                                                style="width: 100%"
                                                type="date"
                                                value-format="YYYY-MM-DD"
                                            />
                                            <div v-if="form.errors.ended_at" class="text-error text-caption mt-1">
                                                {{ form.errors.ended_at }}
                                            </div>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Reason -->
                            <v-card variant="outlined">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-text-box-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Reason</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-textarea
                                        v-model="form.notes"
                                        :error-messages="form.errors.notes"
                                        density="compact"
                                        hide-details="auto"
                                        label="Why do you need this leave?"
                                        placeholder="Briefly describe your reason..."
                                        rows="3"
                                        variant="outlined"
                                    />
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <!-- Right Column: Balance & Summary -->
                        <v-col cols="12" md="4" order="1" order-md="2">
                            <!-- Leave Balance -->
                            <v-card variant="outlined" class="mb-4">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-wallet-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Your Balance</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text class="pa-2">
                                    <v-row dense>
                                        <v-col v-for="balance in balances" :key="balance.id" cols="6">
                                            <v-card
                                                :variant="form.leave_type_id === balance.id ? 'flat' : 'tonal'"
                                                :color="form.leave_type_id === balance.id ? 'primary' : undefined"
                                                class="text-center pa-3 cursor-pointer"
                                                @click="form.leave_type_id = balance.id"
                                            >
                                                <div class="text-caption font-weight-bold">{{ balance.name }}</div>
                                                <div class="text-h6 mt-1">{{ balance.remaining }}</div>
                                                <div class="text-caption text-medium-emphasis">of {{ balance.total }} days</div>
                                            </v-card>
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>

                            <!-- Request Summary -->
                            <v-card v-if="totalDays > 0" variant="outlined" class="mb-4">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-calculator-variant-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Request Summary</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text class="pa-0">
                                    <v-list density="compact">
                                        <v-list-item>
                                            <template #prepend>
                                                <v-icon size="18" color="primary">mdi-calendar-check</v-icon>
                                            </template>
                                            <v-list-item-title class="text-body-2">Days Requested</v-list-item-title>
                                            <template #append>
                                                <v-chip size="small" color="primary" variant="tonal" label>
                                                    {{ totalDays }}
                                                </v-chip>
                                            </template>
                                        </v-list-item>
                                        <v-list-item v-if="selectedBalance">
                                            <template #prepend>
                                                <v-icon size="18" color="success">mdi-wallet-outline</v-icon>
                                            </template>
                                            <v-list-item-title class="text-body-2">Current Balance</v-list-item-title>
                                            <template #append>
                                                <v-chip size="small" color="success" variant="tonal" label>
                                                    {{ selectedBalance.remaining }}
                                                </v-chip>
                                            </template>
                                        </v-list-item>
                                        <v-list-item v-if="selectedBalance">
                                            <template #prepend>
                                                <v-icon size="18" :color="hasEnoughBalance ? 'info' : 'error'">mdi-scale-balance</v-icon>
                                            </template>
                                            <v-list-item-title class="text-body-2">After This Leave</v-list-item-title>
                                            <template #append>
                                                <v-chip size="small" :color="hasEnoughBalance ? 'info' : 'error'" variant="tonal" label>
                                                    {{ selectedBalance.remaining - totalDays }}
                                                </v-chip>
                                            </template>
                                        </v-list-item>
                                    </v-list>
                                </v-card-text>
                            </v-card>

                            <!-- Insufficient Balance Warning -->
                            <v-alert
                                v-if="totalDays > 0 && !hasEnoughBalance"
                                type="error"
                                variant="tonal"
                                density="compact"
                                class="text-caption"
                            >
                                Not enough balance. You have <strong>{{ selectedBalance?.remaining }}</strong> day(s) left
                                but requesting <strong>{{ totalDays }}</strong>.
                            </v-alert>
                        </v-col>
                    </v-row>
                </v-card-text>

                <v-divider/>
                <v-card-actions class="justify-center pa-4">
                    <v-btn
                        :disabled="form.processing || !hasEnoughBalance"
                        :loading="form.processing"
                        class="text-none"
                        color="primary"
                        prepend-icon="mdi-send"
                        type="submit"
                        variant="flat"
                    >
                        Submit Leave Request
                    </v-btn>
                </v-card-actions>
            </form>
        </v-card>
    </DefaultLayout>
</template>
