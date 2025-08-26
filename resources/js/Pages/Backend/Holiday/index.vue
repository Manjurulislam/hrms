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
        {title: 'Holiday Date', key: 'day_at'},
        {title: 'Description', key: 'description', sortable: false},
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
    axios.get(route('holidays.get', state.filters)).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    });
};

const toggleStatus = (item) => {
    axios.get(route('holidays.toggle-status', item.id), {}, {preserveScroll: true})
        .then(() => toast('Holiday status has been updated.'));
};

const handleSearch = (filters) => {
    state.filters = filters;
    state.loading = true;
    getData(state.filters);
};

const truncateText = (text, length = 50) => {
    if (!text) return '-';
    return text.length > length ? text.substring(0, length) + '...' : text;
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getDateColor = (date) => {
    if (!date) return 'default';
    const today = new Date();
    const holidayDate = new Date(date);
    const diffTime = holidayDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return 'error'; // Past
    if (diffDays <= 7) return 'warning'; // Within a week
    if (diffDays <= 30) return 'info'; // Within a month
    return 'success'; // Future
};
</script>

<template>
    <DefaultLayout>
        <Head title="Holidays"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title: 'Add New' , route: 'holidays.create'}"
                        icon="mdi-plus"
                        title="Holidays"
                    />

                    <FilterWithoutTrash :dateSearch="true" :filters="state.filters" @handleFilter="handleSearch"/>
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
                            <template v-slot:item.day_at="{ item }">
                                <v-chip
                                    :color="getDateColor(item.day_at)"
                                    class="font-weight-regular"
                                    size="small"
                                    variant="tonal"
                                >
                                    {{ formatDate(item.day_at) }}
                                </v-chip>
                            </template>
                            <template v-slot:item.description="{ item }">
                                <span
                                    v-if="item.description"
                                    :title="item.description"
                                    class="text-body-2"
                                >
                                    {{ truncateText(item.description, 60) }}
                                </span>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <btn-link
                                    :route="route('holidays.edit', item.id)"
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
