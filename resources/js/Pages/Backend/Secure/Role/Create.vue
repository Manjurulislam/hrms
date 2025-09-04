<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import TextInput from '@/Components/common/form/TextInput.vue';

const toast = useToast();
const form = useForm({
    name: '',
    description: '',
    status: true
});

const submit = () => {
    form.post(route('roles.store'), {
        onSuccess: () => toast('Data has been added successfully.'),
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
                        title="Create Role"
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
                                        label="Discription"/>
                                </v-col>
                            </v-row>
                        </v-card-item>
                        <v-divider></v-divider>
                        <v-card-actions>
                            <v-btn class="text-none mb-4 mx-auto" color="primary" type="submit" variant="flat">
                                Save
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
