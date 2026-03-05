<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head, useForm} from '@inertiajs/vue3';
import {ref} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const props = defineProps({
    leaveRequest: Object,
    isCurrentApprover: Boolean,
    approverLevel: Number,
});

const remarks = ref('');
const showRejectDialog = ref(false);

const approveForm = useForm({
    remarks: '',
    forward: false,
});

const rejectForm = useForm({
    remarks: '',
});

const approve = (forward = false) => {
    approveForm.remarks = remarks.value;
    approveForm.forward = forward;
    approveForm.post(route('emp-leave.approvals.approve', props.leaveRequest.id));
};

const reject = () => {
    rejectForm.remarks = remarks.value;
    rejectForm.post(route('emp-leave.approvals.reject', props.leaveRequest.id), {
        onSuccess: () => {
            showRejectDialog.value = false;
        },
    });
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

const getApprovalStatusColor = (status) => {
    const colors = {
        'pending': 'warning',
        'approved': 'success',
        'rejected': 'error',
    };
    return colors[status] || 'default';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'});
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Leave Approval"/>
        <v-row no-gutters>
            <v-col cols="12" md="8" offset-md="2">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back', route: 'emp-leave.approvals', icon: 'mdi-arrow-left-bold'}"
                        icon="mdi-clipboard-check-outline"
                        title="Leave Approval"
                    />

                    <v-card-text>
                        <!-- Leave Details -->
                        <v-card variant="tonal" color="primary" class="pa-4 mb-4">
                            <v-row dense>
                                <v-col cols="6" md="3">
                                    <span class="text-caption text-medium-emphasis">Employee</span>
                                    <div class="font-weight-bold">
                                        {{ leaveRequest.employee?.first_name }} {{ leaveRequest.employee?.last_name }}
                                    </div>
                                </v-col>
                                <v-col cols="6" md="3">
                                    <span class="text-caption text-medium-emphasis">Emp ID</span>
                                    <div class="font-weight-bold">{{ leaveRequest.employee?.id_no }}</div>
                                </v-col>
                                <v-col cols="6" md="3">
                                    <span class="text-caption text-medium-emphasis">Leave Type</span>
                                    <div class="font-weight-bold">{{ leaveRequest.leave_type?.name }}</div>
                                </v-col>
                                <v-col cols="6" md="3">
                                    <span class="text-caption text-medium-emphasis">Status</span>
                                    <div>
                                        <v-chip
                                            :color="getStatusColor(leaveRequest.status)"
                                            size="x-small"
                                            variant="tonal"
                                        >
                                            {{ getStatusLabel(leaveRequest.status) }}
                                        </v-chip>
                                    </div>
                                </v-col>
                            </v-row>
                            <v-row dense class="mt-2">
                                <v-col cols="6" md="3">
                                    <span class="text-caption text-medium-emphasis">Start Date</span>
                                    <div class="font-weight-bold">{{ formatDate(leaveRequest.started_at) }}</div>
                                </v-col>
                                <v-col cols="6" md="3">
                                    <span class="text-caption text-medium-emphasis">End Date</span>
                                    <div class="font-weight-bold">{{ formatDate(leaveRequest.ended_at) }}</div>
                                </v-col>
                                <v-col cols="6" md="3">
                                    <span class="text-caption text-medium-emphasis">Total Days</span>
                                    <div class="font-weight-bold">{{ leaveRequest.total_days }}</div>
                                </v-col>
                                <v-col cols="6" md="3">
                                    <span class="text-caption text-medium-emphasis">Current Approver</span>
                                    <div class="font-weight-bold">
                                        {{ leaveRequest.current_approver
                                            ? leaveRequest.current_approver.first_name + ' ' + leaveRequest.current_approver.last_name
                                            : '-' }}
                                    </div>
                                </v-col>
                            </v-row>
                            <v-row v-if="leaveRequest.title || leaveRequest.notes" dense class="mt-2">
                                <v-col v-if="leaveRequest.title" cols="12" md="6">
                                    <span class="text-caption text-medium-emphasis">Title</span>
                                    <div class="font-weight-bold">{{ leaveRequest.title }}</div>
                                </v-col>
                                <v-col v-if="leaveRequest.notes" cols="12" md="6">
                                    <span class="text-caption text-medium-emphasis">Notes</span>
                                    <div>{{ leaveRequest.notes }}</div>
                                </v-col>
                            </v-row>
                        </v-card>

                        <!-- Approval Timeline -->
                        <div class="mb-4">
                            <div class="text-subtitle-2 font-weight-bold mb-2">Approval History</div>
                            <v-timeline density="compact" side="end">
                                <v-timeline-item
                                    v-for="approval in leaveRequest.approvals"
                                    :key="approval.id"
                                    :dot-color="getApprovalStatusColor(approval.status)"
                                    size="small"
                                >
                                    <div class="d-flex align-center ga-2">
                                        <strong>
                                            {{ approval.approver?.first_name }} {{ approval.approver?.last_name }}
                                        </strong>
                                        <v-chip
                                            :color="getApprovalStatusColor(approval.status)"
                                            size="x-small"
                                            variant="flat"
                                        >
                                            {{ getStatusLabel(approval.status) }}
                                        </v-chip>
                                    </div>
                                    <div v-if="approval.remarks" class="text-body-2 mt-1">
                                        {{ approval.remarks }}
                                    </div>
                                    <div class="text-caption text-medium-emphasis mt-1">
                                        {{ approval.acted_at ? formatDateTime(approval.acted_at) : 'Pending' }}
                                    </div>
                                </v-timeline-item>
                            </v-timeline>
                        </div>

                        <!-- Action Buttons -->
                        <div v-if="isCurrentApprover" class="mt-4">
                            <v-divider class="mb-4"/>
                            <div class="text-subtitle-2 font-weight-bold mb-2">Take Action</div>

                            <v-textarea
                                v-model="remarks"
                                density="compact"
                                hide-details
                                label="Remarks (optional)"
                                rows="2"
                                variant="outlined"
                                class="mb-4"
                            />

                            <div class="d-flex ga-3">
                                <!-- Level > 2 (Team Lead/PM): Approve auto-forwards -->
                                <v-btn
                                    v-if="approverLevel > 2"
                                    :loading="approveForm.processing"
                                    color="success"
                                    prepend-icon="mdi-check"
                                    variant="flat"
                                    @click="approve(false)"
                                >
                                    Approve
                                </v-btn>

                                <!-- Level 2 (CTO): Final Approve or Forward -->
                                <v-btn
                                    v-if="approverLevel === 2"
                                    :loading="approveForm.processing"
                                    color="success"
                                    prepend-icon="mdi-check-all"
                                    variant="flat"
                                    @click="approve(false)"
                                >
                                    Final Approve
                                </v-btn>
                                <v-btn
                                    v-if="approverLevel === 2"
                                    :loading="approveForm.processing"
                                    color="info"
                                    prepend-icon="mdi-forward"
                                    variant="flat"
                                    @click="approve(true)"
                                >
                                    Approve & Forward to CEO
                                </v-btn>

                                <!-- Level 1 (CEO): Final Approve -->
                                <v-btn
                                    v-if="approverLevel === 1"
                                    :loading="approveForm.processing"
                                    color="success"
                                    prepend-icon="mdi-check-all"
                                    variant="flat"
                                    @click="approve(false)"
                                >
                                    Final Approve
                                </v-btn>

                                <!-- Reject - all levels -->
                                <v-btn
                                    color="error"
                                    prepend-icon="mdi-close"
                                    variant="flat"
                                    @click="showRejectDialog = true"
                                >
                                    Reject
                                </v-btn>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Reject Confirmation Dialog -->
        <v-dialog v-model="showRejectDialog" max-width="400">
            <v-card>
                <v-card-title>Reject Leave Request</v-card-title>
                <v-card-text>
                    <p class="mb-3">Are you sure you want to reject this leave request?</p>
                    <v-textarea
                        v-model="remarks"
                        density="compact"
                        hide-details
                        label="Rejection Remarks"
                        rows="3"
                        variant="outlined"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer/>
                    <v-btn variant="text" @click="showRejectDialog = false">Cancel</v-btn>
                    <v-btn
                        :loading="rejectForm.processing"
                        color="error"
                        variant="flat"
                        @click="reject"
                    >
                        Reject
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </DefaultLayout>
</template>
