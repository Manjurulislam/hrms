<script setup>
import {Head, useForm} from "@inertiajs/vue3";
import {useToast} from "vue-toastification";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import CardTitle from "@/Components/common/card/CardTitle.vue";
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();
const form = useForm({
    name: '',
    code: '',
    email: '',
    phone: '',
    address: '',
    website: '',
    status: true,
});

const submit = () => {
    form.post(route('company.store'), {
        onSuccess: (success) => {
            toast('Company has been added successfully.');
        },
        onError: (error) => {
            toast.error('Something is wrong. Please try again.');
        }
    });
};
</script>

<template>
    <DefaultLayout>
        <Head title="Create Company"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        icon="mdi-arrow-left-bold"
                        title="Create Company"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4 rounded-md">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.name"
                                        :error-messages="form.errors.name"
                                        label="Company Name"
                                        required
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.code"
                                        :error-messages="form.errors.code"
                                        label="Company Code"
                                        required
                                    />
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.email"
                                        :error-messages="form.errors.email"
                                        label="Email"
                                        type="email"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.phone"
                                        :error-messages="form.errors.phone"
                                        label="Phone"
                                        type="tel"
                                    />
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.website"
                                        :error-messages="form.errors.website"
                                        label="Website"
                                        placeholder="https://example.com"
                                        type="url"
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

                            <v-row>
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="form.address"
                                        :error-messages="form.errors.address"
                                        label="Address"
                                        rows="3"
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
