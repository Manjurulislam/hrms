<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import WorkflowBuilder from "@/Components/modules/approval-workflow/WorkflowBuilder.vue";

const toast = useToast();

defineProps({
    companies: Array,
    approverTypes: Array,
    conditionTypes: Array,
    designations: Array,
    employees: Array,
});

const form = useForm({
    name: '',
    company_id: null,
    steps: [
        {approver_type: 'direct_manager', approver_value: null, is_mandatory: true, condition_type: 'always', condition_value: null},
    ],
});

const submit = () => {
    form.post(route('approval-workflows.store'), {
        onSuccess: () => toast('Workflow created successfully.'),
        onError: () => toast.error('Something went wrong. Please try again.'),
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Approval Workflow"/>
        <WorkflowBuilder
            v-model:company-id="form.company_id"
            v-model:name="form.name"
            :approver-types="approverTypes"
            :companies="companies"
            :condition-types="conditionTypes"
            :designations="designations"
            :employees="employees"
            :errors="form.errors"
            :processing="form.processing"
            :steps="form.steps"
            submit-label="Create Workflow"
            title="Create Approval Workflow"
            @submit="submit"
        />
    </DefaultLayout>
</template>
