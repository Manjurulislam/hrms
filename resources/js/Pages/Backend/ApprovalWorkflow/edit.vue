<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import WorkflowBuilder from "@/Components/modules/approval-workflow/WorkflowBuilder.vue";

const toast = useToast();

const props = defineProps({
    workflow: Object,
    companies: Array,
    approverTypes: Array,
    conditionTypes: Array,
    designations: Array,
    employees: Array,
});

const form = useForm({
    name: props.workflow.name,
    company_id: props.workflow.company_id,
    steps: props.workflow.steps.map(s => ({
        approver_type: s.approver_type,
        approver_value: s.approver_value,
        is_mandatory: s.is_mandatory,
        condition_type: s.condition_type,
        condition_value: s.condition_value,
    })),
});

const submit = () => {
    form.put(route('approval-workflows.update', props.workflow.id), {
        onSuccess: () => toast('Workflow updated successfully.'),
        onError: () => toast.error('Something went wrong. Please try again.'),
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Edit Approval Workflow"/>
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
            submit-label="Update Workflow"
            title="Edit Approval Workflow"
            @submit="submit"
        />
    </DefaultLayout>
</template>
