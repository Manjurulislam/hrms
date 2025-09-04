<script setup>
import TextInput from '@/Components/common/form/TextInput.vue';
import {Head, useForm} from '@inertiajs/vue3';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const toast = useToast();
const props = defineProps({
    item: Object,
});

let form = useForm({
    name: props.item?.name || '',
    description: props.item?.description || '',
    status: props.item?.status ?? true
});

const submit = () => {
    form.put(route('roles.update', props.item.id), {
        onSuccess: () => toast('Data has been updated successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Role"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title:'Back', route:'roles.index'}"
                        icon="mdi-arrow-left-bold"
                        title="Edit Role"
                    />
                    <form @submit.prevent="submit">
                        <v-card-item>
                            <v-row justify="center">
                                <v-col class="mt-5" cols="8">
                                    <TextInput
                                        v-model="form.name"
                                        :error-messages="form.errors.name"
                                        label="Name"/>

                                    <TextInput
                                        v-model="form.description"
                                        :error-messages="form.errors.description"
                                        label="Description"/>
                                </v-col>
                            </v-row>
                        </v-card-item>
                        <v-divider></v-divider>
                        <v-card-actions>
                            <v-btn class="text-none mb-4 mx-auto" color="primary" type="submit" variant="flat">
                                Update
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
