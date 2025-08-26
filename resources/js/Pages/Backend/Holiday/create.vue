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
    description: '',
    day_at: '',
    company_id: null,
    status: true,
});

const submit = () => {
    form.post(route('holidays.store'), {
        onSuccess: (success) => {
            toast('Holiday has been added successfully.');
        },
        onError: (error) => {
            toast.error('Something is wrong. Please try again.');
        }
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Holiday"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back' , route: 'holidays.index', icon:'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        title="Create Holiday"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4 rounded-md">
                            <v-row>
                                <v-col cols="12" md="4">
                                    <TextInput
                                        v-model="form.name"
                                        :error-messages="form.errors.name"
                                        label="Name"
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
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
                                    />
                                </v-col>

                                <v-col cols="12" md="4">
                                    <div>
                                        <el-date-picker
                                            v-model="form.day_at"
                                            format="YYYY-MM-DD"
                                            placeholder="Select holiday date"
                                            size="large"
                                            style="width: 100%"
                                            type="date"
                                            value-format="YYYY-MM-DD"
                                        />
                                        <div v-if="form.errors.day_at" class="text-error text-caption mt-1">
                                            {{ form.errors.day_at }}
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
                                        placeholder="Enter holiday description..."
                                        rows="4"
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
