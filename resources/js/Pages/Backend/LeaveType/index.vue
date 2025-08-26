<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head} from '@inertiajs/vue3';
import BtnLink from '@/Components/common/utility/BtnLink.vue';
import {reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import FilterWithoutTrash from '@/Components/common/filter/FilterWithoutTrash.vue';
import {useToast} from 'vue-toastification';

const toast = useToast();
const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Name', key: 'name'},
        {title: 'Company', key: 'company'},
        {title: 'Days', key: 'days', align: 'center'},
        {title: 'Status', key: 'status', sortable: false, width: '8%'},
        {title: 'Actions', key: 'actions', sortable: false, width: '8%'}
    ],
    pagination: {
        itemsPerPage: 10,
        totalItems: 0
    },
    filters: {
        search: '',
        dateSearch: null,
        isChecked: false,
        per_page: 10
    },
    serverItems: [],
    loading: true
});

const setLimit = (obj) => {
    const {page, itemsPerPage, sortBy} = obj;
    state.filters.page = page;
    state.filters.sort = sortBy;
    state.filters.per_page = itemsPerPage === 'All' ? -1 : itemsPerPage;
};

const getData = (obj) => {
    setLimit(obj);
    axios.get(route('leave-types.get', state.filters)).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    });
};

const toggleStatus = (item) => {
    axios.get(route('leave-type.toggle-status', item.id), {}, {preserveScroll: true})
        .then(() => toast('Leave type status has been updated.'));
};

const handleSearch = (filters) => {
    state.filters = filters;
    state.loading = true;
    getData(state.filters);
};

const getDaysColor = (days) => {
    if (days <= 5) return 'error'; // Very few days
    if (days <= 15) return 'warning'; // Moderate days
    if (days <= 30) return 'info'; // Good amount
    return 'success'; // Generous amount
};

const getDaysLabel = (days) => {
    if (days === 1) return '1 Day';
    return `${days} Days`;
};
</script>

<template>
    <DefaultLayout>
        <Head title="Leave Types"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title: 'Add New' , route: 'leave-types.create'}"
                        icon="mdi-plus"
                        title="Leave Types"
                    />

                    <FilterWithoutTrash :dateSearch="false" :filters="state.filters" @handleFilter="handleSearch"/>
                    <v-card-text>
                        <v-data-table-server
                            :headers="state.headers"
                            :items="state.serverItems"
                            :items-length="state.pagination.totalItems"
                            :items-per-page="state.pagination.itemsPerPage"
                            :loading="state.loading"
                            :search="state.searchParam"
                            density="compact"
                            item-value="name"
                            @update:options="getData"
                        >
                            <template v-slot:item.id="{ index }">
                                {{ index + 1 }}
                            </template>
                            <template v-slot:item.status="{ item }">
                                <v-switch
                                    v-model="item.status"
                                    color="success"
                                    density="compact"
                                    hide-details
                                    @change="() => toggleStatus(item)"
                                />
                            </template>
                            <template v-slot:item.company="{ item }">
                                <v-chip
                                    v-if="item.company"
                                    class="font-weight-regular"
                                    color="primary"
                                    size="small"
                                    variant="tonal"
                                >
                                    {{ item.company.name }}
                                </v-chip>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.days="{ item }">
                                <v-chip
                                    :color="getDaysColor(item.days)"
                                    class="font-weight-medium"
                                    size="small"
                                    variant="flat"
                                >
                                    <v-icon size="small" start>mdi-calendar-clock</v-icon>
                                    {{ getDaysLabel(item.days) }}
                                </v-chip>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <btn-link
                                    :route="route('leave-types.edit', item.id)"
                                    color="bg-darkprimary"
                                    icon="mdi-pencil"/>
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </DefaultLayout>
</template>
