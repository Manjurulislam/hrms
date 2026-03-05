<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();

const form = useForm({
    name: '',
    max_per_year: null,
    status: true,
});

const submit = () => {
    form.post(route('company.leave-types.store'), {
        onSuccess: () => toast('Leave type has been added successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
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
                        :extra-route="{title: 'Back' , route: 'company.leave-types.index', icon:'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        title="Create Leave Type"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4 rounded-md">
                            <v-row>
                                <v-col cols="12" md="4">
                                    <TextInput v-model="form.name" :error-messages="form.errors.name" label="Name" required/>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <TextInput v-model="form.max_per_year" :error-messages="form.errors.max_per_year" label="Max Days Per Year" type="number" required/>
                                </v-col>
                                <v-col cols="12" md="4" class="d-flex align-center">
                                    <el-switch v-model="form.status" active-text="Active" inactive-text="Inactive"/>
                                </v-col>
                            </v-row>
                        </v-card-text>
                        <v-divider></v-divider>
                        <v-card-actions>
                            <v-btn :loading="form.processing" class="text-none mb-4 mx-auto" color="primary" type="submit" variant="flat">
                                Submit
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
