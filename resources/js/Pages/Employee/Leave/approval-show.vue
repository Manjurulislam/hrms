<script setup>
import {Head, Link, useForm} from '@inertiajs/vue3';
import {ref} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const props = defineProps({
    leaveRequest: Object,
    isCurrentApprover: Boolean,
});

const remarks = ref('');
const showRejectDialog = ref(false);

const approveForm = useForm({remarks: ''});
const rejectForm = useForm({remarks: ''});

const approve = () => {
    approveForm.remarks = remarks.value;
    approveForm.post(route('emp-leave.approvals.approve', props.leaveRequest.id));
};

const reject = () => {
    rejectForm.remarks = remarks.value;
    rejectForm.post(route('emp-leave.approvals.reject', props.leaveRequest.id), {
        onSuccess: () => showRejectDialog.value = false,
    });
};

const getStatusColor = (status) => ({
    'pending': 'orange', 'in_review': 'blue', 'approved': 'green',
    'rejected': 'red', 'cancelled': 'grey',
})[status] || 'default';

const getStatusLabel = (status) => ({
    'pending': 'Pending', 'in_review': 'In Review', 'approved': 'Approved',
    'rejected': 'Rejected', 'cancelled': 'Cancelled',
})[status] || status;

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

const emp = props.leaveRequest.employee;
const empName = `${emp?.first_name || ''} ${emp?.last_name || ''}`.trim();
const empInitials = `${emp?.first_name?.charAt(0) || ''}${emp?.last_name?.charAt(0) || ''}`;
</script>

<template>
    <DefaultLayout>
        <Head title="Leave Approval Details"/>

        <v-row>
            <v-col cols="12">
                <!-- Back Button -->
                <Link :href="route('emp-leave.approvals')" class="text-decoration-none">
                    <v-btn variant="text" color="primary" prepend-icon="mdi-arrow-left" class="mb-4 px-0">
                        Back to Approvals
                    </v-btn>
                </Link>

                <v-row>
                    <!-- Left Column: Request Details -->
                    <v-col cols="12" lg="8">
                        <!-- Employee & Leave Info Card -->
                        <v-card variant="flat" rounded="lg" border class="mb-4">
                            <v-card-text class="pa-5">
                                <div class="d-flex align-center justify-space-between mb-4">
                                    <div class="d-flex align-center ga-3">
                                        <v-avatar color="primary" size="52">
                                            <span class="text-h6 font-weight-bold text-white">{{ empInitials }}</span>
                                        </v-avatar>
                                        <div>
                                            <div class="text-h6 font-weight-bold">{{ empName }}</div>
                                            <div class="text-body-2 text-medium-emphasis">
                                                Employee ID: {{ emp?.id_no || '-' }}
                                            </div>
                                        </div>
                                    </div>
                                    <v-chip
                                        :color="getStatusColor(leaveRequest.status)"
                                        size="default"
                                        variant="flat"
                                        label
                                        class="font-weight-bold"
                                    >
                                        {{ getStatusLabel(leaveRequest.status) }}
                                    </v-chip>
                                </div>

                                <v-divider class="mb-4"/>

                                <!-- Details Grid -->
                                <v-row>
                                    <v-col cols="6" sm="3">
                                        <div class="text-caption text-medium-emphasis mb-1">Leave Type</div>
                                        <v-chip color="primary" size="small" variant="tonal">
                                            {{ leaveRequest.leave_type?.name }}
                                        </v-chip>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <div class="text-caption text-medium-emphasis mb-1">Total Days</div>
                                        <div class="text-h6 font-weight-bold text-primary">
                                            {{ leaveRequest.total_days }}
                                            <span class="text-body-2 font-weight-regular text-medium-emphasis">
                                                {{ leaveRequest.total_days > 1 ? 'days' : 'day' }}
                                            </span>
                                        </div>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <div class="text-caption text-medium-emphasis mb-1">Start Date</div>
                                        <div class="text-body-1 font-weight-bold">{{ formatDate(leaveRequest.started_at) }}</div>
                                    </v-col>
                                    <v-col cols="6" sm="3">
                                        <div class="text-caption text-medium-emphasis mb-1">End Date</div>
                                        <div class="text-body-1 font-weight-bold">{{ formatDate(leaveRequest.ended_at) }}</div>
                                    </v-col>
                                </v-row>

                                <!-- Reason / Notes Section -->
                                <div v-if="leaveRequest.title || leaveRequest.notes" class="mt-4">
                                    <v-divider class="mb-4"/>
                                    <div v-if="leaveRequest.title" class="mb-3">
                                        <div class="text-caption text-medium-emphasis mb-1">Subject</div>
                                        <div class="text-body-1 font-weight-medium">{{ leaveRequest.title }}</div>
                                    </div>
                                    <div v-if="leaveRequest.notes">
                                        <div class="text-caption text-medium-emphasis mb-1">Reason / Notes</div>
                                        <v-sheet color="grey-lighten-4" rounded="lg" class="pa-3">
                                            <div class="text-body-2" style="white-space: pre-wrap;">{{ leaveRequest.notes }}</div>
                                        </v-sheet>
                                    </div>
                                </div>

                                <!-- Current Approver -->
                                <div v-if="leaveRequest.current_approver" class="mt-4">
                                    <v-divider class="mb-4"/>
                                    <div class="text-caption text-medium-emphasis mb-1">Current Approver</div>
                                    <div class="d-flex align-center ga-2">
                                        <v-avatar color="info" size="28">
                                            <span class="text-caption font-weight-bold text-white">
                                                {{ leaveRequest.current_approver.first_name?.charAt(0) }}{{ leaveRequest.current_approver.last_name?.charAt(0) }}
                                            </span>
                                        </v-avatar>
                                        <span class="text-body-2 font-weight-medium">
                                            {{ leaveRequest.current_approver.first_name }} {{ leaveRequest.current_approver.last_name }}
                                        </span>
                                    </div>
                                </div>
                            </v-card-text>
                        </v-card>

                        <!-- Action Card -->
                        <v-card v-if="isCurrentApprover" variant="flat" rounded="lg" border>
                            <v-card-text class="pa-5">
                                <div class="text-subtitle-1 font-weight-bold mb-4">
                                    <v-icon size="20" class="mr-1">mdi-gesture-tap</v-icon>
                                    Take Action
                                </div>

                                <v-textarea
                                    v-model="remarks"
                                    density="compact"
                                    label="Remarks (optional)"
                                    placeholder="Add your remarks here..."
                                    rows="3"
                                    variant="outlined"
                                    class="mb-4"
                                    hide-details
                                />

                                <div class="d-flex flex-wrap ga-3">
                                    <v-btn
                                        :loading="approveForm.processing"
                                        color="success"
                                        prepend-icon="mdi-check"
                                        variant="flat"
                                        size="large"
                                        @click="approve"
                                    >
                                        Approve
                                    </v-btn>

                                    <v-btn
                                        color="error"
                                        prepend-icon="mdi-close"
                                        variant="outlined"
                                        size="large"
                                        @click="showRejectDialog = true"
                                    >
                                        Reject
                                    </v-btn>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Right Column: Approval Timeline -->
                    <v-col cols="12" lg="4">
                        <v-card variant="flat" rounded="lg" border>
                            <v-card-text class="pa-5">
                                <div class="text-subtitle-1 font-weight-bold mb-4">
                                    <v-icon size="20" class="mr-1">mdi-timeline-clock-outline</v-icon>
                                    Approval History
                                </div>

                                <v-timeline density="compact" side="end" line-thickness="2">
                                    <v-timeline-item
                                        v-for="approval in leaveRequest.approvals"
                                        :key="approval.id"
                                        :dot-color="getStatusColor(approval.status)"
                                        size="x-small"
                                    >
                                        <v-card variant="tonal" :color="getStatusColor(approval.status)" density="compact">
                                            <v-card-text class="pa-3">
                                                <div class="d-flex align-center justify-space-between mb-1 ga-2">
                                                    <span class="text-body-2 font-weight-bold">
                                                        {{ approval.approver?.first_name }} {{ approval.approver?.last_name }}
                                                    </span>
                                                    <v-chip
                                                        :color="getStatusColor(approval.status)"
                                                        size="x-small"
                                                        variant="flat"
                                                        label
                                                    >
                                                        {{ getStatusLabel(approval.status) }}
                                                    </v-chip>
                                                </div>
                                                <div v-if="approval.remarks" class="text-body-2 mt-2 font-italic">
                                                    "{{ approval.remarks }}"
                                                </div>
                                                <div class="text-caption text-medium-emphasis mt-2">
                                                    <v-icon size="12" class="mr-1">mdi-clock-outline</v-icon>
                                                    {{ approval.acted_at ? formatDateTime(approval.acted_at) : 'Awaiting action' }}
                                                </div>
                                            </v-card-text>
                                        </v-card>
                                    </v-timeline-item>
                                </v-timeline>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-col>
        </v-row>

        <!-- Reject Dialog -->
        <v-dialog v-model="showRejectDialog" max-width="480">
            <v-card rounded="lg">
                <v-card-title class="d-flex align-center ga-2 pa-5 pb-2">
                    <v-icon color="error">mdi-alert-circle-outline</v-icon>
                    Reject Leave Request
                </v-card-title>
                <v-card-text class="px-5">
                    <p class="text-body-2 text-medium-emphasis mb-4">
                        Are you sure you want to reject the leave request from
                        <strong>{{ empName }}</strong>?
                    </p>
                    <v-textarea
                        v-model="remarks"
                        density="compact"
                        label="Rejection Remarks"
                        placeholder="Please provide a reason for rejection..."
                        rows="3"
                        variant="outlined"
                        hide-details
                    />
                </v-card-text>
                <v-card-actions class="pa-5 pt-2">
                    <v-spacer/>
                    <v-btn variant="text" @click="showRejectDialog = false">Cancel</v-btn>
                    <v-btn
                        :loading="rejectForm.processing"
                        color="error"
                        variant="flat"
                        prepend-icon="mdi-close"
                        @click="reject"
                    >
                        Confirm Reject
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </DefaultLayout>
</template>
