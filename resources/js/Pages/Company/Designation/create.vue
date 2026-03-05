<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();
const props = defineProps({
    parentDesignations: Array,
});

const form = useForm({
    title: '',
    level: null,
    parent_id: null,
    description: '',
    status: true,
});

const submit = () => {
    form.post(route('company.designations.store'), {
        onSuccess: () => {
            toast('Designation has been added successfully.');
        },
        onError: () => {
            toast.error('Something is wrong. Please try again.');
        }
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Designation"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back' , route: 'company.designations.index', icon:'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        title="Create Designation"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4 rounded-md">
                            <v-row>
                                <v-col cols="12" md="4">
                                    <TextInput
                                        v-model="form.title"
                                        :error-messages="form.errors.title"
                                        label="Title"
                                        required
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
                                    <TextInput
                                        v-model="form.level"
                                        :error-messages="form.errors.level"
                                        label="Level"
                                        type="number"
                                        required
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-select
                                        v-model="form.parent_id"
                                        :error-messages="form.errors.parent_id"
                                        :items="parentDesignations"
                                        clearable
                                        density="compact"
                                        item-title="title"
                                        item-value="id"
                                        label="Parent Designation"
                                        variant="outlined"
                                    />
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="form.description"
                                        :error-messages="form.errors.description"
                                        label="Description"
                                        placeholder="Enter designation description..."
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
