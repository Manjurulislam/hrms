<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();
const props = defineProps({
    companies: Array
});

const form = useForm({
    name: '',
    days: '',
    company_id: null,
    status: true,
});

const submit = () => {
    form.post(route('leave-types.store'), {
        onSuccess: (success) => {
            toast('Leave type has been added successfully.');
        },
        onError: (error) => {
            toast.error('Something is wrong. Please try again.');
        }
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Leave Type"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back' , route: 'leave-types.index', icon:'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        title="Create Leave Type"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4 rounded-md">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.name"
                                        :error-messages="form.errors.name"
                                        label="Name"
                                        placeholder="e.g., Annual Leave, Sick Leave"
                                        required
                                    />
                                </v-col>
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
                                        required
                                        variant="outlined"
                                    />
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        v-model="form.days"
                                        :error-messages="form.errors.days"
                                        density="compact"
                                        label="Number of Days"
                                        min="0"
                                        placeholder="e.g., 21"
                                        required
                                        step="1"
                                        type="number"
                                        variant="outlined"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <div class="mt-3">
                                        <v-label class="mb-2 font-weight-medium">Status</v-label>
                                        <div>
                                            <el-switch
                                                v-model="form.status"
                                                size="large"
                                                style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"
                                            />
                                        </div>
                                    </div>
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
