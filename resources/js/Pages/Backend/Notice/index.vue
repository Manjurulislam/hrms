<script setup>
import CardTitle from '@/Components/common/card/CardTitle.vue';
import {Head} from '@inertiajs/vue3';
import BtnLink from '@/Components/common/utility/BtnLink.vue';
import {reactive} from 'vue';
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import NoticeFilter from '@/Components/common/filter/NoticeFilter.vue';
import {useToast} from 'vue-toastification';

const props = defineProps({
    companies: Array,
    defaultCompanyId: Number,
});

const toast = useToast();
const state = reactive({
    headers: [
        {title: 'SL', align: 'start', sortable: false, key: 'id'},
        {title: 'Title', key: 'title'},
        {title: 'Company', key: 'company'},
        {title: 'Department', key: 'department'},
        {title: 'Published', key: 'published_at'},
        {title: 'Expires', key: 'expired_at'},
        {title: 'Created By', key: 'creator'},
        {title: 'Status', key: 'status', sortable: false, width: '8%'},
        {title: 'Actions', key: 'actions', sortable: false, width: '8%'}
    ],
    pagination: {
        itemsPerPage: 50,
        totalItems: 0
    },
    filters: {
        search: '',
        company_id: props.defaultCompanyId,
        status: null,
        per_page: 50
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
    axios.get(route('notices.get', state.filters)).then(({data}) => {
        state.loading = false;
        state.serverItems = data.data;
        state.pagination.totalItems = data.total;
    });
};

const toggleStatus = (item) => {
    axios.post(route('notices.toggle-status', item.id))
        .then(() => toast('Notice status has been updated.'));
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
    const d = new Date(date);
    const diffDays = Math.ceil((d.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return 'error';
    if (diffDays <= 7) return 'warning';
    return 'success';
};
</script>

<template>
    <DefaultLayout>
        <Head title="Notice Board"/>
        <v-row no-gutters>
            <v-col cols="12">
                <v-card>
                    <CardTitle
                        :router="{title: 'Add New' , route: 'notices.create'}"
                        icon="mdi-plus"
                        title="Notice Board"
                    />

                    <NoticeFilter
                        :filters="state.filters"
                        :companies="props.companies"
                        @handleFilter="handleSearch"
                    />
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
                            <template v-slot:item.title="{ item }">
                                {{ truncateText(item.title, 40) }}
                            </template>
                            <template v-slot:item.company="{ item }">
                                <v-chip
                                    v-if="item.company"
                                    class="font-weight-regular"
                                    color="primary"
                                    size="x-small"
                                    variant="tonal"
                                >
                                    {{ item.company.name }}
                                </v-chip>
                                <span v-else class="text-muted">-</span>
                            </template>
                            <template v-slot:item.department="{ item }">
                                <v-chip
                                    v-if="item.department"
                                    class="font-weight-regular"
                                    color="info"
                                    size="x-small"
                                    variant="tonal"
                                >
                                    {{ item.department.name }}
                                </v-chip>
                                <span v-else class="text-grey">All</span>
                            </template>
                            <template v-slot:item.published_at="{ item }">
                                <v-chip
                                    :color="getDateColor(item.published_at)"
                                    class="font-weight-regular"
                                    size="x-small"
                                    variant="tonal"
                                >
                                    {{ formatDate(item.published_at) }}
                                </v-chip>
                            </template>
                            <template v-slot:item.expired_at="{ item }">
                                <v-chip
                                    v-if="item.expired_at"
                                    :color="getDateColor(item.expired_at)"
                                    class="font-weight-regular"
                                    size="x-small"
                                    variant="tonal"
                                >
                                    {{ formatDate(item.expired_at) }}
                                </v-chip>
                                <span v-else class="text-grey">No Expiry</span>
                            </template>
                            <template v-slot:item.creator="{ item }">
                                {{ item.creator?.name || '-' }}
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
                            <template v-slot:item.actions="{ item }">
                                <btn-link
                                    :route="route('notices.edit', item.id)"
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
