<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";
import {computed} from "vue";

const toast = useToast();
const props = defineProps({
    companies: Array,
    departments: Array,
});

const form = useForm({
    title: '',
    description: '',
    company_id: null,
    department_id: null,
    published_at: '',
    expired_at: '',
    status: true,
});

const filteredDepartments = computed(() => {
    if (!form.company_id) return [];
    return props.departments.filter(d => d.company_id === form.company_id);
});

const submit = () => {
    form.post(route('notices.store'), {
        onSuccess: () => {
            toast('Notice has been created successfully.');
        },
        onError: () => {
            toast.error('Something is wrong. Please try again.');
        }
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Notice"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back' , route: 'notices.index', icon:'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        title="Create Notice"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4 rounded-md">
                            <v-row>
                                <v-col cols="12">
                                    <TextInput
                                        v-model="form.title"
                                        :error-messages="form.errors.title"
                                        label="Title"
                                    />
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12" md="6">
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
                                        @update:modelValue="form.department_id = null"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-select
                                        v-model="form.department_id"
                                        :error-messages="form.errors.department_id"
                                        :items="filteredDepartments"
                                        clearable
                                        density="compact"
                                        item-title="name"
                                        item-value="id"
                                        label="Department (optional - leave empty for all)"
                                        variant="outlined"
                                    />
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12" md="6">
                                    <div>
                                        <el-date-picker
                                            v-model="form.published_at"
                                            format="YYYY-MM-DD"
                                            placeholder="Publish date"
                                            size="large"
                                            style="width: 100%"
                                            type="date"
                                            value-format="YYYY-MM-DD"
                                        />
                                        <div v-if="form.errors.published_at" class="text-error text-caption mt-1">
                                            {{ form.errors.published_at }}
                                        </div>
                                    </div>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <div>
                                        <el-date-picker
                                            v-model="form.expired_at"
                                            format="YYYY-MM-DD"
                                            placeholder="Expiry date (optional)"
                                            size="large"
                                            style="width: 100%"
                                            type="date"
                                            value-format="YYYY-MM-DD"
                                        />
                                        <div v-if="form.errors.expired_at" class="text-error text-caption mt-1">
                                            {{ form.errors.expired_at }}
                                        </div>
                                    </div>
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="form.description"
                                        :error-messages="form.errors.description"
                                        label="Description"
                                        placeholder="Enter notice content..."
                                        rows="6"
                                        variant="outlined"
                                    />
                                </v-col>
                            </v-row>
                        </v-card-text>
                        <v-divider></v-divider>
                        <v-card-actions>
                            <v-btn
                                :loading="form.processing"
                                class="text-none mb-4 mx-auto"
                                color="primary"
                                type="submit"
                                variant="flat"
                            >
                                Submit
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
