<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {onMounted} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import TextInput from "@/Components/common/form/TextInput.vue";

const toast = useToast();
const props = defineProps({
    item: Object,
});

let form = useForm({
    name: '',
    start_date: '',
    end_date: '',
    description: '',
    status: true
});

const submit = () => {
    form.put(route('company.holidays.update', props.item.id), {
        onSuccess: () => toast('Holiday has been updated successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};

onMounted(() => {
    form = Object.assign(form, props.item);
});
</script>

<template>
    <DefaultLayout>
        <Head title="Edit Holiday"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back' , route: 'company.holidays.index', icon:'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        title="Edit Holiday"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4 rounded-md">
                            <v-row>
                                <v-col cols="12" md="4">
                                    <TextInput v-model="form.name" :error-messages="form.errors.name" label="Name" required/>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-label class="text-caption mb-1">Start Date</v-label>
                                    <el-date-picker v-model="form.start_date" format="YYYY-MM-DD" placeholder="Select date" style="width: 100%" type="date" value-format="YYYY-MM-DD"/>
                                    <div v-if="form.errors.start_date" class="text-error text-caption mt-1">{{ form.errors.start_date }}</div>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-label class="text-caption mb-1">End Date</v-label>
                                    <el-date-picker v-model="form.end_date" format="YYYY-MM-DD" placeholder="Select date" style="width: 100%" type="date" value-format="YYYY-MM-DD"/>
                                    <div v-if="form.errors.end_date" class="text-error text-caption mt-1">{{ form.errors.end_date }}</div>
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12">
                                    <v-textarea v-model="form.description" :error-messages="form.errors.description" label="Description" placeholder="Enter holiday description..." rows="4" variant="outlined"/>
                                </v-col>
                            </v-row>
                        </v-card-text>
                        <v-divider></v-divider>
                        <v-card-actions>
                            <v-btn :loading="form.processing" class="text-none mb-4 mx-auto" color="primary" type="submit" variant="flat">
                                Update
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
