<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import {computed, onMounted} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import TextInput from '@/Components/common/form/TextInput.vue';

const toast = useToast();
const props = defineProps({
    item: Object,
    companies: Array,
    departments: Array,
});

let form = useForm({
    title: '',
    description: '',
    company_id: null,
    department_id: null,
    published_at: '',
    expired_at: '',
});

const filteredDepartments = computed(() => {
    if (!form.company_id) return [];
    return props.departments.filter(d => d.company_id === form.company_id);
});

const submit = () => {
    form.put(route('notices.update', props.item.id), {
        onSuccess: () => toast('Notice has been updated successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};

onMounted(() => {
    form = Object.assign(form, props.item);
});
</script>

<template>
    <DefaultLayout>
        <Head title="Edit Notice"/>
        <v-card>
            <CardTitle
                :extra-route="{title: 'Back', route: 'notices.index', icon: 'mdi-arrow-left-bold'}"
                icon="mdi-clipboard-text-outline"
                title="Edit Notice"
            />
            <form @submit.prevent="submit">
                <v-card-text>
                    <v-row>
                        <!-- Left Column -->
                        <v-col cols="12" md="8">
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-information-outline</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Notice Details</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-row dense>
                                        <v-col cols="12">
                                            <TextInput
                                                v-model="form.title"
                                                :error-messages="form.errors.title"
                                                label="Title"
                                            />
                                        </v-col>
                                        <v-col cols="12">
                                            <v-textarea
                                                v-model="form.description"
                                                :error-messages="form.errors.description"
                                                density="compact"
                                                label="Description"
                                                placeholder="Enter notice content..."
                                                rows="6"
                                                variant="outlined"
                                            />
                                        </v-col>
                                    </v-row>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <!-- Right Column -->
                        <v-col cols="12" md="4">
                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-target</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Target Audience</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-select
                                        v-model="form.company_id"
                                        :error-messages="form.errors.company_id"
                                        :items="companies"
                                        clearable
                                        density="compact"
                                        item-title="name"
                                        item-value="id"
                                        label="Company"
                                        variant="outlined"
                                        class="mb-3"
                                        @update:model-value="form.department_id = null"
                                    />
                                    <v-select
                                        v-model="form.department_id"
                                        :error-messages="form.errors.department_id"
                                        :items="filteredDepartments"
                                        :disabled="!form.company_id"
                                        :hint="!form.company_id ? 'Select a company first' : ''"
                                        :persistent-hint="!form.company_id"
                                        clearable
                                        density="compact"
                                        item-title="name"
                                        item-value="id"
                                        label="Department (optional)"
                                        variant="outlined"
                                    />
                                </v-card-text>
                            </v-card>

                            <v-card variant="outlined" class="mb-5">
                                <v-toolbar density="compact" color="transparent" class="border-b">
                                    <v-icon class="ml-4" size="small">mdi-calendar-range</v-icon>
                                    <v-toolbar-title class="text-body-2 font-weight-bold">Schedule</v-toolbar-title>
                                </v-toolbar>
                                <v-card-text>
                                    <v-label class="mb-2 font-weight-medium text-caption">Publish Date</v-label>
                                    <el-date-picker
                                        v-model="form.published_at"
                                        format="YYYY-MM-DD"
                                        placeholder="Publish date"
                                        size="large"
                                        style="width: 100%"
                                        type="date"
                                        value-format="YYYY-MM-DD"
                                    />
                                    <div v-if="form.errors.published_at" class="text-error text-caption mt-1">{{ form.errors.published_at }}</div>

                                    <v-label class="mb-2 mt-4 font-weight-medium text-caption">Expiry Date</v-label>
                                    <el-date-picker
                                        v-model="form.expired_at"
                                        format="YYYY-MM-DD"
                                        placeholder="Expiry date (optional)"
                                        size="large"
                                        style="width: 100%"
                                        type="date"
                                        value-format="YYYY-MM-DD"
                                    />
                                    <div v-if="form.errors.expired_at" class="text-error text-caption mt-1">{{ form.errors.expired_at }}</div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-divider/>
                <v-card-actions class="justify-center pa-4">
                    <v-btn
                        :loading="form.processing"
                        class="text-none"
                        color="primary"
                        type="submit"
                        variant="flat"
                    >
                        Update
                    </v-btn>
                </v-card-actions>
            </form>
        </v-card>
    </DefaultLayout>
</template>
