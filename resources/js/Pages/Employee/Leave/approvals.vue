<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head, Link} from '@inertiajs/vue3';
import {computed, reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {useToast} from 'vue-toastification';
import {useDisplay} from 'vuetify';

const toast = useToast();
const {smAndDown} = useDisplay();

const allHeaders = [
    {title: 'SL', align: 'start', sortable: false, key: 'id', mobile: true},
    {title: 'Employee', key: 'employee', mobile: true},
    {title: 'Emp ID', key: 'emp_id', mobile: false},
    {title: 'Leave Type', key: 'leave_type', mobile: false},
    {title: 'Start Date', key: 'started_at', mobile: true},
    {title: 'End Date', key: 'ended_at', mobile: true},
    {title: 'Days', key: 'total_days', mobile: true},
    {title: 'Notes', key: 'notes', sortable: false, mobile: false},
    {title: 'Status', key: 'status', sortable: false, mobile: true},
    {title: 'Actions', key: 'actions', sortable: false, width: '5%', mobile: true},
];

const headers = computed(() =>
    smAndDown.value ? allHeaders.filter(h => h.mobile) : allHeaders
);

const state = reactive({
    serverItems: [],
    loading: true,
});

const loadData = () => {
    state.loading = true;
    axios.get(route('emp-leave.approvals.get')).then(({data}) => {
        state.serverItems = data.data;
    }).catch(() => {
        toast.error('Failed to load approvals.');
    }).finally(() => {
        state.loading = false;
    });
};

loadData();

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

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'});
};
</script>

<template>
    <DefaultLayout>
        <Head title="Pending Approvals"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle icon="mdi-clipboard-check-outline" title="Pending Approvals"/>

                    <v-card-text>
                        <v-data-table
                            :headers="headers"
                            :items="state.serverItems"
                            :loading="state.loading"
                            density="compact"
                            item-value="id"
                        >
                            <template v-slot:item.id="{ index }">
                                {{ index + 1 }}
                            </template>
                            <template v-slot:item.employee="{ item }">
                                <div class="d-flex align-center ga-2">
                                    <v-avatar size="28" color="primary" variant="tonal">
                                        <v-img v-if="item.employee?.avatar_url" :src="item.employee.avatar_url" cover/>
                                        <span v-else class="text-caption text-uppercase">{{ item.employee?.first_name?.charAt(0) }}{{ item.employee?.last_name?.charAt(0) }}</span>
                                    </v-avatar>
                                    <span>{{ item.employee?.first_name }} {{ item.employee?.last_name }}</span>
                                </div>
                            </template>
                            <template v-slot:item.emp_id="{ item }">
                                {{ item.employee?.id_no || '-' }}
                            </template>
                            <template v-slot:item.leave_type="{ item }">
                                {{ item.leave_type?.name || '-' }}
                            </template>
                            <template v-slot:item.started_at="{ item }">
                                {{ formatDate(item.started_at) }}
                            </template>
                            <template v-slot:item.ended_at="{ item }">
                                {{ formatDate(item.ended_at) }}
                            </template>
                            <template v-slot:item.notes="{ item }">
                                <span v-if="item.notes" class="text-body-2" :title="item.notes">
                                    {{ item.notes.length > 40 ? item.notes.substring(0, 40) + '...' : item.notes }}
                                </span>
                                <span v-else class="text-medium-emphasis">-</span>
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
                            <template v-slot:item.actions="{ item }">
                                <Link :href="route('emp-leave.approvals.show', item.id)">
                                    <v-btn
                                        color="primary"
                                        icon="mdi-eye"
                                        size="x-small"
                                        variant="text"
                                    />
                                </Link>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
