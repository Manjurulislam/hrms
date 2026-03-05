<script setup>
import TextInput from '@/Components/common/form/TextInput.vue';
import {Head, useForm} from '@inertiajs/vue3';
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {computed, onMounted, watch} from 'vue';
import {useToast} from 'vue-toastification';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';

const toast = useToast();
const props = defineProps({
    item: Object,
    companies: Array,
    parentDesignations: Array
});

let form = useForm({
    title: '',
    description: '',
    level: 5,
    company_id: null,
    parent_id: null,
});

const filteredParentDesignations = computed(() => {
    if (!form.company_id) return props.parentDesignations;
    return props.parentDesignations.filter(d => d.company_id === form.company_id);
});

const submit = () => {
    form.put(route('designations.update', props.item.id), {
        onSuccess: () => toast('Designation has been updated successfully.'),
        onError: () => toast.error('Something is wrong. Please try again.')
    });
};

const onCompanyChange = () => {
    form.parent_id = null;
};

onMounted(() => {
    form = Object.assign(form, props.item);
});
</script>

<template>
    <DefaultLayout>
        <Head title="Edit Designation"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :extra-route="{title: 'Back' , route: 'designations.index', icon:'mdi-arrow-left-bold'}"
                        icon="mdi-arrow-left-bold"
                        title="Edit Designation"
                    />
                    <form @submit.prevent="submit">
                        <v-card-text class="mt-4 rounded-md">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <TextInput
                                        v-model="form.title"
                                        :error-messages="form.errors.title"
                                        label="Title"
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
                                        @update:model-value="onCompanyChange"
                                    />
                                </v-col>
                            </v-row>

                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        v-model="form.level"
                                        :error-messages="form.errors.level"
                                        density="compact"
                                        hint="1 = Highest (CEO), 5 = Lowest (Developer)"
                                        label="Level"
                                        max="10"
                                        min="1"
                                        persistent-hint
                                        type="number"
                                        variant="outlined"
                                    />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-select
                                        v-model="form.parent_id"
                                        :error-messages="form.errors.parent_id"
                                        :items="filteredParentDesignations"
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
                                Update
                            </v-btn>
                        </v-card-actions>
                    </form>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
