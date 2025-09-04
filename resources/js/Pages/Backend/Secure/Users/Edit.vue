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
    roles: Array,
    selectedRole: Array
});

let form = useForm({
    name: '',
    email: '',
    password: '',
    status: true,
    role: []
});

const submit = () => {
    form.put(route('users.update', props.item.id), {
        onSuccess: () => toast('Data has been updated successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};

onMounted(() => {
    form = Object.assign(form, props.item);
    form.role = props.selectedRole || null;
});
</script>

<template>
    <DefaultLayout>
        <Head title="Users"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title:'Back', route:'users.index'}"
                        icon="mdi-arrow-left-bold"
                        title="Edit User"
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
                                        v-model="form.password"
                                        :error-messages="form.errors.password"
                                        label="Password"
                                        type="password"
                                    />
                                    <v-select
                                        v-model="form.role"
                                        :error-messages="form.errors.role"
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
                                Update
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
