<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head} from '@inertiajs/vue3';
import BtnLink from '@/Components/common/utility/BtnLink.vue';
import BtnDelete from '@/Components/common/utility/BtnDelete.vue';
import {reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {useToast} from 'vue-toastification';

const props = defineProps({
    company: Object,
});

const toast = useToast();
const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Day', key: 'day_label'},
        {title: 'Day of Week', key: 'day_of_week'},
        {title: 'Working', key: 'is_working', sortable: false, width: '8%'},
        {title: 'Actions', key: 'actions', sortable: false, width: '10%'}
    ],
    pagination: {
        itemsPerPage: 50,
        totalItems: 0
    },
    filters: {
        search: '',
        per_page: 50,
        page: 1
    },
    serverItems: [],
    loading: true
});

const dayLabels = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const setLimit = (obj) => {
    const {page, itemsPerPage, sortBy} = obj;
    state.filters.page = page;
    state.filters.sort = sortBy;
    state.filters.per_page = itemsPerPage === 'All' ? -1 : itemsPerPage;
};

const getData = (obj) => {
    setLimit(obj);
    axios.get(route('working-days.get', {company: props.company.id, ...state.filters})).then(({data}) => {
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    }).catch(() => {
        toast.error('Failed to load working days.');
    }).finally(() => {
        state.loading = false;
    });
};

const toggleStatus = (item) => {
    axios.post(route('working-days.toggle-status', {company: props.company.id, workingDay: item.id}))
        .then(() => toast('Working day status has been updated.'));
};
</script>

<template>
    <DefaultLayout>
        <Head :title="`Working Days - ${company.name}`"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title: 'Add New', route: 'working-days.create', queryParam: company.id}"
                        :extra-route="{title: 'Back', route: 'companies.index', icon: 'mdi-arrow-left-bold'}"
                        icon="mdi-plus"
                        :title="`Working Days - ${company.name}`"
                    />
                    <v-card-text>
                        <v-data-table-server
                            :headers="state.headers"
                            :items="state.serverItems"
                            :items-length="state.pagination.totalItems"
                            :items-per-page="state.pagination.itemsPerPage"
                            :loading="state.loading"
                            density="compact"
                            item-value="id"
                            @update:options="getData"
                        >
                            <template v-slot:item.id="{ index }">
                                {{ (state.filters.page - 1) * state.pagination.itemsPerPage + index + 1 }}
                            </template>
                            <template v-slot:item.day_of_week="{ item }">
                                {{ dayLabels[item.day_of_week] ?? item.day_of_week }}
                            </template>
                            <template v-slot:item.is_working="{ item }">
                                <v-switch
                                    v-model="item.is_working"
                                    color="success"
                                    density="compact"
                                    hide-details
                                    @change="() => toggleStatus(item)"
                                />
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <div class="d-flex ga-1">
                                    <btn-link
                                        :route="route('working-days.edit', {company: company.id, workingDay: item.id})"
                                        color="bg-darkprimary"
                                        icon="mdi-pencil"/>
                                    <btn-delete :route="route('working-days.destroy', {company: company.id, workingDay: item.id})"/>
                                </div>
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
