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
        {title: 'Code', key: 'code'},
        {title: 'Email', key: 'email'},
        {title: 'Phone', key: 'phone'},
        {title: 'Website', key: 'website'},
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
    axios.get(route('companies.get', state.filters)).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    });
};

const toggleStatus = (item) => {
    axios.get(route('companies.toggle-status', item.id), {}, {preserveScroll: true})
        .then(() => toast('Company status has been updated.'));
};

const handleSearch = (filters) => {
    state.filters = filters;
    state.loading = true;
    getData(state.filters);
};
</script>

<template>
    <DefaultLayout>
        <Head title="Companies"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title: 'Add New' , route: 'companies.create'}"
                        icon="mdi-plus"
                        title="Companies"
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
                            <template v-slot:item.website="{ item }">
                                <a
                                    v-if="item.website"
                                    :href="item.website"
                                    class="text-decoration-none text-primary"
                                    target="_blank"
                                >
                                    <v-icon size="small">mdi-open-in-new</v-icon>
                                    Visit
                                </a>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <btn-link
                                    :route="route('companies.edit', item.id)"
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
