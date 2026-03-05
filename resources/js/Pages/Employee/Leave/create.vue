<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head, useForm} from '@inertiajs/vue3';
import {computed} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

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
    form.post(route('emp-leave.store'));
};
</script>

<template>
    <DefaultLayout>
        <Head title="Apply Leave"/>
        <v-row no-gutters>
            <v-col cols="12" md="8" offset-md="2">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back', route: 'emp-leave.index', icon: 'mdi-arrow-left-bold'}"
                        icon="mdi-calendar-plus"
                        title="Apply Leave"
                    />

                    <v-card-text>
                        <!-- Balance Cards -->
                        <v-row class="mb-4" dense>
                            <v-col v-for="balance in balances" :key="balance.id" cols="6" md="3">
                                <v-card
                                    :variant="form.leave_type_id === balance.id ? 'flat' : 'outlined'"
                                    :color="form.leave_type_id === balance.id ? 'primary' : undefined"
                                    class="text-center pa-3 cursor-pointer"
                                    @click="form.leave_type_id = balance.id"
                                >
                                    <div class="font-weight-bold">{{ balance.name }}</div>
                                    <div class="text-h6 mt-1">{{ balance.remaining }}/{{ balance.total }}</div>
                                    <div class="text-caption">{{ balance.used }} used</div>
                                </v-card>
                            </v-col>
                        </v-row>

                        <v-form @submit.prevent="submit">
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
                                        label="Leave Type *"
                                        variant="outlined"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        v-model="form.title"
                                        :error-messages="form.errors.title"
                                        density="compact"
                                        hide-details="auto"
                                        label="Title"
                                        variant="outlined"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <el-date-picker
                                        v-model="form.started_at"
                                        format="YYYY-MM-DD"
                                        placeholder="Start Date *"
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
                                        placeholder="End Date *"
                                        size="large"
                                        style="width: 100%"
                                        type="date"
                                        value-format="YYYY-MM-DD"
                                    />
                                    <div v-if="form.errors.ended_at" class="text-error text-caption mt-1">
                                        {{ form.errors.ended_at }}
                                    </div>
                                </v-col>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="form.notes"
                                        :error-messages="form.errors.notes"
                                        density="compact"
                                        hide-details="auto"
                                        label="Notes / Reason"
                                        rows="3"
                                        variant="outlined"
                                    />
                                </v-col>
                            </v-row>

                            <!-- Summary -->
                            <v-row v-if="totalDays > 0" class="mt-2" dense>
                                <v-col cols="12">
                                    <v-alert
                                        :color="hasEnoughBalance ? 'info' : 'error'"
                                        :type="hasEnoughBalance ? 'info' : 'error'"
                                        density="compact"
                                        variant="tonal"
                                    >
                                        <template v-if="hasEnoughBalance">
                                            Total: <strong>{{ totalDays }} day(s)</strong>
                                            <span v-if="selectedBalance">
                                                | Remaining after: <strong>{{ selectedBalance.remaining - totalDays }}</strong>
                                            </span>
                                        </template>
                                        <template v-else>
                                            Insufficient balance. You have <strong>{{ selectedBalance?.remaining }}</strong> day(s)
                                            remaining but requesting <strong>{{ totalDays }}</strong>.
                                        </template>
                                    </v-alert>
                                </v-col>
                            </v-row>

                            <v-row class="mt-4">
                                <v-col cols="12" class="d-flex justify-end">
                                    <v-btn
                                        :disabled="form.processing || !hasEnoughBalance"
                                        :loading="form.processing"
                                        color="primary"
                                        type="submit"
                                        variant="flat"
                                    >
                                        Submit Leave Request
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
