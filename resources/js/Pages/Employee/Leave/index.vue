<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head, Link, router} from '@inertiajs/vue3';
import {computed, reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {useToast} from 'vue-toastification';
import {useDisplay} from 'vuetify';

const toast = useToast();
const {smAndDown} = useDisplay();

const props = defineProps({
    statusOptions: Array,
});

const allHeaders = [
    {title: 'SL', align: 'start', sortable: false, key: 'id', mobile: true},
    {title: 'Leave Type', key: 'leave_type', mobile: true},
    {title: 'Title', key: 'title', mobile: false},
    {title: 'Start Date', key: 'started_at', mobile: true},
    {title: 'End Date', key: 'ended_at', mobile: true},
    {title: 'Days', key: 'total_days', mobile: true},
    {title: 'Status', key: 'status', sortable: false, mobile: true},
    {title: 'Current Approver', key: 'current_approver', mobile: false},
    {title: 'Actions', key: 'actions', sortable: false, width: '5%', mobile: true},
];

const headers = computed(() =>
    smAndDown.value ? allHeaders.filter(h => h.mobile) : allHeaders
);

const state = reactive({
    pagination: {
        itemsPerPage: 50,
        totalItems: 0,
    },
    filters: {
        status: null,
        per_page: 50,
        page: 1,
    },
    serverItems: [],
    loading: true,
});

const setLimit = (obj) => {
    const {page, itemsPerPage, sortBy} = obj;
    state.filters.page = page;
    state.filters.sort = sortBy;
    state.filters.per_page = itemsPerPage === 'All' ? -1 : itemsPerPage;
};

const getData = (obj) => {
    setLimit(obj);
    state.loading = true;
    axios.get(route('emp-leave.get'), {params: state.filters}).then(({data}) => {
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    }).catch(() => {
        toast.error('Failed to load leave requests.');
    }).finally(() => {
        state.loading = false;
    });
};

const refreshData = () => {
    getData({page: 1, itemsPerPage: state.pagination.itemsPerPage, sortBy: []});
};

const cancelRequest = (id) => {
    if (confirm('Are you sure you want to cancel this leave request?')) {
        router.post(route('emp-leave.cancel', id), {}, {
            onSuccess: () => refreshData(),
        });
    }
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'in_review': 'info',
        'approved': 'success',
        'rejected': 'error',
        'cancelled': 'grey',
    };
    return colors[status] || 'default';
};

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'Pending',
        'in_review': 'In Review',
        'approved': 'Approved',
        'rejected': 'Rejected',
        'cancelled': 'Cancelled',
    };
    return labels[status] || status;
};
</script>

<template>
    <DefaultLayout>
        <Head title="My Leaves"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle icon="mdi-calendar-clock" title="My Leaves">
                        <template #action>
                            <Link :href="route('emp-leave.create')">
                                <v-btn
                                    class="text-none"
                                    color="primary"
                                    prepend-icon="mdi-plus"
                                    size="small"
                                    variant="flat"
                                >
                                    Apply Leave
                                </v-btn>
                            </Link>
                        </template>
                    </CardTitle>

                    <v-card-text>
                        <v-row class="mb-4" dense>
                            <v-col cols="12" md="3">
                                <v-select
                                    v-model="state.filters.status"
                                    :items="statusOptions"
                                    clearable
                                    density="compact"
                                    hide-details
                                    item-title="label"
                                    item-value="value"
                                    label="Status"
                                    variant="outlined"
                                    @update:model-value="refreshData"
                                />
                            </v-col>
                        </v-row>

                        <v-data-table-server
                            :headers="headers"
                            :items="state.serverItems"
                            :items-length="state.pagination.totalItems"
                            :items-per-page="state.pagination.itemsPerPage"
                            :loading="state.loading"
                            density="compact"
                            item-value="id"
                            @update:options="getData"
                        >
                            <template v-slot:item.id="{ index }">
                                {{ (state.filters.page - 1) * state.pagination.itemsPerPage + index + 1 }}
                            </template>
                            <template v-slot:item.leave_type="{ item }">
                                {{ item.leave_type?.name || '-' }}
                            </template>
                            <template v-slot:item.started_at="{ item }">
                                {{ item.started_at ? new Date(item.started_at).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}) : '-' }}
                            </template>
                            <template v-slot:item.ended_at="{ item }">
                                {{ item.ended_at ? new Date(item.ended_at).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'}) : '-' }}
                            </template>
                            <template v-slot:item.status="{ item }">
                                <v-chip
                                    :color="getStatusColor(item.status)"
                                    class="font-weight-regular"
                                    size="x-small"
                                    variant="tonal"
                                >
                                    {{ getStatusLabel(item.status) }}
                                </v-chip>
                            </template>
                            <template v-slot:item.current_approver="{ item }">
                                <span v-if="item.current_approver">
                                    {{ item.current_approver.first_name }} {{ item.current_approver.last_name }}
                                </span>
                                <span v-else class="text-medium-emphasis">-</span>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn
                                    v-if="item.status === 'pending'"
                                    color="error"
                                    icon="mdi-close-circle"
                                    size="x-small"
                                    variant="text"
                                    @click="cancelRequest(item.id)"
                                />
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
