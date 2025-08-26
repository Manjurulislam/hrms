<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import TextInput from '@/Components/common/form/TextInput.vue';

const props = defineProps({
    roles: Array
});

const toast = useToast();
const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    status: true,
    roles: []
});

const submit = () => {
    form.post(route('core.users.store'), {
        onSuccess: () => toast('Data has been added successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Users"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title:'Back', route:'core.users.index'}"
                        icon="mdi-arrow-left-bold"
                        title="Create User"
                    />
                    <form @submit.prevent="submit">
                        <v-card-item>
                            <v-row>
                                <v-col class="mt-5" cols="8">
                                    <TextInput
                                        v-model="form.name"
                                        :error-messages="form.errors.name"
                                        label="Name"
                                    />
                                    <TextInput
                                        v-model="form.email"
                                        :error-messages="form.errors.email"
                                        label="Email"
                                    />
                                    <TextInput
                                        v-model="form.phone"
                                        :error-messages="form.errors.phone"
                                        label="Phone"
                                    />
                                    <TextInput
                                        v-model="form.password"
                                        :error-messages="form.errors.password"
                                        label="Password"
                                        type="password"
                                    />
                                    <v-select
                                        v-model="form.roles"
                                        :error-messages="form.errors.roles"
                                        :items="roles"
                                        clearable
                                        density="compact"
                                        item-title="name"
                                        item-value="id"
                                        label="Roles"
                                        multiple
                                    />
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
