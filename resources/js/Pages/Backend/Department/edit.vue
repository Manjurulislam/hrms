<script setup>
import TextInput from '@/Components/common/form/TextInput.vue';
import {Head, useForm} from '@inertiajs/vue3';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {onMounted} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const toast = useToast();
const props = defineProps({
    item: Object,
    companies: Array
});

let form = useForm({
    name: '',
    description: '',
    company_id: null,
    status: true
});

const submit = () => {
    form.put(route('departments.update', props.item.id), {
        onSuccess: () => toast('Department has been updated successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};

onMounted(() => {
    form = Object.assign(form, props.item);
});
</script>

<template>
    <DefaultLayout>
        <Head title="Edit Department"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back' , route: 'departments.index', icon:'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        title="Edit Department"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4 rounded-md">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.name"
                                        :error-messages="form.errors.name"
                                        label="Name"
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
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="form.description"
                                        :error-messages="form.errors.description"
                                        label="Description"
                                        placeholder="Enter department description..."
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
                                Update
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
