<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import {onMounted} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import CardTitle from '@/Components/common/card/CardTitle.vue';

const toast = useToast();
const props = defineProps({
    company: Object,
    item: Object,
});

const days = [
    {value: 0, label: 'Sunday'},
    {value: 1, label: 'Monday'},
    {value: 2, label: 'Tuesday'},
    {value: 3, label: 'Wednesday'},
    {value: 4, label: 'Thursday'},
    {value: 5, label: 'Friday'},
    {value: 6, label: 'Saturday'},
];

let form = useForm({
    day_of_week: null,
    day_label: '',
    is_working: true,
});

const onDayChange = (val) => {
    const day = days.find(d => d.value === val);
    if (day) {
        form.day_label = day.label;
    }
};

const submit = () => {
    form.put(route('working-days.update', {company: props.company.id, workingDay: props.item.id}), {
        onSuccess: () => toast('Working day has been updated successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};

onMounted(() => {
    form = Object.assign(form, props.item);
});
</script>

<template>
    <DefaultLayout>
        <Head :title="`Edit Working Day - ${company.name}`"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back', route: 'working-days.index', queryParam: company.id, icon: 'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        :title="`Edit Working Day - ${company.name}`"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4 rounded-md">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-select
                                        v-model="form.day_of_week"
                                        :error-messages="form.errors.day_of_week"
                                        :items="days"
                                        item-title="label"
                                        item-value="value"
                                        density="compact"
                                        label="Day"
                                        required
                                        variant="outlined"
                                        @update:model-value="onDayChange"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-switch
                                        v-model="form.is_working"
                                        :error-messages="form.errors.is_working"
                                        color="primary"
                                        density="compact"
                                        hide-details="auto"
                                        label="Is Working Day"
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
